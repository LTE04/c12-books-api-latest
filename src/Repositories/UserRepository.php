<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role, created_at, updated_at FROM users WHERE email = :email'
        );
        $stmt->execute([':email' => mb_strtolower(trim($email))]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, role, created_at, updated_at FROM users WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(string $name, string $email, string $hash, string $role = 'member'): int
    {
        $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, :role)'
        )->execute([
            ':name' => trim($name),
            ':email' => mb_strtolower(trim($email)),
            ':hash' => $hash,
            ':role' => $role,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = :email');
        $stmt->execute([':email' => mb_strtolower(trim($email))]);

        return (bool)$stmt->fetchColumn();
    }
}
