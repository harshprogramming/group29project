<?php

class Router
{
    private array $routes = [];

    public function get(string $path, ...$handlers): void
    {
        $this->addRoute('GET', $path, $handlers);
    }

    public function post(string $path, ...$handlers): void
    {
        $this->addRoute('POST', $path, $handlers);
    }

    public function put(string $path, ...$handlers): void
    {
        $this->addRoute('PUT', $path, $handlers);
    }

    public function delete(string $path, ...$handlers): void
    {
        $this->addRoute('DELETE', $path, $handlers);
    }

    private function addRoute(string $method, string $path, array $handlers): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handlers' => $handlers
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace('#\{[^/]+\}#', '([^/]+)', $route['path']);
            $pattern = '#^' . rtrim($pattern, '/') . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                foreach ($route['handlers'] as $index => $handler) {
                    if ($index < count($route['handlers']) - 1) {
                        call_user_func($handler);
                    } else {
                        call_user_func_array($handler, $matches);
                    }
                }
                return;
            }
        }

        Response::error('Route not found', 404);
    }
}