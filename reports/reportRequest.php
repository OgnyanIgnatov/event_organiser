<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['id'])) die("Not logged in");

$user_id = (int)$_SESSION['id'];
$role = $_SESSION['role'];
$errors = [];
$success = '';

if($role === 'client'){
    $res = $connection->query("SELECT id, username FROM users WHERE role='organiser' AND is_active=1");
    $backLink = "../client/dashboard.php";
}
else{
    $res = $connection->query("SELECT id, username FROM users WHERE role = 'client' AND is_active=1");
    $backLink = "../organiser/dashboard.php";
}

$users = $res->fetch_all(MYSQLI_ASSOC);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $reported_id = (int)$_POST['reported_id'];
    $reason = trim($_POST['reason']);

    if($reported_id <= 0){
        $errors[] = 'Select a user.';
    }
    if($reason === ''){
        $errors[] = 'Reason is required';
    }

    if(!$errors){
        $check = $connection->prepare("
            SELECT 1 FROM blacklists
            WHERE reporter_id = ? AND reported_id = ?
        ");
        $check->bind_param('ii', $user_id, $reported_id);
        $check->execute();
        $check->store_result();

        if($check->num_rows > 0){
            $errors[] = "You already reported this user.";
        }
        $check->close();
    }

    if(!$errors){
        $stmt = $connection->prepare("
            INSERT INTO blacklists (reporter_id, reported_id, reason)
            VALUES (?,?,?)
        ");
        $stmt->bind_param('iis', $user_id, $reported_id, $reason);

        if($stmt->execute()){
            $success = "User reported successfully.";
        }
        else{
            $errors[] = "Database error.";
        }
        $stmt->close();
    }

    if(!$errors){
        $cntStmt = $connection->prepare("
            SELECT COUNT(*) AS cnt
            FROM blacklists
            WHERE reported_id = ?
        ");
        $cntStmt->bind_param('i', $reported_id);
        $cntStmt->execute();
        
        $cntResult = $cntStmt->get_result()->fetch_assoc();
        $cntStmt->close();

        $cnt = (int)($cntResult['cnt'] ?? 0);

        if($cnt >= 4){
            $stmt = $connection->prepare("
                UPDATE users
                SET is_active = 0
                WHERE id = ? AND role != 'admin'
            ");
            $stmt->bind_param('i', $reported_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}   
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report User</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Report a User</h2>
        <p><a href="<?php echo htmlspecialchars($backLink); ?>">← Back</a></p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <label>Select user</label>
            <select name="reported_id" required>
                <option value="">Select</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?php echo (int)$u['id']; ?>">
                        <?php echo htmlspecialchars($u['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Reason</label>
            <input name="reason" required>
            <button type="submit">Report</button>
        </form>
    </div>
</div>
</body>
</html>