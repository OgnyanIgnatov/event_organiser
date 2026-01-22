<?php
session_start();
require_once '../middlewares/requireOrganiser.php';
require_once '../controllers/organiserController.php';

$organiser_id = (int)$_SESSION['id'];
$errors = [];
$success = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $request_id=(int)($_POST['request_id']??0);
    $action=$_POST['action']??'';
    $note=trim($_POST['organiser_note']??'');
    $result=updateRequestStatus($request_id,$organiser_id,$action,$note);
    if($result===true){header("Location: dashboard.php?success=1");exit;}else{$errors[]=$result;}
}

if(isset($_GET['success'])) $success="Request updated successfully";

$pendingRequests = getPendingRequests($organiser_id);
$correctionRequests = getCorrectionRequests($organiser_id);
$completedRequests = getCompletedRequests($organiser_id);

$eventTypeLabels = [
    'private_party'=>'Private Party',
    'corporate_party'=>'Corporate Party',
    'team_building'=>'Team Building',
    'birthday'=>'Birthday',
    'other'=>'Other'
];

function readableStatus(string $status):string{
    return match($status){
        'pending'=>'Pending',
        'rejected_by_client'=>'Rejected by Client',
        'accepted_by_client'=>'Accepted by Client',
        'needs_correction'=>'Needs Correction',
        'accepted_by_organiser'=>'Accepted by Organiser',
        'rejected_by_organiser'=>'Rejected by Organiser',
        default=>ucfirst(str_replace('_',' ',$status))
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Organiser Dashboard</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Organiser Dashboard</h1>
        <p>Logged in as <strong><?=htmlspecialchars($_SESSION['username'])?></strong> |
        <a href="../index.php">Home</a> |
        <a href="../profile/editProfile.php">Edit Profile</a> |
        <a href="../reports/reportRequest.php">Report Client</a> |
        <a href="../auth/login.php?logout=1">Logout</a></p>
        <?php if($success):?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif;?>
        <?php if($errors):?><div class="alert alert-error"><ul><?php foreach($errors as $e):?><li><?=htmlspecialchars($e)?></li><?php endforeach;?></ul></div><?php endif;?>
    </div>
    <div class="card">
        <h2>Accepted By Organiser / Declined by Client Requests</h2>
        <?php if(!$pendingRequests):?><p>No requests.</p>
        <?php else: ?>
        <table>
            <tr>
                <th>ID</th><th>Client</th><th>Email</th><th>Type</th><th>Date</th><th>Participants</th><th>Visibility</th><th>Status</th><th>Actions</th>
            </tr>
            <?php foreach($pendingRequests as $r): ?>
            <tr>
                <td><?=$r['id']?></td>
                <td><?=htmlspecialchars($r['client_name'])?></td>
                <td><?= htmlspecialchars($r['client_email']) ?></td>
                <td><?=$eventTypeLabels[$r['event_type']]??htmlspecialchars($r['event_type'])?></td>
                <td><?=$r['requested_date']?></td>
                <td><?=$r['participants']?></td>
                <td><?=$r['is_public']?'Public':'Private'?></td>
                <td><?=readableStatus($r['status'])?></td>
                <td>
                    <?php if(organiserCanEdit($r['status'])):?><a href="editRequest.php?request_id=<?=$r['id']?>" class="button">Edit</a><?php endif;?>
                </td>
            </tr>
            <?php endforeach;?>
        </table>
        <?php endif;?>
    </div>
    <div class="card">
        <h2>Pending / Needs Correction</h2>
        <?php if(!$correctionRequests):?><p>No requests.</p>
        <?php else: ?>
        <table>
            <th>ID</th><th>Client</th><th>Email</th><th>Type</th><th>Date</th><th>Participants</th><th>Visibility</th><th>Status</th><th>Actions</th><th>Note</th></tr>
            <?php foreach($correctionRequests as $r): ?>
            <tr>
                <td><?=$r['id']?></td>
                <td><?=htmlspecialchars($r['client_name'])?></td>
                <td><?= htmlspecialchars($r['client_email']) ?></td>
                <td><?=$eventTypeLabels[$r['event_type']]??htmlspecialchars($r['event_type'])?></td>
                <td><?=$r['requested_date']?></td>
                <td><?=$r['participants']?></td>
                <td><?=$r['is_public']?'Public':'Private'?></td>
                <td><?=readableStatus($r['status'])?></td>
                <td>
                    <?php if($r['status'] == 'needs_correction'):?>
                        <form method="post"><input type="hidden" name="request_id" value="<?=$r['id']?>"><input type="hidden" name="action" value="reaccept"><button>Mark Accepted</button></form><?php endif;?>

                    <?php if($r['status'] == 'pending'):?>
                        <form method="post" style="display:inline">
                                <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="accept">
                                <button>Accept</button>
                            </form>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="decline">
                                <button onclick="return confirm('Reject?')">Reject</button>
                            </form>
                            <form method="post" style="display:inline">
                                <button>Need Correction</button>
                                <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="needs_correction">
                                <input type="text" name="correction_note" placeholder="Correction note" required>
                            </form>
                    <?php endif;?>
                </td>
                <td>
                    <?php if($r['status'] == 'needs_correction'):?>
                        <?php if ($r['organiser_note']): ?>
                            <div><strong>You:</strong><br><?= nl2br(htmlspecialchars($r['organiser_note'])) ?></div>
                        <?php endif; ?>
                        <?php if ($r['correction_note']): ?>
                            <div><strong>Client:</strong><br><?= nl2br(htmlspecialchars($r['correction_note'])) ?></div>
                        <?php endif; ?>
                    <?php endif;?>
                </td>
            </tr>
            <?php endforeach;?>
        </table>
        <?php endif;?>
    </div>
    <div class="card">
    <h2>Completed Events</h2>
    <?php if(!$completedRequests): ?>
        <p>No completed events.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Type</th>
                <th>Date</th>
                <th>Participants</th>
                <th>Visibility</th>
                <th>Status</th>
                <th>Your Feedback</th>
                <th>Client Feedback</th>
                <th>Gallery</th>
                <th>Comments</th>
            </tr>

            <?php foreach($completedRequests as $r): ?>
                <?php 
                    $organiserFeedback = getOrganiserFeedback($r['id']); 
                    $clientFeedback = getClientFeedback($r['id']);
                ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= htmlspecialchars($r['client_name']) ?></td>
                    <td><?= $eventTypeLabels[$r['event_type']] ?? htmlspecialchars($r['event_type']) ?></td>
                    <td><?= $r['requested_date'] ?></td>
                    <td><?= $r['participants'] ?></td>
                    <td><?= $r['is_public'] ? 'Public' : 'Private' ?></td>
                    <td><?= readableStatus($r['status']) ?></td>
                    <td>
                        <?php if ($r['status'] === 'accepted_by_client'): ?>
                            <?php if (!$organiserFeedback): ?>
                                <a href="feedback.php?request_id=<?= $r['id'] ?>&role=organiser" class="button">Give Feedback</a>
                            <?php else: ?>
                                Rating: <?= (int)$organiserFeedback['rating'] ?><br>
                                <?= nl2br(htmlspecialchars($organiserFeedback['comment'])) ?><br>
                                <a href="feedback.php?request_id=<?= $r['id'] ?>&role=organiser" class="button">Update Feedback</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span>N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$clientFeedback): ?>
                            <span>No feedback yet</span>
                        <?php else: ?>
                            Rating: <?= (int)$clientFeedback['rating'] ?><br>
                            <?= nl2br(htmlspecialchars($clientFeedback['comment'])) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (organiserCanUploadGallery($r['status'])): ?>
                            <a href="uploadGallery.php?request_id=<?= $r['id'] ?>" class="button">View Photos</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="comments.php?request_id=<?= $r['id'] ?>" class="button">View Comments</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>


</div>
</body>
</html>