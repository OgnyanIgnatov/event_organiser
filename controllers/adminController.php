<?php
require_once '../config/db.php';

function getUserById(int $user_id) {
    global $connection;
    $stmt = $connection->prepare("SELECT id, username, email, role, is_active FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res ?: null;
}

function addUser(string $username, string $email, string $role, string $password) {
    global $connection;
    $stmt = $connection->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        return "Username or email already exists";
    }
    $stmt->close();

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $connection->prepare("INSERT INTO users (username,email,password,role) VALUES (?,?,?,?)");
    $stmt->bind_param('ssss', $username, $email, $hashed, $role);
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $stmt->close();
        return "Database error";
    }
}

function editUser(int $user_id, string $username, string $email, string $role, ?string $password = null) {
    global $connection;

    if ($password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $connection->prepare("UPDATE users SET username=?, email=?, role=?, password=? WHERE id=?");
        $stmt->bind_param('ssssi', $username, $email, $role, $hashed, $user_id);
    } else {
        $stmt = $connection->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
        $stmt->bind_param('sssi', $username, $email, $role, $user_id);
    }

    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $stmt->close();
        return "Database error";
    }
}

function deleteUser(int $user_id) {
    global $connection;
    $stmt = $connection->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $stmt->close();
        return "Database error";
    }
}

function getAllUsers(): array {
    global $connection;
    $res = $connection->query("SELECT id, username, email, role, is_active FROM users ORDER BY id DESC");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function getUsersByRole(string $role): array {
    global $connection;
    $stmt = $connection->prepare("SELECT id, username, email, role, is_active FROM users WHERE role=? ORDER BY username");
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $res = $stmt->get_result();
    $users = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $users;
}

function getAllRequests(): array {
    global $connection;
    $res = $connection->query("
        SELECT er.*, c.username AS client_name, o.username AS organiser_name
        FROM event_requests er
        JOIN users c ON c.id = er.client_id
        JOIN users o ON o.id = er.organiser_id
        ORDER BY er.created_at DESC
    ");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function getRequestById(int $request_id) {
    global $connection;
    $stmt = $connection->prepare("
        SELECT er.*, c.username AS client_name, c.email AS client_email, 
               o.username AS organiser_name, o.email AS organiser_email
        FROM event_requests er
        JOIN users c ON c.id = er.client_id
        JOIN users o ON o.id = er.organiser_id
        WHERE er.id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res ?: null;
}


function deleteRequest(int $request_id) {
    global $connection;
    $stmt = $connection->prepare("DELETE FROM event_requests WHERE id=?");
    $stmt->bind_param('i', $request_id);
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $stmt->close();
        return "Database error";
    }
}