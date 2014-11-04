<?php require_once __DIR__ . "/login-check.php"; ?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>

<?php
use Access2Me\Helper;

$db = new Database;
$auth = new Helper\Auth($db);
$user = $auth->getLoggedUser();

$messageSql = "SELECT `id`, `from_email`, `from_name`, `subject` FROM `messages` WHERE `user_id` = '" . $user['id'] . "' LIMIT 10;";
$messages = $db->getArray($messageSql);

foreach ($messages AS &$message) {
    $sql = "SELECT a.`from_name`, a.`from_email`,b.`service`,b.`profile` FROM `messages` AS a LEFT JOIN `senders` AS b ON a.`from_email` = b.`sender` WHERE a.`id` = '" . $message['id'] . "'";
    $sender = $db->getArray($sql);

    foreach ($sender AS &$service) {
        if ($service['service'] == '1') {
            $linkedinProfile = unserialize($service['profile']);

            if (!empty($linkedinProfile->profileUrl)) {
                $message['profileUrl']['linkedin'] = $linkedinProfile->profileUrl;
            }
        }

        if ($service['service'] == '2') {
            $facebookProfile = unserialize($service['profile']);

            if (!empty($facebookProfile->profileUrl)) {
                $message['profileUrl']['facebook'] = $facebookProfile->profileUrl;
            }
        }

        if ($service['service'] == '3') {
            $twitterProfile = unserialize($service['profile']);

            if (!empty($twitterProfile->profileUrl)) {
                $message['profileUrl']['twitter'] = $twitterProfile->profileUrl;
            }
        }
    }
}

?>

<!-- Page content -->
<div id="page-content">
    <?php include('inc/page_status_icons.php'); ?>
    <!-- Table Styles Block -->
    <div class="block">
        <!-- Table Styles Title -->
        <div class="block-title clearfix">
            <!-- Changing classes functionality initialized in js/pages/tablesGeneral.js -->
            <h2><span class="hidden-xs">Most</span> Recent Messages</h2>
        </div>
        <!-- END Table Styles Title -->

        <!-- Table Styles Content -->
        <div class="table-responsive">
            <!--
            Available Table Classes:
                'table'             - basic table
                'table-bordered'    - table with full borders
                'table-borderless'  - table with no borders
                'table-striped'     - striped table
                'table-condensed'   - table with smaller top and bottom cell padding
                'table-hover'       - rows highlighted on mouse hover
                'table-vcenter'     - middle align content vertically
            -->
            <table id="general-table" class="table table-striped table-bordered table-vcenter table-hover">
                <thead>
                    <tr>
                        <th style="width: 80px;" class="text-center"><label class="csscheckbox csscheckbox-primary"><input type="checkbox"><span></span></label></th>
                        <th>Name</th>
                        <th>Email</th>
                        <th style="width: 320px;">Profiles</th>
                        <th style="width: 120px;" class="text-center"><i class="fa fa-flash"></i></th>
                    </tr>
                </thead>
                <tbody>
<?php

if (!empty($messages) && is_array($messages)) {
    foreach ($messages as &$message) {
        ?>
        <tr>
            <td class="text-center"><label class="csscheckbox csscheckbox-primary"><input
                        type="checkbox"><span></span></label></td>
            <td><strong><?php echo $message['from_name']; ?></strong></td>
            <td><?php echo $message['from_email']; ?></td>
            <td>
                <?php if (!empty($message['profileUrl']['linkedin'])) {
                    echo '<a href="' . $message['profileUrl']['linkedin'] . '" target="_blank">';
                    echo '<img src="http://app.access2.me/images/16-linkedin.png" border="0">';
                    echo '</a>&nbsp;';
                } ?>
                <?php if (!empty($message['profileUrl']['facebook'])) {
                    echo '<a href="' . $message['profileUrl']['facebook'] . '" target="_blank">';
                    echo '<img src="http://app.access2.me/images/16-facebook.png" border="0">';
                    echo '</a>&nbsp;';
                } ?>
                <?php if (!empty($message['profileUrl']['twitter'])) {
                    echo '<a href="' . $message['profileUrl']['twitter'] . '" target="_blank">';
                    echo '<img src="http://app.access2.me/images/16-twitter.png" border="0">';
                    echo '</a>&nbsp';
                } ?>
            </td>
            <td class="text-center">
                <a href="javascript:void(0)" data-toggle="tooltip" title="Whitelist User"
                   class="btn btn-effect-ripple btn-sm btn-success"><i class="fa fa-check"></i></a>
                <a href="javascript:void(0)" data-toggle="tooltip" title="Block User"
                   class="btn btn-effect-ripple btn-sm btn-danger"><i class="fa fa-times"></i></a>
            </td>
        </tr>
    <?php
    }
}

?>
                </tbody>
            </table>
        </div>
        <!-- END Table Styles Content -->
    </div>
    <!-- END Table Styles Block -->

</div>
<!-- END Page Content -->

<?php include 'inc/page_footer.php'; ?>
<?php include 'inc/template_scripts.php'; ?>

<!-- Load and execute javascript code used only in this page -->
<script src="js/pages/readyDashboard.js"></script>
<script>$(function(){ ReadyDashboard.init(); });</script>

<?php include 'inc/template_end.php'; ?>