<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class BookRepository
{
    public function __construct(private PDO $pdo) {}

    public function all(string $q = '', int $limit = 0): array
    {
        $sql  = 'SELECT * FROM books';
        $args = [];

        if ($q !== '') {
            $sql           .= ' WHERE title LIKE :q_title OR author LIKE :q_author';
            $args[':q_title']  = '%' . $q . '%';
            $args[':q_author'] = '%' . $q . '%';
        }

        $sql .= ' ORDER BY id ASC';

        if ($limit > 0) {
            $sql .= ' LIMIT ' . max(1, $limit);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM books WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @param int $createdBy  ID of the authenticated user creating the book */
    public function create(array $book, int $createdBy): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO books (title, author, year, genre, created_by)
             VALUES (:title, :author, :year, :genre, :owner)'
        );
        $stmt->execute([
            ':title'  => trim((string)$book['title']),
            ':author' => trim((string)$book['author']),
            ':year'   => (int)$book['year'],
            ':genre'  => trim((string)($book['genre'] ?? 'Uncategorised')),
            ':owner'  => $createdBy,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $book): int
    {
        $sets = [];
        $args = [':id' => $id];

        foreach (['title', 'author', 'genre'] as $field) {
            if (array_key_exists($field, $book)) {
                $sets[]       = "$field=:$field";
                $args[":$field"] = trim((string)$book[$field]);
            }
        }

        if (array_key_exists('year', $book)) {
            $sets[]       = 'year=:year';
            $args[':year'] = (int)$book['year'];
        }

        if (!$sets) {
            return 0;
        }

        $stmt = $this->pdo->prepare('UPDATE books SET ' . implode(', ', $sets) . ' WHERE id=:id');
        $stmt->execute($args);

        return $stmt->rowCount();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM books WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() === 1;
    }
}
