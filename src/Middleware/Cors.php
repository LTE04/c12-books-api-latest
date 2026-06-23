<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;

final class Cors implements MiddlewareInterface
{
    private array $allowed;

    public function __construct()
    {
        $list          = (string)($_ENV['CORS_ALLOWED_ORIGINS'] ?? '');
        $this->allowed = array_filter(array_map('trim', explode(',', $list)));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->withCors($request, new SlimResponse(204));
        }

        return $this->withCors($request, $handler->handle($request));
    }

    private function withCors(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        $origin = $req->getHeaderLine('Origin');
        $allow  = '*';
        $creds  = false;

        if ($this->allowed && in_array($origin, $this->allowed, true)) {
            $allow = $origin;
            $creds = true;
        }

        $res = $res
            ->withHeader('Access-Control-Allow-Origin',  $allow)
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Vary', 'Origin');

        if ($creds) {
            $res = $res->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $res;
    }
}
