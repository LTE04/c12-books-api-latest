<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\JwtService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private JwtService $jwt)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $this->fail('Missing or malformed token');
        }

        try {
            $payload = $this->jwt->verify($matches[1]);
        } catch (\Throwable $e) {
            error_log('[Auth] ' . $e->getMessage());
            return $this->fail('Invalid or expired token');
        }

        return $handler->handle($request->withAttribute('auth', $payload));
    }

    private function fail(string $message): ResponseInterface
    {
        $response = new SlimResponse(401);
        $response->getBody()->write(json_encode(['error' => $message]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('WWW-Authenticate', 'Bearer');
    }
}
