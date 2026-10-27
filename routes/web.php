<?php
declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\AuthController;
use App\Controllers\CctvController;
use App\Controllers\ClearDeskController;
use App\Controllers\DashboardController;
use App\Controllers\AntrianOnlineController;
use App\Controllers\HardwareServerController;
use App\Controllers\KesesuaianUserController;
use App\Controllers\HomeController;
use App\Controllers\SuhuController;
use App\Core\RequireLogin;

$home = new HomeController();
$auth = new AuthController();
$account = new AccountController();
$suhu = new SuhuController();
$cctv = new CctvController();
$clearDesk = new ClearDeskController();
$hardwareServer = new HardwareServerController();
$kesesuaianUser = new KesesuaianUserController();
$antrianOnline = new AntrianOnlineController();
$dashboard = new DashboardController();

// Every route below needs a signed-in session unless it is marked public.
$router->middleware(new RequireLogin());

$router->get('/login', [$auth, 'showLogin'], public: true);
$router->post('/login', [$auth, 'login'], public: true);
$router->post('/logout', [$auth, 'logout']);

$router->get('/', [$home, 'index']);
$router->get('/password', [$account, 'password']);
$router->get('/users', [$account, 'users']);

// Dashboard categories and cards (every POST checks the CSRF token).
$router->post('/dashboard/sections', [$dashboard, 'storeSection']);
$router->post('/dashboard/sections/{id}/delete', [$dashboard, 'deleteSection']);
$router->post('/dashboard/links', [$dashboard, 'storeLink']);
$router->post('/dashboard/links/{id}', [$dashboard, 'updateLink']);
$router->post('/dashboard/cards/{id}/hide', [$dashboard, 'hideCard']);
$router->post('/dashboard/cards/{id}/show', [$dashboard, 'showCard']);
$router->post('/dashboard/cards/{id}/move', [$dashboard, 'moveCard']);
$router->post('/dashboard/cards/{id}/delete', [$dashboard, 'deleteCard']);
$router->get('/files/{id}', [$dashboard, 'file']);

$router->get('/form/suhu', [$suhu, 'form']);
$router->get('/form/temperature', static fn () => redirect('/form/suhu', 302));
$router->get('/rekap/suhu', [$suhu, 'rekap']);
$router->post('/rekap/suhu', [$suhu, 'submit']);

$router->get('/form/cctv', [$cctv, 'form']);
$router->get('/rekap/cctv', [$cctv, 'rekap']);
$router->post('/rekap/cctv', [$cctv, 'submit']);

$router->get('/form/cleardesk', [$clearDesk, 'form']);
$router->get('/rekap/cleardesk', [$clearDesk, 'rekap']);
$router->post('/rekap/cleardesk', [$clearDesk, 'submit']);

$router->get('/forms/hardware-server', [$hardwareServer, 'form']);
$router->get('/reports/hardware-server', [$hardwareServer, 'rekap']);
$router->get('/forms/kesesuaian-user', [$kesesuaianUser, 'form']);
$router->get('/reports/kesesuaian-user', [$kesesuaianUser, 'rekap']);
$router->get('/forms/antrian-online', [$antrianOnline, 'form']);
$router->get('/reports/antrian-online', [$antrianOnline, 'rekap']);
