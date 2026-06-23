<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AuditLog;
use App\Repositories\BookRepository;
use App\Validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class BookController
{
    public function __construct(
        private BookRepository $books,
        private AuditLog       $audit
    ) {}

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $rows   = $this->books->all(
            (string)($params['q']     ?? ''),
            (int)($params['limit'] ?? 0)
        );

        return $this->json($response, ['count' => count($rows), 'data' => $rows]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id   = (int)($args['id'] ?? 0);
        $book = $this->books->find($id);

        return $book
            ? $this->json($response, $book)
            : $this->json($response, ['error' => "Book {$id} not found"], 404);
    }

    public function create(Request $request, Response $response): Response
    {
        $auth = (array)$request->getAttribute('auth', []);
        $body = (array)$request->getParsedBody();

        $errors = (new Validator())
            ->required('title', 'author', 'year')
            ->field('title',  Validator::nonEmptyString(200), 'title must be 1-200 chars')
            ->field('author', Validator::nonEmptyString(150), 'author must be 1-150 chars')
            ->field('year',   Validator::intRange(1000, (int)date('Y')), 'year must be 1000..now')
            ->field('genre',  Validator::nonEmptyString(80),  'genre must be ≤ 80 chars')
            ->validate($body);

        if ($errors) {
            return $this->json($response, ['errors' => $errors], 400);
        }

        $createdBy = (int)($auth['sub'] ?? 0);
        $id        = $this->books->create($body, $createdBy);

        $ip = (string)($request->getServerParams()['REMOTE_ADDR'] ?? '');
        $this->audit->record('book.create', $createdBy, "book:{$id}", $ip);

        return $this->json($response, [
            'message' => 'Book created',
            'data'    => $this->books->find($id),
        ], 201)->withHeader('Location', '/api/books/' . $id);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id   = (int)($args['id'] ?? 0);
        $book = $this->books->find($id);

        if (!$book) {
            return $this->json($response, ['error' => "Book {$id} not found"], 404);
        }

        // IDOR check
        $auth    = (array)$request->getAttribute('auth', []);
        $isOwner = (int)$book['created_by'] === (int)($auth['sub'] ?? 0);
        $isAdmin = ($auth['role'] ?? 'member') === 'admin';

        if (!$isOwner && !$isAdmin) {
            $ip = (string)($request->getServerParams()['REMOTE_ADDR'] ?? '');
            $this->audit->record('book.idor_denied', (int)($auth['sub'] ?? 0), "book:{$id}", $ip);
            return $this->json($response, ['error' => 'Forbidden'], 403);
        }

        $body = (array)$request->getParsedBody();

        $errors = (new Validator())
            ->field('title',  Validator::nonEmptyString(200), 'title must be 1-200 chars')
            ->field('author', Validator::nonEmptyString(150), 'author must be 1-150 chars')
            ->field('year',   Validator::intRange(1000, (int)date('Y')), 'year must be 1000..now')
            ->field('genre',  Validator::nonEmptyString(80),  'genre must be ≤ 80 chars')
            ->validate($body, true); // partial = true

        if ($errors) {
            return $this->json($response, ['errors' => $errors], 400);
        }

        $this->books->update($id, $body);

        $actorId = (int)($auth['sub'] ?? 0);
        $ip      = (string)($request->getServerParams()['REMOTE_ADDR'] ?? '');
        $this->audit->record('book.update', $actorId, "book:{$id}", $ip);

        return $this->json($response, [
            'message' => 'Book updated',
            'data'    => $this->books->find($id),
        ]);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $auth = (array)$request->getAttribute('auth', []);

        if (($auth['role'] ?? 'member') !== 'admin') {
            return $this->json($response, ['error' => 'Admins only'], 403);
        }

        $id   = (int)($args['id'] ?? 0);
        $book = $this->books->find($id);

        if (!$book) {
            return $this->json($response, ['error' => "Book {$id} not found"], 404);
        }

        $this->books->delete($id);

        $actorId = (int)($auth['sub'] ?? 0);
        $ip      = (string)($request->getServerParams()['REMOTE_ADDR'] ?? '');
        $this->audit->record('book.delete', $actorId, "book:{$id}", $ip);

        return $this->json($response, ['message' => 'Book deleted', 'data' => $book]);
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
