<?php
require_once 'db.php';

try {
    $username = 'admin';
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        $updateStmt = $pdo->prepare("UPDATE users SET password = :password, role = 'admin' WHERE id = :id");
        $updateStmt->execute([
            'password' => $hashed_password,
            'id' => $admin['id']
        ]);
        echo "Admin user updated successfully. Password reset to 'admin123' and role set to 'admin'.\n";
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES (:username, :password, 'Administrator', 'admin')");
        $insertStmt->execute([
            'username' => $username,
            'password' => $hashed_password
        ]);
        echo "Admin user created successfully with password 'admin123' and role 'admin'.\n";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
