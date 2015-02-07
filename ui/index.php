<?php require_once __DIR__ . "/login-check.php"; ?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>

<?php
use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\Service;

class IndexController
{
    public function process()
    {
         if (isset($_GET['sender']) && isset($_GET['messageId'])) {
            $this->addSenderAction($_GET['sender'], $_GET['messageId']);
            return true;
        }

        return false;
    }

    public function addSenderAction($action, $messageId)
    {
        $db = new Database();
        $mesgRepo = new Model\MessageRepository($db);
        
        if ($action != 'allow' && $action != 'deny') {
            Helper\FlashMessage::add('Unknown action', Helper\FlashMessage::ERROR);
            return;
        }

        // get message
        $message = $mesgRepo->getById($messageId);
        if (!$message) {
            Helper\FlashMessage::add('Message not exists', Helper\FlashMessage::ERROR);
            return;
        }

        // get authenticated user
        $auth = new Helper\Auth($db);
        $user = $auth->getLoggedUser();

        // validate email
        $email = $message['from_email'];
        if (!Helper\Utils::isValidEmail($email)) {
            $msg = sprintf(
                'Can\'t %s invalid email: %s',
                $action == 'allow' ? 'whitelist' : 'blacklist',
                htmlentities($email)
            );
            Helper\FlashMessage::add($msg, Helper\FlashMessage::ERROR);
            return;
        }

        $access = $action == 'allow' ? Model\UserSenderRepository::ACCESS_ALLOWED : Model\UserSenderRepository::ACCESS_DENIED;

        // add email
        if ($this->addEmailToUserList($email, $access, $user, $db)) {
            $msg = 'Sender <b>' . htmlentities($email) . '</b> ' . ($action == 'allow' ? 'whitelisted' : 'blacklisted');
            Helper\FlashMessage::add($msg, Helper\FlashMessage::SUCCESS);
        }
    }

    private function addEmailToUserList($email, $access, $user, $db)
    {
        $userListRepo = new Model\UserSenderRepository($db);

        // find existing entry
        $entry = $userListRepo->getByUserAndSender($user['id'], $email);
        if (!$entry) {
            $entry = [
                'user_id' => $user['id'],
                'sender' => $email,
                'type' => Model\UserSenderRepository::TYPE_EMAIL
            ];
        }

        $entry['access'] = $access;

        // save list entry
        $userListRepo->save($entry);

        return true;
    }
}


$db = new Database;
$auth = new Helper\Auth($db);
$user = $auth->getLoggedUser();
    
$mesgRepo = new Model\MessageRepository($db);

// process userlist actions
$ctrl = new IndexController();
$ctrl->process();

$messages = $mesgRepo->findByUser($user['id'], 10);

$senderRepo = new Model\SenderRepository($db);
foreach ($messages AS &$message) {
    $senders = $senderRepo->findByMessageId($message['id']);

    if (!$senders) {
        continue;
    }

    $profProv = Helper\Registry::getProfileProvider();
    $profiles = $profProv->getProfiles($senders);

    foreach ($profiles as $sid=>$profile) {
        if ($sid == Service\Service::LINKEDIN) {
            if (!empty($profile->profileUrl)) {
                $message['profileUrl']['linkedin'] = $profile->profileUrl;
            }
        } else if ($sid == Service\Service::FACEBOOK) {
            if (!empty($profile->profileUrl)) {
                $message['profileUrl']['facebook'] = $profile->profileUrl;
            }
        } else if ($sid == Service\Service::TWITTER) {
            if (!empty($profile->profileUrl)) {
                $message['profileUrl']['twitter'] = $profile->profileUrl;
            }
        }
    }
}

$userStats = Helper\Registry::getUserStats($user);

?>

<!-- Page content -->
<div id="page-content">
    <?php include('inc/page_status_icons.php'); ?>

    <?php echo Helper\FlashMessage::toHTML(); ?>

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
                        <th>Created at</th>
                        <th style="width: 320px;">Profiles</th>
                        <th style="width: 120px;" class="text-center"><i class="fa fa-flash"></i></th>
                    </tr>
                </thead>
                <tbody>
<?php

if (!empty($messages) && is_array($messages)) {
    foreach ($messages as &$message) {
        $createdAt = Helper\DateTime::fromUTCtoDefault($message['created_at']);
        $profileUrl = $appConfig['siteUrl'] . '/ui/sender_profile.php?'
                . 'email=' . urlencode($message['from_email']);
        ?>
        <tr>
            <td class="text-center"><label class="csscheckbox csscheckbox-primary"><input
                        type="checkbox"><span></span></label></td>
            <td><strong><?php echo htmlentities($message['from_name']); ?></strong></td>
            <td><a href="<?php echo htmlentities($profileUrl); ?>"><?php echo htmlentities($message['from_email']); ?></a></td>
            <td><?php echo $createdAt->format($appConfig['dateTimeFormat']); ?></td>
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
                <a href="<?php echo htmlentities('index.php?sender=allow&messageId='. $message['id']); ?>" data-toggle="tooltip" title="Whitelist User"
                   class="btn btn-effect-ripple btn-sm btn-success"><i class="fa fa-check"></i></a>
                <a href="<?php echo htmlentities('index.php?sender=deny&messageId='. $message['id']); ?>" data-toggle="tooltip" title="Block User"
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