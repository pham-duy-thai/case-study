<?php

function ensureUserSchema(PDO $conn): void
{
    $conn->exec(
        "CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $conn->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_id INT NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            avatar VARCHAR(255) DEFAULT '',
            status TINYINT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES roles(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $existingRoles = (int) $conn->query("SELECT COUNT(*) FROM roles")->fetchColumn();
    if ($existingRoles === 0) {
        $stmt = $conn->prepare("INSERT INTO roles (name) VALUES (:name)");
        foreach (['admin', 'user', 'staff'] as $roleName) {
            $stmt->execute(['name' => $roleName]);
        }
    }

    $adminEmail = 'admin@gmail.com';
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $adminEmail]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $adminRoleId = (int) $conn->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();
        $passwordHash = password_hash('Admin@123', PASSWORD_DEFAULT);
        $insertAdmin = $conn->prepare(
            "INSERT INTO users (role_id, full_name, email, password, status)
             VALUES (:role_id, :full_name, :email, :password, 1)"
        );
        $insertAdmin->execute([
            'role_id' => $adminRoleId,
            'full_name' => 'Quản trị viên',
            'email' => $adminEmail,
            'password' => $passwordHash,
        ]);
    }
}

function columnExists(PDO $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE :column");
    $stmt->execute(['column' => $column]);
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function ensurePostsSchema(PDO $conn): void
{
    ensureUserSchema($conn);

    $conn->exec(
        "CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            area VARCHAR(255) DEFAULT '',
            address VARCHAR(255) DEFAULT '',
            price BIGINT DEFAULT 0,
            size DECIMAL(10,2) DEFAULT 0,
            excerpt TEXT,
            content TEXT,
            status VARCHAR(50) DEFAULT 'draft',
            is_hidden TINYINT DEFAULT 0,
            views INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $columns = [
        'status' => "VARCHAR(50) DEFAULT 'draft'",
        'is_hidden' => "TINYINT DEFAULT 0",
        'excerpt' => "TEXT NULL",
        'content' => "TEXT NULL",
        'views' => "INT DEFAULT 0",
    ];

    foreach ($columns as $column => $definition) {
        if (!columnExists($conn, 'posts', $column)) {
            $conn->exec("ALTER TABLE `posts` ADD COLUMN `$column` $definition");
        }
    }
}

function getAllRoles(PDO $conn): array
{
    $stmt = $conn->query("SELECT id, name FROM roles ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function getAllUsersWithRoles(PDO $conn): array
{
    $sql = "
        SELECT users.*, roles.name AS role_name
        FROM users
        JOIN roles ON roles.id = users.role_id
        ORDER BY users.created_at DESC
    ";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function getActiveUsers(PDO $conn): array
{
    $sql = "
        SELECT id, full_name, email
        FROM users
        WHERE status = 1
        ORDER BY full_name ASC
    ";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function generateRandomPassword(int $length = 10): string
{
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#$!';
    $maxIndex = strlen($characters) - 1;
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, $maxIndex)];
    }
    return $password;
}

function getAllPostsWithAuthor(PDO $conn): array
{
    $sql = "
        SELECT posts.*, users.full_name AS author_name, users.email AS author_email
        FROM posts
        JOIN users ON users.id = posts.user_id
        ORDER BY posts.updated_at DESC
    ";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
