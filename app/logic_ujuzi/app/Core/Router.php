<?php

namespace App\Core;

/**
 * Single-file path handler: public/index.php is the only script Apache ever
 * executes (see public/.htaccess) — every route below maps a clean URL to a
 * Controller@method.
 */
class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $paramNames = [];
        $pattern = preg_replace_callback('#\{([a-zA-Z_]+)\}#', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '([^/]+)';
        }, rtrim($path, '/') ?: '/');

        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $pattern . '$#',
            'params' => $paramNames,
            'handler' => $handler,
        ];
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $path = Url::currentPath();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches)) {
                array_shift($matches);
                $args = array_combine($route['params'], array_map('urldecode', $matches));
                [$class, $action] = $route['handler'];
                try {
                    $controller = new $class();
                    call_user_func_array([$controller, $action], $args);
                } catch (\App\Models\DuplicateIdentifierException $e) {
                    // An email or phone another account already uses: back to the form, saying so.
                    flashError($e->getMessage() . ' Each email and phone number can belong to one account only.');
                    $back = (string) ($_SERVER['HTTP_REFERER'] ?? '');
                    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
                    header('Location: ' . ($back !== '' && $host !== '' && parse_url($back, PHP_URL_HOST) === parse_url('//' . $host, PHP_URL_HOST) ? $back : Url::to('/account/dashboard')));
                    exit;
                }
                return;
            }
        }

        http_response_code(404);
        View::render('lms.404');
    }
}
