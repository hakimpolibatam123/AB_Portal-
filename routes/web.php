<?php
declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\AssetController;
use App\Controllers\AttentionController;
use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\DynamicFormController;
use App\Controllers\FileController;
use App\Controllers\FormEditorController;
use App\Controllers\HomeController;
use App\Controllers\MailSettingsController;
use App\Core\RequireLogin;
use App\Core\VerifyCsrf;

$home = new HomeController();
$dashboard = new DashboardController();
$formEditor = new FormEditorController();
$dynamicForm = new DynamicFormController();
$auth = new AuthController();
$account = new AccountController();
$audit = new AuditController();
$attention = new AttentionController();
$files = new FileController();
$asset = new AssetController();
$mailSettings = new MailSettingsController();

// Every POST (public ones included) must carry the session's CSRF token.
$router->middleware(new VerifyCsrf(), always: true);
// Every route below needs a signed-in session unless it is marked public.
$router->middleware(new RequireLogin());

$router->get('/login', [$auth, 'showLogin'], public: true);
$router->post('/login', [$auth, 'login'], public: true);
$router->post('/logout', [$auth, 'logout']);
$router->get('/password', [$account, 'password']);
$router->post('/password', [$account, 'updatePassword']);
// SMTP account of the Aset IT reminder emails (side menu "Pengaturan Email").
$router->get('/pengaturan-email', [$mailSettings, 'show']);
$router->post('/pengaturan-email', [$mailSettings, 'save']);
$router->post('/pengaturan-email/tes', [$mailSettings, 'test']);
// Change log, read only (B9, PRD SEC-12): no route changes audit_logs.
$router->get('/riwayat-aktivitas', [$audit, 'index']);
$router->get('/perlu-perhatian', [$attention, 'index']);
$router->post('/perlu-perhatian/dilihat', [$attention, 'markSeen']);

$router->get('/', [$home, 'index']);
// Dashboard JSON API (public/assets/js/dashboard.js): fetch() POSTs with the X-CSRF-Token header.
$router->post('/dashboard/sections', [$dashboard, 'createSection']);
$router->post('/dashboard/sections/{id}/delete', [$dashboard, 'deleteSection']);
$router->post('/dashboard/forms', [$dashboard, 'createForm']);
$router->post('/dashboard/links', [$dashboard, 'createLink']);
$router->post('/dashboard/icon', [$dashboard, 'uploadIcon']);
$router->post('/dashboard/cards/{id}', [$dashboard, 'updateCard']);
$router->post('/dashboard/cards/{id}/hide', [$dashboard, 'hide']);
$router->post('/dashboard/cards/{id}/show', [$dashboard, 'show']);
$router->post('/dashboard/cards/{id}/move', [$dashboard, 'move']);
$router->post('/dashboard/cards/{id}/duplicate', [$dashboard, 'duplicate']);
$router->post('/dashboard/cards/{id}/delete', [$dashboard, 'delete']);

// Edit Isi Form: the page, and saving it (JSON; questions of a dynamic form become a new version).
$router->get('/form-editor', [$formEditor, 'edit']);
$router->post('/form-editor', [$formEditor, 'save']);
$router->post('/form-editor/pratinjau', [$formEditor, 'preview']);
// Dynamic forms (B6): fill-in page, reports saved on the server, history (DynamicFormController).
$router->get('/form-dinamis', [$dynamicForm, 'fill']);
$router->post('/form-dinamis/entri', [$dynamicForm, 'store']);
$router->get('/form-dinamis/entri/{id}/ubah', [$dynamicForm, 'edit']);
$router->post('/form-dinamis/entri/{id}', [$dynamicForm, 'update']);
$router->post('/form-dinamis/entri/{id}/hapus', [$dynamicForm, 'destroy']);
$router->get('/form-dinamis/entri/{id}', [$dynamicForm, 'detail']);
$router->get('/form-dinamis/respons', [$dynamicForm, 'responses']);
$router->get('/form-dinamis/respons/data', [$dynamicForm, 'data']);
// Exports of the recap pages (B8, PRD §9): ?format=xlsx|pdf|cetak plus the filters of the page.
$router->get('/form-dinamis/respons/ekspor', [$dynamicForm, 'export']);
$router->post('/form-dinamis/tanda-tangan', [$dynamicForm, 'saveSignature']);

// Aset IT (IT Asset Management): menu, forms, recap; records as JSON and the operations of one submit.
$router->get('/aset', [$asset, 'index']);
$router->get('/aset/registrasi', [$asset, 'registrasi']);
$router->get('/aset/registrasi/template', [$asset, 'importTemplate']);
$router->post('/aset/registrasi/impor', [$asset, 'import']);
$router->get('/aset/penempatan', [$asset, 'penempatan']);
$router->get('/aset/lacak', [$asset, 'lacak']);
$router->get('/aset/servis', [$asset, 'servis']);
$router->get('/aset/disposal', [$asset, 'disposal']);
$router->get('/aset/serah-terima', [$asset, 'serahTerima']);
$router->get('/aset/serah-terima/pdf', [$asset, 'serahTerimaPdf']);
$router->get('/aset/rekap-serah-terima', [$asset, 'rekapSerahTerima']);
$router->get('/aset/rekap', [$asset, 'rekap']);
$router->get('/aset/rekap-perangkat', [$asset, 'rekapPerangkat']);
$router->get('/aset/rekap-perpindahan', [$asset, 'rekapPerpindahan']);
$router->get('/aset/rekap-servis', [$asset, 'rekapServis']);
$router->get('/aset/rekap-disposal', [$asset, 'rekapDisposal']);
$router->get('/aset/data', [$asset, 'data']);
$router->post('/aset/perintah', [$asset, 'command']);
$router->post('/aset/diagnosa', [$asset, 'diagnose']);
$router->post('/aset/berkas', [$asset, 'upload']);

// Uploaded files of the reports (only for a signed-in session).
$router->get('/berkas/{id}', [$files, 'show']);
