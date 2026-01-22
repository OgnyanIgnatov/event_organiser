<?php
require_once '../config/db.php';

function getPendingRequests(int $organiser_id): array {
    global $connection;

    $stmt = $connection->prepare("
        SELECT er.*, 
               c.username AS client_name, 
               c.email AS client_email
        FROM event_requests er
        JOIN users c ON c.id = er.client_id
        WHERE er.organiser_id = ?
          AND er.status IN ('declined_by_client', 'accepted_by_organiser')
        ORDER BY er.created_at DESC
    ");
    $stmt->bind_param("i", $organiser_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $res;
}

function getCorrectionRequests(int $organiser_id): array {
    global $connection;

    $stmt = $connection->prepare("
        SELECT er.*, 
               c.username AS client_name, 
               c.email AS client_email
        FROM event_requests er
        JOIN users c ON c.id = er.client_id
        WHERE er.organiser_id = ?
          AND er.status IN ('pending','needs_correction')
        ORDER BY er.created_at DESC
    ");
    $stmt->bind_param("i", $organiser_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $res;
}

function getCompletedRequests(int $organiser_id): array {
    global $connection;

    $stmt = $connection->prepare("
        SELECT er.*, 
               c.username AS client_name, 
               c.email AS client_email
        FROM event_requests er
        JOIN users c ON c.id = er.client_id
        WHERE er.organiser_id = ?
          AND er.status = 'accepted_by_client'
        ORDER BY er.created_at DESC
    ");
    $stmt->bind_param("i", $organiser_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $res;
}

function updateRequestStatus(int $request_id, int $organiser_id, string $action, string $organiser_note = '') {
    global $connection;
    if($action === 'accept' || $action === 'reject'){
        $map = [
                'accept'   => 'accepted_by_organiser',
                'reject'   => 'rejected_by_organiser',
        ];

        $newStatus = $map[$action];

        $stmt = $connection->prepare("
            UPDATE event_requests
            SET status = ?
            WHERE id = ?
            AND organiser_id = ?
            AND status = 'pending'
        ");
        $stmt->bind_param("sii", $map[$action], $request_id, $organiser_id);
        $stmt->execute();

        $ok = ($stmt->affected_rows === 1);
        $stmt->close();

        return $ok ? true : "Request not found or cannot update.";
    }
    

    if($action === 'needs_correction'){
        $organiser_note = trim($organiser_note);
        if($organiser_note === '') {return "Write a not for the client.";}

        $stmt = $connection->prepare("
            UPDATE event_requests
            SET status = 'needs_correction', organiser_note = ?
            WHERE id = ? AND organiser_id = ? 
            AND status IN ('pending', 'needs_correction', 'accepted_by_organiser')
        ");
        $stmt->bind_param('sii', $organiser_note, $request_id, $organiser_id);
        $stmt->execute();

        $ok = ($stmt->affected_rows === 1);
        $stmt->close();

        return $ok ? true : "Request not found or cannot mark as needs_correction.";
    }

    if($action === 'reaccept'){
        $stmt = $connection->prepare("
            UPDATE event_requests
            SET status = 'accepted_by_organiser'
            WHERE id = ? AND organiser_id = ? 
            AND status = 'needs_correction'
        ");

        $stmt->bind_param("ii", $request_id, $organiser_id);
        $stmt->execute();

        $ok = ($stmt->affected_rows === 1);
        $stmt->close();

        return $ok ? true : "Request not found or cannot reaccept";
    }

    return "Invalid action.";
}

function organiserCanEdit(string $status): bool {
    return in_array($status, [
        'accepted_by_organiser',
        'declined_by_client'
    ]);
}

function organiserCanUploadGallery(string $status): bool {
    return $status === 'accepted_by_client';
}

function submitOrganiserFeedback(int $request_id, int $rating, string $comment): bool|string {
    global $connection;
    $comment = trim($comment);
    if ($comment === '') return "Comment cannot be empty.";

    $stmt = $connection->prepare("
        INSERT INTO feedbacks (request_id, rating, comment, role)
        VALUES (?,?,?, 'organiser')
        ON DUPLICATE KEY UPDATE rating=?, comment=?
    ");
    $stmt->bind_param("issii", $request_id, $comment, $comment, $rating, $rating);
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $stmt->close();
        return "Database error while submitting feedback.";
    }
}

function getClientFeedback(int $request_id): ?array {
    global $connection;
    $stmt = $connection->prepare("SELECT rating, comment FROM feedbacks WHERE request_id=? AND role='client' LIMIT 1");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res ?: null;
}

function getOrganiserFeedback(int $request_id): ?array {
    global $connection;
    $stmt = $connection->prepare("SELECT rating, comment FROM feedbacks WHERE request_id=? AND role='organiser' LIMIT 1");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res ?: null;
}