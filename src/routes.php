<?php

declare(strict_types=1);

use App\Auth\JwtService;
use App\Controllers\AuthController;
use App\Controllers\BookController;
use App\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RateLimit;
use App\Repositories\AuditLog;
use App\Repositories\BookRepository;
use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app): void {

    $pdo   = Database::get();
    $jwt   = new JwtService();
    $audit = new AuditLog($pdo);

    $bookCtrl = new BookController(new BookRepository($pdo), $audit);
    $authCtrl = new AuthController(new UserRepository($pdo), $jwt, $audit);
    $auth     = new AuthMiddleware($jwt);

    // Rate limiter for login: 5 attempts per 60-second window (from .env)
    $loginMw = new RateLimit(
        (int)($_ENV['LOGIN_RATE_LIMIT']      ?? 5),
        (int)($_ENV['LOGIN_WINDOW_SECONDS']  ?? 60),
        'login'
    );

    // Public
    $app->get('/', function (Request $r, Response $s) {
        $s->getBody()->write(json_encode([
            'name'    => 'Books REST API',
            'version' => '4.0.0 (Ch12 hardened)',
            'endpoints' => [
                'public' => [
                    'POST /auth/register',
                    'POST /auth/login',
                    'GET  /api/books',
                    'GET  /api/books/{id}',
                ],
                'protected' => [
                    'GET    /auth/me',
                    'POST   /api/books',
                    'PUT    /api/books/{id}   (owner or admin)',
                    'DELETE /api/books/{id}   (admin only)',
                ],
            ],
        ]));
        return $s->withHeader('Content-Type', 'application/json');
    });

    // Auth routes
    $app->post('/auth/register', [$authCtrl, 'register']);
    $app->post('/auth/login',    [$authCtrl, 'login'])->add($loginMw);   // ← rate-limited

    $app->get('/auth/me', [$authCtrl, 'me'])->add($auth);

    // Book routes — reads public, writes protected
    $app->get('/api/books',      [$bookCtrl, 'index']);
    $app->get('/api/books/{id}', [$bookCtrl, 'show']);

    $app->group('/api/books', function ($g) use ($bookCtrl) {
        $g->post  ('',      [$bookCtrl, 'create']);
        $g->put   ('/{id}', [$bookCtrl, 'update']);
        $g->delete('/{id}', [$bookCtrl, 'delete']);
    })->add($auth);

    // CORS pre-flight catch-all (must NOT go through AuthMiddleware)
    $app->options('/{routes:.+}', fn(Request $r, Response $s) => $s);
};
