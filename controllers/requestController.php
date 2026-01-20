<?php
require_once __DIR__ . '/../config/db.php';

function getRequestById(int $id) {
    global $connection;
    $stmt = $connection->prepare("SELECT * FROM event_requests WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res ?: null;
}

function updateRequestByClient(
    int $request_id,
    int $client_id,
    string $event_type,
    string $requested_date,
    int $participants
) {
    global $connection;

    $stmt = $connection->prepare("
        UPDATE event_requests
        SET event_type=?, requested_date=?, participants=?, status='pending'
        WHERE id=? AND client_id=? 
          AND status IN ('pending','rejected_by_organiser','needs_correction')
    ");
    $stmt->bind_param("ssiii",
        $event_type, $requested_date, $participants,
        $request_id, $client_id
    );
    $ok = $stmt->execute() && $stmt->affected_rows === 1;
    $stmt->close();

    return $ok ? true : "Request cannot be edited.";
}

function updateRequestByOrganiser(
    int $request_id,
    int $organiser_id,
    string $event_type,
    string $requested_date,
    int $participants
) {
    global $connection;

    $stmt = $connection->prepare("
        UPDATE event_requests
        SET event_type=?, requested_date=?, participants=?
        WHERE id=? AND organiser_id=? 
          AND status IN ('accepted_by_organiser','declined_by_client')
    ");
    $stmt->bind_param("ssiii",
        $event_type, $requested_date, $participants,
        $request_id, $organiser_id
    );
    $ok = $stmt->execute() && $stmt->affected_rows === 1;
    $stmt->close();

    return $ok ? true : "Request cannot be edited.";
}
