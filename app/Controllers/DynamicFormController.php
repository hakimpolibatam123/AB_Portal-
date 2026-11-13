<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Database;
use App\Core\View;
use App\Helpers\DashboardData;
use App\Helpers\Format;
use App\Models\Attachment;
use App\Models\AttentionList;
use App\Models\Dashboard;
use App\Models\FormEntries;
use App\Models\NumberSequence;
use App\Services\Attention;
use App\Services\FileUpload;
use App\Services\FormAnswers;
use App\Services\FormDefinition;
use App\Services\FormRecap;
use App\Services\FormToken;
use App\Services\RecapExport;
use App\Services\ReportSignature;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;

/**
 * Forms made on the dashboard. Their questions are edited in "Edit Isi Form" (each save is a new form version);
 * their reports are saved on the server (form_entries) with the version they were filled in. Routes:
 *   GET  /form-dinamis?id=<slug>                           fill-in page (public/assets/js/form-dinamis.js)
 *   POST /form-dinamis/entri?id=<slug>                     save a report (multipart, X-CSRF-Token) → JSON
 *   GET  /form-dinamis/respons?id=<slug>                   recap page (public/assets/js/rekap.js)
 *   GET  /form-dinamis/respons/data?id=<slug>              rows and statistics → JSON
 *   GET  /form-dinamis/respons/ekspor?id=<slug>&format=…    Excel, PDF or print of the filtered reports (PRD §9)
 *   GET  /form-dinamis/entri/{id}                          detail with the history → JSON
 * A sent report is read only: it is not changed or removed afterwards (like a disposal record).
 */
final class DynamicFormController extends Controller
{
    private const OWNER = 'form_entry';
    private const ICONS = ['send', 'arrow-left', 'calendar', 'clock', 'file-up', 'star', 'check', 'circle-check', 'circle-alert', 'triangle-alert', 'table-2', 'chart-column', 'plus', 'x', 'list-checks', 'info', 'search', 'eye', 'history', 'refresh-ccw', 'chevron-left', 'chevron-right', 'file-pen-line', 'pencil', 'trash-2', 'paperclip', 'save', 'arrow-up', 'arrow-down', 'clipboard-list'];

    public function fill(): void
    {
        $form = $this->formOrRedirect();
        if ($form !== null) {
            $this->page($form);
        }
    }

    public function responses(): void
    {
        $form = $this->formOrRedirect();
        if ($form !== null) {
            $this->recap($form);
        }
    }

    public function store(): void
    {
        if ($this->tooLarge()) {
            return;
        }
        $form = $this->formFromQuery();
        if ($form === null) {
            json_response(['message' => 'Form tidak ditemukan. Mungkin sudah dihapus.'], 404);
            return;
        }
        $token = $_POST['form_token'] ?? null;
        $tokenState = FormToken::state($form['slug'], $token);
        if ($tokenState === 'used') {
            json_response(['message' => 'Form ini sudah terkirim sebelumnya.', 'duplicate' => true] + FormToken::usedFor($form['slug'], (string) $token), 409);
            return;
        }
        if ($tokenState === 'unknown') {
            json_response(['message' => 'Halaman form sudah kedaluwarsa. Muat ulang halaman lalu kirim lagi; isian Anda tetap tersimpan di browser.'], 419);
            return;
        }
        // The page shows the current version; a version saved meanwhile in the editor is refused (its questions differ).
        if ((int) ($_POST['versi'] ?? 0) !== (int) $form['current_version_id']) {
            json_response(['message' => 'Pertanyaan form ini baru saja diubah. Muat ulang halaman; isian Anda tetap tersimpan di browser.'], 409);
            return;
        }
        $items = FormDefinition::answerItems($this->definition($form['definition_json']));
        $check = FormAnswers::check($items, $_POST, $this->uploadedFiles($items));
        if ($check['errors'] !== []) {
            $this->invalid($check['errors']);
            return;
        }
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $date = $now->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('Y-m-d');
            $entryNo = NumberSequence::next($pdo, $form['entry_prefix'] ?: 'F' . $form['id'], $date);
            $entries = new FormEntries($pdo);
            $id = $entries->create([
                'form_id' => (int) $form['id'], 'version_id' => (int) $form['current_version_id'], 'entry_no' => $entryNo,
                'submitted_at' => $now->format('Y-m-d H:i:s'), 'entry_date' => $date, 'answers_json' => '{}', 'search_text' => '', 'attention' => 0,
            ]);
            $answers = $this->withFiles($pdo, $id, $check['answers'], $check['uploads']);
            $entries->update($id, $this->stored($items, $answers));
            Audit::log('create', self::OWNER, $id, ['form' => $form['slug'], 'version' => (int) $form['version_no']], $entryNo, pdo: $pdo);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Form dinamis: ' . $exception->getMessage());
            json_response(['message' => 'Laporan tidak tersimpan. Coba lagi; isian Anda tetap di form.'], 500);
            return;
        }
        $answer = ['entry_no' => $entryNo, 'rekap_url' => $this->urls($form)['rekap']];
        FormToken::markUsed($form['slug'], (string) $token, $answer);
        json_response(['message' => 'Laporan tersimpan'] + $answer, 201);
    }

    /** GET /form-dinamis/respons/data?id=<slug>: rows and statistics for the filters in the query. */
    public function data(): void
    {
        $form = $this->formFromQuery();
        if ($form === null) {
            json_response(['message' => 'Form tidak ditemukan. Mungkin sudah dihapus.'], 404);
            return;
        }
        $filters = FormRecap::filters($_GET);
        $items = FormDefinition::answerItems($this->definition($form['definition_json']));
        $entries = $this->entries();
        $page = $entries->page((int) $form['id'], $filters, $filters['page'], $filters['per']);
        $pages = max(1, (int) ceil($page['total'] / $filters['per']));
        if ($filters['page'] > $pages) {
            $filters['page'] = $pages;
            $page = $entries->page((int) $form['id'], $filters, $pages, $filters['per']);
        }
        $from = ($filters['page'] - 1) * $filters['per'];
        $month = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')));
        json_response([
            'filters' => array_intersect_key($filters, array_flip(['period', 'range_from', 'range_to', 'attention', 'q', 'sort', 'dir', 'per', 'page'])),
            'error' => $filters['error'],
            'columns' => FormRecap::columns($items),
            'rows' => array_map(static fn (array $row): array => FormRecap::row($row, $items), $page['rows']),
            'total' => $page['total'],
            'pages' => $pages,
            'count_text' => $page['total'] > 0
                ? 'Menampilkan ' . ($from + 1) . '–' . ($from + count($page['rows'])) . ' dari ' . $page['total'] . ' laporan'
                : '0 laporan',
            'stats' => FormRecap::stats($entries->stats((int) $form['id'], $filters, $month->format('Y-m-01'), $month->format('Y-m-t'))),
        ]);
    }

    /** GET /form-dinamis/respons/ekspor?id=<slug>&format=xlsx|pdf|cetak: every report of the filters (PRD §9). */
    public function export(): void
    {
        $form = $this->formFromQuery();
        if ($form === null) {
            $this->notFound();
            return;
        }
        $filters = FormRecap::filters($_GET);
        $items = FormDefinition::answerItems($this->definition($form['definition_json']));
        $entries = $this->entries();
        $month = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
        $stats = $entries->stats((int) $form['id'], $filters, $month->format('Y-m-01'), $month->format('Y-m-t'));
        RecapExport::send((string) ($_GET['format'] ?? ''), [
            'module' => 'rekap-' . $form['slug'],
            'title' => 'Rekap ' . $form['title'],
            'name' => (string) $form['title'],
            // Every period by default: the file is named by the first and last report (FR-E4).
            'from' => $filters['from'] ?? $stats['first_date'] ?? $month->format('Y-m-d'),
            'to' => $filters['to'] ?? $stats['last_date'] ?? $month->format('Y-m-d'),
            'period' => RecapExport::period($filters['month'], $filters['from'], $filters['to']),
            'filters' => FormRecap::filterLabels($filters),
            'stats' => FormRecap::stats($stats),
            'columns' => FormRecap::exportColumns($items),
            'signature' => ReportSignature::fromJson($form['report_signature_json'] ?? null),
            'total' => $stats['count'],
            'rows' => RecapExport::rows(
                static fn (int $page, int $per): array => $entries->page((int) $form['id'], $filters, $page, $per)['rows'],
                static fn (array $row): array => FormRecap::exportRow($row, $items)
            ),
            'error' => $filters['error'],
        ], $_GET);
    }

    /**
     * POST /form-dinamis/tanda-tangan?id=<slug> {left_title, left_role, left_name, right_title, right_role,
     * right_name}: the signature block of this form's PDF and Cetak export ("Tanda tangan" on the recap page).
     */
    public function saveSignature(): void
    {
        $form = $this->formFromQuery();
        if ($form === null) {
            json_response(['message' => 'Form tidak ditemukan. Mungkin sudah dihapus.'], 404);
            return;
        }
        $check = ReportSignature::check(json_decode((string) file_get_contents('php://input'), true));
        if ($check['errors'] !== []) {
            json_response(['message' => 'Periksa kembali isian yang ditandai.', 'errors' => (object) $check['errors']], 422);
            return;
        }
        $old = ReportSignature::fromJson($form['report_signature_json'] ?? null);
        $pdo = Database::connection();
        try {
            $pdo->beginTransaction();
            $this->model()->saveSignature((int) $form['id'], $check['values']);
            $changes = Audit::changes($old, $check['values'], ReportSignature::FIELDS);
            if ($changes !== []) {
                Audit::log('update', 'form', (int) $form['id'], ['title' => $form['title'], 'changes' => $changes], pdo: $pdo);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Tanda tangan ekspor: ' . $exception->getMessage());
            json_response(['message' => 'Tanda tangan tidak tersimpan. Coba lagi.'], 500);
            return;
        }
        json_response(['message' => 'Tanda tangan tersimpan', 'signature' => $check['values']]);
    }

    /** GET /form-dinamis/entri/{id}: every answer with the questions of its version, files and change history. */
    public function detail(array $params): void
    {
        $entry = $this->entries()->find((int) $params['id']);
        if ($entry === null) {
            json_response(['message' => 'Laporan tidak ditemukan. Mungkin sudah dihapus.'], 404);
            return;
        }
        // Opening a "Perlu perhatian" report counts as seen for the header bell.
        if ((int) ($entry['attention'] ?? 0) === 1 && ($userId = Attention::userId()) !== null) {
            try {
                (new AttentionList(Database::connection()))->markRead($userId, [(int) $entry['id']]);
            } catch (Throwable $exception) {
                error_log('Tandai dilihat: ' . $exception->getMessage());
            }
        }
        $definition = $this->definition($entry['definition_json']);
        $answers = json_decode((string) $entry['answers_json'], true) ?: [];
        $labels = [];
        foreach (FormDefinition::answerItems($definition) as $q) {
            $labels[$q['id']] = $q['type'] === 'confirm' ? 'Pernyataan' : $q['title'];
        }
        $fields = [
            ['label' => 'Waktu kirim', 'text' => Format::dateTime($entry['submitted_at'])],
            ['label' => 'Versi form', 'text' => 'Versi ' . $entry['version_no'] . ' · ' . $entry['version_title']],
            ...((bool) $entry['attention'] ? [['label' => 'Status', 'text' => 'Perlu perhatian', 'chip' => 'Perlu perhatian', 'tone' => 'warn']] : []),
            ...FormRecap::fields($definition, $answers),
        ];
        json_response([
            'title' => $entry['entry_no'],
            'sub' => 'Dikirim ' . Format::dateTime($entry['created_at']),
            'fields' => $fields,
            'history' => Audit::history(self::OWNER, (int) $entry['id'], $entry['entry_no'], $labels),
        ]);
    }

    /** The fill-in page of a form: a new report (a sent report is read only, so it is never opened here again). */
    private function page(array $form): void
    {
        $data = $this->dashboardData(withQuestions: true);
        if ($data === null) {
            return;
        }
        if (!isset($data['forms'][$form['slug']])) {
            $this->notFound();
            return;
        }
        $this->render('form-dinamis/fill', [
            'pageTitle' => (string) $form['title'],
            'portalPage' => true,
            'backToDashboard' => true,
            'breadcrumbWidth' => 'breadcrumb-fill',
            'formId' => $form['slug'],
            'submitUrl' => url('/form-dinamis/entri') . '?' . http_build_query(['id' => $form['slug']]),
            'formToken' => FormToken::issue($form['slug']),
            'formVersion' => (int) $form['current_version_id'],
            'prefill' => [],
            'editing' => null,
            'clientState' => DashboardData::clientState($data),
            'iconNames' => DashboardData::iconNames($data, self::ICONS),
            'pageScripts' => ['js/dashboard-store.js', 'js/question-ui.js', 'js/form-dinamis.js'],
        ], 'main');
    }

    /** The recap page (app/Views/form-dinamis/rekap-form.php). */
    private function recap(array $form): void
    {
        $data = $this->dashboardData();
        if ($data === null) {
            return;
        }
        $card = current(array_filter($data['cards'], static fn (array $c): bool => $c['id'] === $form['slug'])) ?: [];
        $urls = $this->urls($form);
        $this->render('form-dinamis/rekap-form', [
            'pageTitle' => 'Rekap ' . $form['title'],
            'portalPage' => true,
            'backToDashboard' => true,
            'title' => (string) $form['title'],
            'card' => $card,
            'dataUrl' => url('/form-dinamis/respons/data') . '?' . http_build_query(['id' => $form['slug']]),
            'exportUrl' => url('/form-dinamis/respons/ekspor') . '?' . http_build_query(['id' => $form['slug']]),
            'signature' => ReportSignature::fromJson($form['report_signature_json'] ?? null),
            'signatureUrl' => url('/form-dinamis/tanda-tangan') . '?' . http_build_query(['id' => $form['slug']]),
            'detailUrl' => url('/form-dinamis/entri'),
            'formUrl' => $urls['fill'],
            'iconNames' => DashboardData::iconNames($data, self::ICONS),
            'pageScripts' => ['js/dashboard-store.js', 'js/rekap.js'],
        ], 'main');
    }

    /** Answers with the uploaded files stored (as attachments of the entry) and added to their question. */
    private function withFiles(PDO $pdo, int $entryId, array $answers, array $uploads): array
    {
        $attachments = new Attachment($pdo);
        foreach ($uploads as $questionId => $list) {
            foreach ($list as $upload) {
                $id = $attachments->storeUpload($upload['tmp'], $upload['name'], $upload['ext'], $upload['mime'], self::OWNER, $entryId, (string) $questionId);
                $answers[$questionId][] = ['id' => $id, 'name' => $upload['name']];
            }
        }
        return $answers;
    }

    /** Columns of form_entries for these answers. */
    private function stored(array $items, array $answers): array
    {
        return [
            'answers_json' => json_encode((object) $answers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'search_text' => FormAnswers::searchText($items, $answers),
            'attention' => FormAnswers::attention($items, $answers) ? 1 : 0,
        ];
    }

    /** Files per question of the request: <id> (one) or <id>[] (several), each as one $_FILES entry. */
    private function uploadedFiles(array $items): array
    {
        $files = [];
        foreach ($items as $q) {
            $raw = $_FILES[$q['id']] ?? null;
            if ($q['type'] !== 'file' || !is_array($raw) || !isset($raw['name'])) {
                continue;
            }
            if (is_array($raw['name'])) {
                foreach (array_keys($raw['name']) as $i) {
                    if (!is_array($raw['name'][$i])) {
                        $files[$q['id']][] = ['name' => $raw['name'][$i], 'type' => $raw['type'][$i] ?? '', 'tmp_name' => $raw['tmp_name'][$i] ?? '', 'error' => $raw['error'][$i] ?? UPLOAD_ERR_NO_FILE, 'size' => $raw['size'][$i] ?? 0];
                    }
                }
            } else {
                $files[$q['id']][] = $raw;
            }
        }
        return $files;
    }

    /** Fill-in and recap addresses of a form. */
    private function urls(array $form): array
    {
        return [
            'fill' => url('/form-dinamis?id=' . rawurlencode($form['slug'])),
            'rekap' => url('/form-dinamis/respons?id=' . rawurlencode($form['slug'])),
        ];
    }

    private function definition(?string $json): array
    {
        $definition = json_decode((string) $json, true);
        return is_array($definition) ? $definition : ['questions' => []];
    }

    /** ?id=<slug> of a form, or null. */
    private function formFromQuery(): ?array
    {
        $slug = (string) ($_GET['id'] ?? '');
        if (preg_match('/^[a-z0-9-]{1,64}$/', $slug) !== 1) {
            return null;
        }
        $form = $this->model()->formBySlug($slug);
        return $form;
    }

    private function formOrRedirect(): ?array
    {
        if (preg_match('/^[a-z0-9-]{1,64}$/', (string) ($_GET['id'] ?? '')) !== 1) {
            redirect('/');
            return null;
        }
        $form = $this->formFromQuery();
        if ($form === null) {
            $this->notFound();
        }
        return $form;
    }

    private function invalid(array $errors): void
    {
        json_response(['message' => 'Periksa kembali isian yang ditandai.', 'errors' => (object) $errors, 'links' => (object) []], 422);
    }

    /** A request larger than post_max_size arrives without fields or files (PHP drops them). */
    private function tooLarge(): bool
    {
        $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $max = FileUpload::iniBytes((string) ini_get('post_max_size'));
        if ($_POST === [] && $_FILES === [] && $max > 0 && $length > $max) {
            json_response(['message' => 'Ukuran kiriman melebihi batas server (' . FileUpload::megabytes($max) . ').'], 413);
            return true;
        }
        return false;
    }

    private function entries(): FormEntries
    {
        return new FormEntries(Database::connection());
    }

    private function model(): Dashboard
    {
        return new Dashboard(Database::connection());
    }

    private function notFound(): void
    {
        http_response_code(404);
        View::render('errors/design-404', ['pageTitle' => 'Halaman tidak ditemukan'], 'design');
    }
}
