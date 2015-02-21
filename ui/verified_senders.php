<?php require_once __DIR__ . "/login-check.php"; ?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>
<?php ob_start(); ?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>
<?php $htmlHeader = ob_get_clean(); ?>

<?php
use Access2Me\Helper;
use Access2Me\Model;

$db = new Database;
$auth = new Helper\Auth($db);
$userId = $auth->getLoggedUser()['id'];

$type = isset($_GET['type']) ? $_GET['type'] : null;

// redirect to the home page if invalid type requested
if ($type != 'unverified' && $type != 'verified') {
    header('Location: ' . $appConfig['siteUrl'] . '/ui');
    exit;
}

$sendersRepo = new Model\SenderRepository($db);

if ($type == 'unverified') {
    $senders = $sendersRepo->findUnverifiedByUser($userId);
    $title = 'Unverified senders';
} else {
    $senders = $sendersRepo->findVerifiedByUser($userId);
    $title = 'Verified senders';
}

echo $htmlHeader;
?>

<div id="page-content">
    <div class="block">
        <!-- Table Styles Title -->
        <div class="block-title clearfix">
            <h1><?php echo $title; ?></h1>
        </div>

        <div class="table-responsive">
            <table id="general-table" class="table table-striped table-bordered table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Sender</th>
                    </tr>
                </thead>
                <tbody id="entries-holder">
                <?php foreach ($senders as $sender): ?>
                    <tr>
                        <td><?php echo htmlentities($sender['email']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'inc/page_footer.php'; ?>
<?php include 'inc/template_scripts.php'; ?>

<!-- Load and execute javascript code used only in this page -->
<script src="js/vendor/jsrender.min.js"></script>
<script src="js/vendor/lodash.min.js"></script>
<script src="js/pages/formsWizard.js"></script>
<script>$(function(){ FormsWizard.init(); });</script>

<?php include 'inc/template_end.php'; ?>