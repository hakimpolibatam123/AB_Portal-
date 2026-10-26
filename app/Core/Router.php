<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        // HEAD is answered like GET; the server discards the body.
        $handler = $this->routes[$method === 'HEAD' ? 'GET' : $method][$path] ?? null;
        if ($handler === null) {
            http_response_code(404);
            View::render('errors/404', ['pageTitle' => 'Halaman tidak ditemukan']);
            return;
        }
        $handler();
    }
}
