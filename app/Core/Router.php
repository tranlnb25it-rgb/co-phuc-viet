<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, regex:string, names:string[], handler:array, middleware:callable[]}> */
    private array $routes = [];

    public function get(string $p, array $h, array $mw = []): void    { $this->add('GET', $p, $h, $mw); }
    public function post(string $p, array $h, array $mw = []): void   { $this->add('POST', $p, $h, $mw); }
    public function put(string $p, array $h, array $mw = []): void    { $this->add('PUT', $p, $h, $mw); }
    public function patch(string $p, array $h, array $mw = []): void  { $this->add('PATCH', $p, $h, $mw); }
    public function delete(string $p, array $h, array $mw = []): void { $this->add('DELETE', $p, $h, $mw); }

    private function add(string $method, string $pattern, array $handler, array $middleware): void
    {
        preg_match_all('/\{(\w+)\}/', $pattern, $m);
        $regex = '#^' . preg_replace('/\{\w+\}/', '([^/]+)', $pattern) . '$#';
        $this->routes[] = ['method' => $method, 'regex' => $regex, 'names' => $m[1],
                           'handler' => $handler, 'middleware' => $middleware];
    }

    public function dispatch(Request $req): void
    {
        $pathMatched = false;
        $method = $req->method === 'HEAD' ? 'GET' : $req->method;   // HEAD is a GET without a body
        foreach ($this->routes as $r) {
            if (!preg_match($r['regex'], $req->path, $m)) continue;
            $pathMatched = true;
            if ($r['method'] !== $method) continue;

            $req->params = array_combine($r['names'], array_map('urldecode', array_slice($m, 1))) ?: [];
            foreach ($r['middleware'] as $mw) $mw($req);

            [$class, $method] = $r['handler'];
            (new $class())->$method($req);
            return;
        }
        throw $pathMatched ? new HttpException(405, 'Phương thức không được hỗ trợ')
                           : HttpException::notFound('Không tìm thấy endpoint');
    }
}
