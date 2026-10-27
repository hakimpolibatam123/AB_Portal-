<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];
    /** @var list<callable(string, string): bool> */
    private array $middleware = [];

    /** A public route skips the middleware (e.g. the login page). */
    public function get(string $path, callable $handler, bool $public = false): void
    {
        $this->routes['GET'][$path] = ['handler' => $handler, 'public' => $public];
    }

    public function post(string $path, callable $handler, bool $public = false): void
    {
        $this->routes['POST'][$path] = ['handler' => $handler, 'public' => $public];
    }

    /** Middleware runs before every non-public route; returning false stops the request. */
    public function middleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function dispatch(string $method, string $path): void
    {
        // HEAD is answered like GET; the server discards the body.
        $method = $method === 'HEAD' ? 'GET' : $method;
        [$route, $params] = $this->match($method, $path);
        if (!($route['public'] ?? false)) {
            foreach ($this->middleware as $middleware) {
                if ($middleware($method, $path) === false) {
                    return;
                }
            }
        }
        if ($route === null) {
            http_response_code(404);
            View::render('errors/design-404', ['pageTitle' => 'Halaman tidak ditemukan'], 'design');
            return;
        }
        ($route['handler'])($params);
    }

    /**
     * Exact paths first, then patterns such as /dashboard/cards/{id}/hide. A {name} segment matches
     * letters, digits, "-" and "_"; {id} matches a positive number only.
     * @return array{0: ?array, 1: array<string, string>}
     */
    private function match(string $method, string $path): array
    {
        if (isset($this->routes[$method][$path])) {
            return [$this->routes[$method][$path], []];
        }
        foreach ($this->routes[$method] ?? [] as $pattern => $route) {
            if (!str_contains($pattern, '{')) {
                continue;
            }
            $regex = preg_replace_callback(
                '/\\\\\{(\w+)\\\\\}/',
                static fn (array $m): string => '(?P<' . $m[1] . '>' . ($m[1] === 'id' ? '[1-9][0-9]{0,18}' : '[A-Za-z0-9_-]{1,64}') . ')',
                preg_quote($pattern, '#')
            );
            if (preg_match('#^' . $regex . '$#', $path, $found) === 1) {
                return [$route, array_filter($found, 'is_string', ARRAY_FILTER_USE_KEY)];
            }
        }
        return [null, []];
    }
}
