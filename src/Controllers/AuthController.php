<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\JwtService;
use App\Repositories\AuditLog;
use App\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AuthController
{
    public function __construct(
        private UserRepository $users,
        private JwtService     $jwt,
        private AuditLog       $audit,
    ) {}

    public function register(Request $request, Response $response): Response
    {
        $body   = (array)$request->getParsedBody();
        $errors = [];

        if (empty($body['name']) || mb_strlen((string)$body['name']) < 2) {
            $errors['name'] = 'min 2 chars';
        }
        if (empty($body['email']) || !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'invalid email';
        }
        if (empty($body['password']) || mb_strlen((string)$body['password']) < 6) {
            $errors['password'] = 'min 6 chars';
        }

        if ($errors) {
            return $this->json($response, ['errors' => $errors], 400);
        }

        if ($this->users->emailExists((string)$body['email'])) {
            return $this->json($response, ['error' => 'Email already registered'], 409);
        }

        $id = $this->users->create(
            (string)$body['name'],
            (string)$body['email'],
            password_hash((string)$body['password'], PASSWORD_DEFAULT)
        );

        $ip = (string)($request->getServerParams()['REMOTE_ADDR'] ?? '');
        $this->audit->record('register', $id, "user:{$id}", $ip);

        return $this->json($response, [
            'message' => 'Registered',
            'user'    => $this->users->findById($id),
        ], 201);
    }

    public function login(Request $request, Response $response): Response
    {
        $body = (array)$request->getParsedBody();
        $ip   = (string)($request->getServerParams()['REMOTE_ADDR'] ?? '');
        $user = $this->users->findByEmail((string)($body['email'] ?? ''));

        if (!$user || !password_verify((string)($body['password'] ?? ''), $user['password_hash'])) {
            $actorId = $user ? (int)$user['id'] : null;
            $this->audit->record('login.fail', $actorId, null, $ip, (string)($body['email'] ?? ''));
            return $this->json($response, ['error' => 'Invalid credentials'], 401);
        }

        $publicUser = $this->users->findById((int)$user['id']);
        $token      = $this->jwt->issue((int)$user['id'], [
            'role'  => $user['role'],
            'email' => $user['email'],
        ]);

        $this->audit->record('login.success', (int)$user['id'], "user:{$user['id']}", $ip);

        return $this->json($response, [
            'token_type'   => 'Bearer',
            'expires_in'   => $this->jwt->ttl(),
            'access_token' => $token,
            'user'         => $publicUser,
        ]);
    }

    public function me(Request $request, Response $response): Response
    {
        $auth = (array)$request->getAttribute('auth', []);
        $user = $this->users->findById((int)($auth['sub'] ?? 0));

        return $user
            ? $this->json($response, $user)
            : $this->json($response, ['error' => 'Not found'], 404);
    }

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}
