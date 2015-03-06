<?php require_once __DIR__ . "/login-check.php"; ?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>

<?php
use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\Service;

class IndexController
{
    protected $appConfig;
    protected $user;

    public function __construct($appConfig, $db, $user)
    {
        $this->appConfig = $appConfig;
        $this->db = $db;
        $this->user = $user;
    }

    public function process()
    {
         if (isset($_GET['sender']) && isset($_GET['messageId'])) {
            $this->addSenderAction($_GET['sender'], $_GET['messageId']);
            return true;
         } elseif (isset($_GET['linkto'])) {
             $this->linkTo($_GET['linkto']);
             return true;
         } elseif (isset($_GET['unlink'])) {
             $this->unlinkFrom($_GET['unlink']);
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

    /**
     *  Link user to external service (linkedin, twitter, etc.)
     *
     * @param string $type
     */
    public function linkTo($type)
    {
        if ($type == 'linkedin') {
            $router = new Helper\Router($this->appConfig);
            $request = new Service\Auth\Linkedin\UserAuthRequest($this->user['id'], $router->getUrl('home'));
            $manager = new Service\Auth\Linkedin($this->appConfig['services']['linkedin']);
            // send request
            $manager->requestAuth($request);
            exit;
        }
    }

    /**
     * Unlink user from certain service
     * @param string $type
     */
    public function unlinkFrom($type)
    {
        if ($type == 'linkedin') {
            $this->user['linkedin_access_token'] = null;
            $userRepo = new Model\UserRepository($this->db);
            $userRepo->save($this->user);
            Helper\FlashMessage::add('You have successfully unlinked LinkedIn account', Helper\FlashMessage::SUCCESS);
            // redirect back to this page
            Helper\Http::redirect(Helper\Registry::getRouter()->getUrl('home'));
        }
    }
}


$db = new Database;
$auth = new Helper\Auth($db);
$user = $auth->getLoggedUser();

$mesgRepo = new Model\MessageRepository($db);

// process userlist actions
$ctrl = new IndexController($appConfig, $db, $user);
$ctrl->process();

// prepare pages
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$count = $mesgRepo->getCountByUser($user['id']);
$pager = new Helper\Pager($count, $appConfig['paging']['items_per_page'], $page);

$messages = $mesgRepo->findByUser($user['id'], $pager->limit, $pager->offset);

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

// we need this in case permissions changed
try {
    $userStats = Helper\Registry::getUserStats($user);
    $statsData = [
        'gmail_contacts_count' => $userStats->get(Access2Me\Data\UserStats::GMAIL_CONTACTS_COUNT),
        'verified_senders_count' => $userStats->get(Access2Me\Data\UserStats::VERIFIED_SENDERS_COUNT),
        'filters_count' => $userStats->get(Access2Me\Data\UserStats::FILTERS_COUNT),
        'gmail_messages_count' => $userStats->get(Access2Me\Data\UserStats::GMAIL_MESSAGES_COUNT),
    ];
} catch (\Google_Exception $ex) {
    // clear gmail access token
    $userRepo = new Access2Me\Model\UserRepository($db);
    $u = $userRepo->getById($user['id']);
    $u['gmail_access_token'] = null;
    $userRepo->save($u);

    // request new permissions
    $url = $appConfig['projectUrl'] . '/ui/gmailoauth.php';
    header('Location: ' . $url);
    exit;
}

?>

<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>

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

if ($messages) {
    foreach ($messages as $message) {
        $createdAt = Helper\DateTime::fromUTCtoDefault($message['created_at']);
        $profileUrl = $appConfig['projectUrl'] . '/ui/sender_profile.php?'
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

        <?php if ($pager->count > 1): ?>
        <div>
            <ul class="pagination">
                <?php if (!$pager->isAtStart): ?>
                    <li><a href="?page=1">
                            <i class="fa fa-angle-double-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($pager->hasPrev): ?>
                    <li>
                        <a href="?page=<?php echo $pager->page-1; ?>">
                            <i class="fa fa-angle-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                <?php foreach ($pager->pages as $page): ?>
                    <?php if ($page == $pager->page): ?>
                        <li class="active">
                            <a href="javascript:void(0)">
                                <?php echo $page; ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="?page=<?php echo $page; ?>">
                                <?php echo $page; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($pager->hasNext): ?>
                    <li>
                        <a href="?page=<?php echo $pager->page+1; ?>">
                            <i class="fa fa-angle-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (!$pager->isAtEnd): ?>
                    <li>
                        <a href="?page=<?php echo $pager->count; ?>">
                            <i class="fa fa-angle-double-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

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