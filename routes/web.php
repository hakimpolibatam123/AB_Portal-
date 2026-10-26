<?php
declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\AuthController;
use App\Controllers\CctvController;
use App\Controllers\ClearDeskController;
use App\Controllers\HomeController;
use App\Controllers\SuhuController;

$home = new HomeController();
$auth = new AuthController();
$account = new AccountController();
$suhu = new SuhuController();
$cctv = new CctvController();
$clearDesk = new ClearDeskController();

$router->get('/', [$home, 'index']);
$router->get('/login', [$auth, 'showLogin']);
$router->get('/password', [$account, 'password']);
$router->get('/users', [$account, 'users']);

$router->get('/form/suhu', [$suhu, 'form']);
$router->get('/rekap/suhu', [$suhu, 'rekap']);
$router->post('/rekap/suhu', [$suhu, 'submit']);

$router->get('/form/cctv', [$cctv, 'form']);
$router->get('/rekap/cctv', [$cctv, 'rekap']);
$router->post('/rekap/cctv', [$cctv, 'submit']);

$router->get('/form/cleardesk', [$clearDesk, 'form']);
$router->get('/rekap/cleardesk', [$clearDesk, 'rekap']);
$router->post('/rekap/cleardesk', [$clearDesk, 'submit']);
