<?php
session_start();
require_once '../config/db.php';
require_once '../middlewares/requireClient.php'; 

$request_id = (int)($_GET['request_id'] ?? 0);
if (!$request_id) die("Invalid request");

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];

$errors = [];
$success = "";

if ($user_role === 'client') {
    $stmt = $connection->prepare("SELECT id FROM event_requests WHERE id=? AND client_id=? LIMIT 1");
    $stmt->bind_param("ii", $request_id, $user_id);
} else if ($user_role === 'organiser') {
    $stmt = $connection->prepare("SELECT id, status FROM event_requests WHERE id=? AND organiser_id=? LIMIT 1");
    $stmt->bind_param("ii", $request_id, $user_id);
}
$stmt->execute();
$requestData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$requestData) die("Access denied");

if ($user_role === 'organiser' && isset($_POST['upload']) && isset($_FILES['image'])) {
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $file = $_FILES['image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = date('YmdHis') . '_' . uniqid() . '.' . $ext;
    $dest = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $stmt = $connection->prepare("INSERT INTO galleries (request_id, image_path, is_public) VALUES (?,?,?)");
        $stmt->bind_param("isi", $request_id, $filename, $is_public);
        if ($stmt->execute()) {
            $success = "Image uploaded successfully!";
        } else {
            $errors[] = "Database error while saving image.";
        }
        $stmt->close();
    } else {
        $errors[] = "Upload failed. Please try again.";
    }
}

if ($user_role === 'client') {
    $stmt = $connection->prepare("SELECT * FROM galleries WHERE request_id=? AND is_public=1 ORDER BY created_at ASC");
    $stmt->bind_param("i", $request_id);
} else {
    $stmt = $connection->prepare("SELECT * FROM galleries WHERE request_id=? ORDER BY created_at ASC");
    $stmt->bind_param("i", $request_id);
}
$stmt->execute();
$images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Event Gallery</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.gallery { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
.gallery img { width: 200px; height: auto; border-radius: 5px; border: 1px solid #ccc; }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Event Gallery</h1>
        <p><a href="dashboard.php">Back to Dashboard</a></p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
            </div>
        <?php endif; ?>

        <?php if ($user_role === 'organiser' && $requestData['status'] !== 'accepted_by_client'): ?>
        <form method="post" enctype="multipart/form-data">
            <label>Select Image:</label>
            <input type="file" name="image" accept="image/*" required>
            <label><input type="checkbox" name="is_public"> Public</label>
            <button type="submit" name="upload">Upload</button>
        </form>
        <?php elseif ($user_role === 'client'): ?>
            <?php if (!$images): ?>
                <p>No public images available yet.</p>
            <?php else: ?>
                <p>Viewing public images only.</p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($images): ?>
            <div class="gallery">
                <?php foreach ($images as $img): ?>
                    <div>
                        <img src="../uploads/<?= htmlspecialchars($img['image_path']) ?>" alt="Event Image">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>