<?php
require_once  '../config/db.php';

function getClientRequests(int $client_id): array {
    global $connection;
    $stmt = $connection->prepare("
        SELECT er.id, er.event_type, er.requested_date, er.participants, er.status, er.created_at, er.correction_note, er.organiser_note,
               u.username AS organiser_name
        FROM event_requests er 
        JOIN users u ON u.id = er.organiser_id 
        WHERE er.client_id=? 
        ORDER BY er.created_at DESC
    ");
    $stmt->bind_param('i', $client_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $res;
}

function getActiveOrganisers(): array {
    global $connection;
    $organisers = [];
    $res = $connection->query("SELECT id, username FROM users WHERE role='organiser' AND is_active=1 ORDER BY username");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $organisers[] = $row;
        }
    }
    return $organisers;
}

function validateRequest(int $organiser_id, string $event_type, string $requested_date, int $participants): array {
    $errors = [];
    $allowed_types = ['private_party', 'corporate party', 'team_building', 'birthday', 'other'];

    if ($organiser_id <= 0) $errors[] = "Select an organiser";
    if (!in_array($event_type, $allowed_types, true)) $errors[] = "Select a valid event";

    if (!$requested_date) {
        $errors[] = "Select a date";
    } elseif ($requested_date < date("Y-m-d")) {
        $errors[] = "Date cannot be in the past";
    }

    if ($participants < 1 || $participants > 500) $errors[] = "Participants must be between 1 and 500";

    return $errors;
}

function createRequest(int $client_id, int $organiser_id, string $event_type, string $requested_date, int $participants) {
    global $connection;

    $stmt = $connection->prepare("
        INSERT INTO event_requests (client_id, organiser_id, event_type, requested_date, participants)
        VALUES (?,?,?,?,?)
    ");
    $stmt->bind_param("iissi", $client_id, $organiser_id, $event_type, $requested_date, $participants);
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    }
    $stmt->close();
    return "Database error";
}

function updateRequest(int $request_id, int $client_id, int $organiser_id, string $event_type, string $requested_date, int $participants): bool|string {
    global $connection;
    $stmt = $connection->prepare("
        UPDATE event_requests
        SET organiser_id=?, event_type=?, requested_date=?, participants=?
        WHERE id=? AND client_id=? AND status='pending'
    ");
    $stmt->bind_param("issiii", $organiser_id, $event_type, $requested_date, $participants, $request_id, $client_id);
    if ($stmt->execute()) { $stmt->close(); return true; }
    $stmt->close();
    return "Database error or cannot edit this request";
}

function updateRequestStatusClient(int $request_id, int $client_id, string $action, string $note = '') {
    global $connection;

    $newStatus = null;
    if ($action === 'accept') $newStatus = "accepted_by_client";
    if ($action === 'decline') $newStatus = "declined_by_client";
    if ($action === 'needs_correction') $newStatus = "needs_correction";

    if (!$newStatus) return 'Invalid action';

    if ($newStatus === "needs_correction" && empty($note)) {
        return "Please write what should be corrected";
    }

    if ($newStatus === 'needs_correction') {
        $stmt = $connection->prepare("
            UPDATE event_requests
            SET status = ?, correction_note = ?
            WHERE id = ? AND client_id = ? 
            AND status IN ('accepted_by_organiser, 'needs_correction')
        ");
        $stmt->bind_param('ssii', $newStatus, $note, $request_id, $client_id);
    } else {
        $stmt = $connection->prepare("
            UPDATE event_requests
            SET status = ?
            WHERE id = ? AND client_id = ? 
            AND status IN ('accepted_by_organiser, 'needs_correction')
        ");
        $stmt->bind_param('sii', $newStatus, $request_id, $client_id);
    }

    $stmt->execute();
    if ($stmt->affected_rows === 1) {
        $stmt->close();
        return true;
    } else {
        $stmt->close();
        return "Request not found or invalid action";
    }
}

function submitFeedback(int $request_id, int $rating, string $comment = ''): bool|string {
    global $connection;
    $stmt = $connection->prepare("
        INSERT INTO feedbacks (request_id, rating, comment)
        VALUES (?,?,?)
    ");
    $stmt->bind_param("iis", $request_id, $rating, $comment);
    if ($stmt->execute()) { $stmt->close(); return true; }
    $stmt->close();
    return "Database error";
}