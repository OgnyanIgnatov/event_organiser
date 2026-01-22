<?php
require_once '../config/db.php';

function getClientRequests(int $client_id): array {
    global $connection;
    $stmt = $connection->prepare("
        SELECT er.*, u.username AS organiser_name
        FROM event_requests er
        JOIN users u ON u.id = er.organiser_id
        WHERE er.client_id = ?
        ORDER BY er.created_at DESC
    ");
    $stmt->bind_param('i', $client_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getClientPendingRequests(int $client_id): array {
    global $connection;
    $stmt = $connection->prepare("
        SELECT er.*, u.username AS organiser_name
        FROM event_requests er
        JOIN users u ON u.id = er.organiser_id
        WHERE er.client_id = ?
          AND er.status IN ('pending','rejected_by_organiser')
        ORDER BY er.created_at DESC
    ");
    $stmt->bind_param('i', $client_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getClientActionRequests(int $client_id): array {
    global $connection;
    $stmt = $connection->prepare("
        SELECT er.*, u.username AS organiser_name
        FROM event_requests er
        JOIN users u ON u.id = er.organiser_id
        WHERE er.client_id = ?
          AND er.status IN ('accepted_by_organiser','needs_correction')
        ORDER BY er.created_at DESC
    ");
    $stmt->bind_param('i', $client_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getClientCompletedRequests(int $client_id): array {
    global $connection;
    $stmt = $connection->prepare("
        SELECT er.*, u.username AS organiser_name,
               (SELECT COUNT(*) FROM feedbacks f WHERE f.request_id = er.id) AS feedback_count
        FROM event_requests er
        JOIN users u ON u.id = er.organiser_id
        WHERE er.client_id = ?
          AND er.status = 'accepted_by_client'
        ORDER BY er.created_at DESC
    ");
    $stmt->bind_param('i', $client_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getActiveOrganisers(): array {
    global $connection;
    $organisers = [];
    $res = $connection->query("
        SELECT id, username 
        FROM users 
        WHERE role='organiser' AND is_active=1 
        ORDER BY username
    ");
    while ($row = $res->fetch_assoc()) {
        $organisers[] = $row;
    }
    return $organisers;
}

function validateRequest(
    int $organiser_id,
    string $event_type,
    string $requested_date,
    int $participants,
    int $is_public
): array {
    $errors = [];
    $allowed_types = [
        'private_party',
        'corporate_party',
        'team_building',
        'birthday',
        'other'
    ];

    if ($organiser_id <= 0) $errors[] = "Select an organiser";
    if (!in_array($event_type, $allowed_types, true)) $errors[] = "Select a valid event";

    if (!$requested_date) {
        $errors[] = "Select a date";
    } elseif ($requested_date < date("Y-m-d")) {
        $errors[] = "Date cannot be in the past";
    }

    if ($participants < 1 || $participants > 500) {
        $errors[] = "Participants must be between 1 and 500";
    }

    if (!in_array($is_public, [0,1], true)) {
        $errors[] = "Invalid visibility option";
    }

    return $errors;
}

function createRequest(
    int $client_id,
    int $organiser_id,
    string $event_type,
    string $requested_date,
    int $participants,
    int $is_public = 0
) {
    global $connection;

    $stmt = $connection->prepare("
        INSERT INTO event_requests
        (client_id, organiser_id, event_type, requested_date, participants, is_public)
        VALUES (?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "iissii",
        $client_id,
        $organiser_id,
        $event_type,
        $requested_date,
        $participants,
        $is_public
    );

    return $stmt->execute() ? true : "Database error";
}

function updateRequest(
    int $request_id,
    int $client_id,
    int $organiser_id,
    string $event_type,
    string $requested_date,
    int $participants
): bool|string {
    global $connection;

    $stmt = $connection->prepare("
        UPDATE event_requests
        SET organiser_id=?, event_type=?, requested_date=?, participants=?
        WHERE id=? AND client_id=? AND status='pending'
    ");
    $stmt->bind_param(
        "issiii",
        $organiser_id,
        $event_type,
        $requested_date,
        $participants,
        $request_id,
        $client_id
    );

    return $stmt->execute() ? true : "Cannot edit this request";
}

function updateRequestStatusClient(
    int $request_id,
    int $client_id,
    string $action,
    string $note = ''
) {
    global $connection;

    $map = [
        'accept' => 'accepted_by_client',
        'decline' => 'declined_by_client',
        'needs_correction' => 'needs_correction'
    ];

    if (!isset($map[$action])) {
        return 'Invalid action';
    }

    if ($action === 'needs_correction' && trim($note) === '') {
        return 'Please write what should be corrected';
    }

    if ($action === 'needs_correction') {
        $stmt = $connection->prepare("
            UPDATE event_requests
            SET status=?, correction_note=?
            WHERE id=? AND client_id=?
              AND status IN ('accepted_by_organiser','needs_correction')
        ");
        $stmt->bind_param(
            'ssii',
            $map[$action],
            $note,
            $request_id,
            $client_id
        );
    } else {
        $stmt = $connection->prepare("
            UPDATE event_requests
            SET status=?
            WHERE id=? AND client_id=?
              AND status IN ('accepted_by_organiser','needs_correction')
        ");
        $stmt->bind_param(
            'sii',
            $map[$action],
            $request_id,
            $client_id
        );
    }

    $stmt->execute();

    return $stmt->affected_rows === 1
        ? true
        : "Request not found or invalid action";
}

function submitFeedback(
    int $request_id,
    int $rating,
    string $comment = ''
): bool|string {
    global $connection;

    $stmt = $connection->prepare("
        INSERT INTO feedbacks (request_id, rating, comment)
        VALUES (?,?,?)
    ");
    $stmt->bind_param("iis", $request_id, $rating, $comment);

    return $stmt->execute() ? true : "Database error";
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