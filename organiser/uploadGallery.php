<?php
session_start();
require_once '../config/db.php';
require_once '../middlewares/requireOrganiser.php';

$request_id = (int)($_GET['request_id'] ?? 0);
if (!$request_id) die("Invalid request");

$errors = [];
$success = "";

$stmt = $connection->prepare("SELECT status FROM event_requests WHERE id=? AND organiser_id=? LIMIT 1");
$stmt->bind_param('ii', $request_id, $_SESSION['id']);
$stmt->execute();
$requestData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$requestData) die("You cannot upload for this request");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $file = $_FILES['image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = date('YmdHis') . '_' . uniqid() . '.' . $ext;
    $dest = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $stmt = $connection->prepare("INSERT INTO galleries (request_id, image_path, is_public) VALUES (?,?,?)");
        $public = isset($_POST['is_public']) ? 1 : 0;
        $stmt->bind_param("isi", $request_id, $filename, $public);
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

$stmt = $connection->prepare("SELECT * FROM galleries WHERE request_id=? ORDER BY created_at ASC");
$stmt->bind_param("i", $request_id);
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
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Event Gallery</h1>
        <p><a href="dashboard.php" class="button">Back to Dashboard</a></p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
            </div>
        <?php endif; ?>

        <?php if ($images): ?>
            <h2>Uploaded Images</h2>
            <div class="gallery">
                <?php foreach ($images as $img): ?>
                    <div>
                        <img src="../uploads/<?= htmlspecialchars($img['image_path']) ?>" alt="Event Image">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No images uploaded yet.</p>
        <?php endif; ?>

        <?php if (in_array($requestData['status'], ['accepted_by_organiser','accepted_by_client','declined_by_client'])): ?>
            <form method="post" enctype="multipart/form-data" style="margin-top: 1em;">
                <div class="form-group">
                    <label>Select Image:</label>
                    <input type="file" name="image" accept="image/*" required>
                </div>
                <button type="submit" class="button">Upload</button>
            </form>
        <?php else: ?>
            <p>Gallery is available for viewing.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>