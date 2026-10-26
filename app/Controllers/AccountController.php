<?php
declare(strict_types=1);

namespace App\Controllers;

final class AccountController extends Controller
{
    public function password(): void
    {
        $this->render('account/password', $this->shell('password', 'Password'));
    }

    public function users(): void
    {
        $this->render('account/users', $this->shell('users', 'Users') + [
            'users' => [config('dashboard')['profile'] + ['note' => 'Akun yang sedang login', 'status' => 'Online']],
        ]);
    }

    private function shell(string $page, string $title): array
    {
        return [
            'pageTitle' => $title,
            'page' => $page,
            'navActive' => $page,
            'brandSubtitle' => 'IT Division',
            'bodyClass' => 'rs-content-box',
            'crumbbar' => [
                'class' => 'flex items-center',
                'items' => [['label' => 'Portal Monitoring Terpadu', 'href' => url('/')], ['label' => $title]],
            ],
            'footer' => ['class' => 'text-center px-4 py-6'],
            'profile' => config('dashboard')['profile'],
        ];
    }
}
