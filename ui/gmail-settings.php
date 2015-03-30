<?php require_once __DIR__ . "/login-check.php"; ?>
<?php
use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\Service;


$db = new Database;

// Check for a Gmail OAuth Token for the current user
$auth = Helper\Registry::getAuth();
$user = $auth->getLoggedUser();

// fetch user's profile if we have access token
if ($user['gmail_access_token'] != null) {
    // get gmail user profile
    $userRepo = new Model\UserRepository($db);
    $authProvider = new Helper\GoogleAuthProvider(
        $appConfig['services']['google'],
        $userRepo
    );

    $googleAuth = $authProvider->getAuth($user['username']);
    $profile = Service\Google\Gmail::getProfile($googleAuth->client);

    $accountName   = $profile['emailAddress'];
    $totalMessages = $profile['messagesTotal'];
} else {
    // we don't have google access token, request it
    $request = new Auth\Google\UserAuthRequest($user['id'], Helper\Registry::getRouter()->getUrl('gmail_settings'));
    $manager = new Auth\Google($appConfig['services']['google']);
    // send request
    $manager->requestAuth($request);
    exit;
}
?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>
<!-- Page content -->
<div id="page-content">
    <!-- Wizard Header -->
    <div class="content-header">
        <div class="row">
            <div class="col-sm-6">
                <div class="header-section">
                    <h1>Gmail Settings</h1>
                </div>
            </div>
            <div class="col-sm-6 hidden-xs">
                <div class="header-section">
                    <ul class="breadcrumb breadcrumb-top">
                        <li>Access2.ME</li>
                        <li>User Settings</li>
                        <li><a href="<?php echo htmlentities(Helper\Template::getUrl('gmail_settings')); ?>">Gmail Settings</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!-- END Wizard Header -->

    <!-- Wizards Content -->
    <!-- Form Wizards are initialized in js/pages/formsWizard.js -->
    <div class="row">
        <div class="col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
            <!-- Progress Bar Wizard Block -->
            <div class="block">
                <!-- Progress Bars Wizard Title -->
                <div class="block-title">
                    <h2>Configure Your Gmail Settings</h2>
                </div>
                <!-- END Progress Bar Wizard Title -->

                <!-- Progress Wizard Content -->
                <form id="progress-wizard" action="page_forms_wizard.php" method="post" class="form-horizontal form-bordered">
                    <!-- First Step -->
                    <div id="progress-first" class="step">
                        <div class="form-group">
                            <label class="col-md-4 control-label" for="example-progress-username">Account</label>
                            <div class="col-md-6">
                                <?php if (!empty($accountName)) { echo $accountName; } ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label" for="example-progress-email">Total Messages</label>
                            <div class="col-md-6">
                                <?php if (!empty($totalMessages)) { echo $totalMessages; } ?>
                            </div>
                        </div>
                    </div>
                    <!-- END First Step -->
                </form>
                <!-- END Progress Bar Wizard Content -->
            </div>
            <!-- END Progress Bar Wizard Block -->
        </div>
    </div>
    <!-- END Wizards Content -->
</div>
<!-- END Page Content -->

<?php include 'inc/page_footer.php'; ?>
<?php include 'inc/template_scripts.php'; ?>

<!-- Load and execute javascript code used only in this page -->
<script src="js/pages/formsWizard.js"></script>
<script>$(function(){ FormsWizard.init(); });</script>

<?php include 'inc/template_end.php'; ?>