<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database;
use App\Core\SecurityHeaders;
use App\Core\View;
use App\Helpers\Format;
use App\Models\Attachment;
use App\Models\ClearanceRepository;
use App\Services\Clearance;
use App\Services\ClearanceExcel;
use App\Services\ClearancePdf;
use App\Services\FileUpload;
use RuntimeException;
use Throwable;

/**
 * Form Clearance of leaving employees (side menu): GET /clearance lists them, GET /clearance/isi[?id=] fills in the
 * employee's data and the Unit IT part (drawn by public/assets/clearance/clearance.js), POST /clearance/simpan stores
 * it, POST /clearance/hapus removes one, POST /clearance/berkas takes the Ka. Unit IT signature image, GET /clearance/pdf and /clearance/excel export the
 * recap of every employee recorded (ClearancePdf, ClearanceExcel).
 */
final class ClearanceController extends Controller
{
    private const FILE_OWNER = 'clearance';
    private const FILE_BYTES = 5 * 1024 * 1024;

    public function index(): void
    {
        $list = array_map(static fn (array $r): array => [
            'id' => $r['id'], 'name' => $r['name'], 'nik' => $r['nik'] ?? '', 'position' => $r['position'] ?? '', 'resignDate' => $r['resignDate'] ?? '',
            'updatedAt' => $r['updatedAt'], 'updatedBy' => $r['updatedBy'] ?? '', 'progress' => Clearance::progress($r),
            // For the "lihat" window of the list: the Unit IT part and who made it.
            'it' => $r['it'] ?? [], 'employeeSign' => $r['employeeSign'] ?? null, 'createdAt' => $r['createdAt'] ?? '', 'createdBy' => $r['createdBy'] ?? '',
        ], (new ClearanceRepository(Database::connection()))->all());
        $this->page('Form Clearance', ['mode' => 'list', 'list' => $list]);
    }

    public function form(): void
    {
        $id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
        $record = $id !== '' ? (new ClearanceRepository(Database::connection()))->find($id) : null;
        if ($id !== '' && $record === null) {
            $this->notFound();
            return;
        }
        $this->page($record !== null ? 'Clearance ' . $record['name'] : 'Form Clearance Baru', ['mode' => 'form', 'record' => $record]);
    }

    /** POST /clearance/simpan {clearance: {...}}: the new or edited record, or 422 with the broken rule. */
    public function save(): void
    {
        $input = json_decode((string) file_get_contents('php://input'), true);
        $in = is_array($input) && is_array($input['clearance'] ?? null) ? $input['clearance'] : null;
        if ($in === null) {
            json_response(['message' => 'Tidak ada isian yang dikirim.'], 422);
            return;
        }
        $user = Auth::user();
        $by = mb_substr(trim((string) ($user['name'] ?? '')) ?: (string) ($user['username'] ?? 'Admin'), 0, 150);
        $pdo = Database::connection();
        $repository = new ClearanceRepository($pdo);
        try {
            $pdo->beginTransaction();
            $id = is_string($in['id'] ?? null) ? $in['id'] : '';
            $existing = $id !== '' ? $repository->find($id) : null;
            if ($id !== '' && $existing === null) {
                throw new RuntimeException('Clearance ' . $id . ' tidak ditemukan.');
            }
            $now = gmdate('Y-m-d\TH:i:s.000\Z');
            if ($existing === null) {
                $id = Clearance::nextId($repository->ids(), gmdate('Y'));
            }
            $record = Clearance::normalize($in, $existing, $id, $by, $now, $this->signatureFiles($in));
            $repository->save($record);
            $pdo->commit();
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            json_response(['message' => $exception->getMessage()], 422);
            return;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Clearance: ' . $exception->getMessage());
            json_response(['message' => 'Clearance tidak tersimpan. Coba lagi.'], 500);
            return;
        }
        Audit::tryLog($existing === null ? 'create' : 'update', 'clearance', null,
            ['text' => 'Clearance ' . $record['id'] . ' (' . $record['name'] . ') ' . ($existing === null ? 'dibuat' : 'diperbarui')]);
        json_response(['clearance' => $record, 'progress' => Clearance::progress($record)]);
    }

    /** POST /clearance/hapus {id}: removes one clearance (the change log keeps who removed which, with its data). */
    public function delete(): void
    {
        $input = json_decode((string) file_get_contents('php://input'), true);
        $id = is_array($input) && is_string($input['id'] ?? null) ? $input['id'] : '';
        $repository = new ClearanceRepository(Database::connection());
        $record = $id !== '' ? $repository->find($id) : null;
        if ($record === null) {
            json_response(['message' => 'Clearance ' . $id . ' tidak ditemukan.'], 404);
            return;
        }
        try {
            $repository->delete($id);
        } catch (Throwable $exception) {
            error_log('Hapus clearance: ' . $exception->getMessage());
            json_response(['message' => 'Clearance tidak terhapus. Coba lagi.'], 500);
            return;
        }
        Audit::tryLog('delete', 'clearance', null, ['text' => 'Clearance ' . $id . ' (' . $record['name'] . ') dihapus', 'record' => $record]);
        json_response(['deleted' => $id]);
    }

    /** POST /clearance/berkas (multipart: file): one signature image, PNG or JPG up to 5 MB. */
    public function upload(): void
    {
        $file = is_array($_FILES['file'] ?? null) && !is_array($_FILES['file']['name'] ?? null) ? $_FILES['file'] : [];
        $check = FileUpload::check($file, ['jpg', 'jpeg', 'png'], self::FILE_BYTES);
        if (isset($check['empty']) || isset($check['error'])) {
            json_response(['message' => $check['error'] ?? 'Pilih gambar tanda tangan PNG atau JPG.'], 422);
            return;
        }
        try {
            $files = new Attachment(Database::connection());
            $id = $files->storeUpload($file['tmp_name'], (string) $file['name'], $check['ext'], $check['mime'], self::FILE_OWNER, 0, 'ttd');
            $stored = $files->find($id);
        } catch (Throwable $exception) {
            error_log('Tanda tangan clearance: ' . $exception->getMessage());
            json_response(['message' => 'Tanda tangan tidak tersimpan. Coba lagi.'], 500);
            return;
        }
        json_response(['file' => ['id' => $id, 'name' => $stored['original_name'] ?? '']]);
    }

    /** GET /clearance/pdf: the recap of every employee recorded, as a PDF download. */
    public function pdf(): void
    {
        $pdo = Database::connection();
        // The filters of the list (?q, dari, sampai, status) choose which employees are in the recap.
        $records = Clearance::filter((new ClearanceRepository($pdo))->all(), $_GET);
        $filter = Clearance::filterText($_GET);
        $files = new Attachment($pdo);
        $pdf = ClearancePdf::render($records, static function (?array $file) use ($files): ?string {
            $found = $file !== null ? $files->find((int) ($file['id'] ?? 0)) : null;
            return $found !== null && $found['owner_type'] === self::FILE_OWNER ? Attachment::path($found) : null;
        }, Format::dateTime(gmdate('Y-m-d H:i:s')), $filter);
        Audit::tryLog('export', 'clearance', null, ['text' => 'Rekap clearance PDF (' . count($records) . ' karyawan; ' . $filter . ') diunduh']);
        $this->download($pdf, 'application/pdf', 'pdf');
    }

    /** GET /clearance/excel: the same recap as an Excel workbook (ClearanceExcel). */
    public function excel(): void
    {
        $records = Clearance::filter((new ClearanceRepository(Database::connection()))->all(), $_GET);
        $filter = Clearance::filterText($_GET);
        $dir = BASE_PATH . '/storage/app/tmp';
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        $path = $dir . '/' . bin2hex(random_bytes(8)) . '.xlsx';
        try {
            ClearanceExcel::write($records, $path, Format::dateTime(gmdate('Y-m-d H:i:s')), $filter);
            $body = (string) file_get_contents($path);
        } catch (Throwable $exception) {
            error_log('Ekspor Excel clearance: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Ekspor Excel gagal dibuat. Coba lagi.';
            return;
        } finally {
            @unlink($path);
        }
        Audit::tryLog('export', 'clearance', null, ['text' => 'Rekap clearance Excel (' . count($records) . ' karyawan; ' . $filter . ') diunduh']);
        $this->download($body, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xlsx');
    }

    private function download(string $body, string $type, string $extension): void
    {
        header_remove('Content-Security-Policy');
        header('Content-Type: ' . $type);
        header('Content-Length: ' . strlen($body));
        header('Content-Disposition: attachment; filename="Rekap-Clearance-' . date('Y-m-d') . '.' . $extension . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        echo $body;
    }

    /** The signature files the record refers to (Ka. Unit IT, employee), when they are clearance signatures: id => original name. */
    private function signatureFiles(array $in): array
    {
        $files = new Attachment(Database::connection());
        $out = [];
        foreach ([(int) ($in['it']['sign']['file']['id'] ?? 0), (int) ($in['employeeSign']['file']['id'] ?? 0)] as $id) {
            $found = $id > 0 ? $files->find($id) : null;
            if ($found !== null && $found['owner_type'] === self::FILE_OWNER && $found['field'] === 'ttd') {
                $out[$id] = (string) $found['original_name'];
            }
        }
        return $out;
    }

    private function page(string $title, array $data): void
    {
        // The form draws markup with style attributes and shows signatures drawn on a canvas (blob:/data:).
        header('Content-Security-Policy: ' . strtr(SecurityHeaders::CSP, [
            "style-src 'self'" => "style-src 'self' 'unsafe-inline'",
            "img-src 'self' https: data:" => "img-src 'self' https: data: blob:",
        ]));
        $user = Auth::user();
        $this->render('clearance/page', [
            'pageTitle' => $title,
            'clearance' => $data + ['definition' => Clearance::definition()],
            'portalPage' => true,
            'backToDashboard' => true,
            'backHref' => $data['mode'] === 'list' ? url('/') : url('/clearance'),
            'backLabel' => $data['mode'] === 'list' ? 'Kembali ke Dashboard' : 'Kembali ke Daftar Clearance',
            'breadcrumbWidth' => $data['mode'] === 'list' ? '' : 'breadcrumb-fill',
            'bodyClass' => 'aset-shell',
            'pageStyles' => ['aset/aset-theme.css', 'aset/aset.css'],
            'pageScripts' => ['aset/icons.js', 'aset/aset-shim.js', 'aset/aset-store.js', 'aset/aset-ui.js', 'aset/aset-gf.js', 'aset/aset-sign.js', 'clearance/clearance.js'],
            'bodyData' => ['data-user-name' => trim((string) ($user['name'] ?? '')) ?: 'Admin', 'data-clearance-api' => url_base() . '/clearance'],
        ], 'main');
    }

    private function notFound(): void
    {
        http_response_code(404);
        View::render('errors/design-404', ['pageTitle' => 'Clearance tidak ditemukan'], 'design');
    }
}
