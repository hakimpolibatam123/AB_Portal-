<?php
declare(strict_types=1);

namespace App\Controllers;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->render('auth/login', [
            'pageTitle' => 'Sign In',
            'redirectTo' => url('/'),
        ], 'auth');
    }
}
