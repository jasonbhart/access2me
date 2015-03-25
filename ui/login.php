<?php

require_once __DIR__ . "/../boot.php";

use Access2Me\Helper;
use Access2Me\Helper\AuthException;

$db = new Database;
$auth = Helper\Registry::getAuth();

if (isset($_GET['action']) && $_GET['action'] == 'logout' && $auth->isAuthenticated()) {
    $auth->logout();
    Helper\Http::redirect(Helper\Registry::getRouter()->getUrl('login'));
}

// redirect on success login
$redirectTo = null;
if (!empty($_REQUEST['redirect_to'])) {
    $redirectTo = $_REQUEST['redirect_to'];
}

if ($auth->isAuthenticated()) {
    if ($redirectTo)
        Helper\Http::redirect($redirectTo);
    else
        Helper\Http::redirect(Helper\Registry::getRouter()->getUrl('home'));
}

if ($_POST) {

    $username = isset($_POST['login-username']) ? $_POST['login-username'] : null;
    $password = isset($_POST['login-password']) ? $_POST['login-password'] : null;
    $remember = isset($_POST['login-remember-me']) ? (bool)$_POST['login-remember-me'] : false;
    
    if ($username && $password) {
        try {
            $auth->login($username, $password, $remember);

            if (empty($redirectTo)) {
                $redirectTo = Helper\Registry::getRouter()->getUrl('home');
            }

            Helper\Http::redirect($redirectTo);
        } catch (AuthException $ex) {
            $errorMessage = $ex->getMessage();
        }
    }
}

?>

<?php include 'inc/config.php'; ?>
<?php include 'inc/template_start.php'; ?>

<!-- Login Container -->
<div id="login-container">
    <!-- Login Header -->
    <h1 class="h2 text-light text-center push-top-bottom animation-slideDown">
        <i class="fa fa-cube"></i> <strong>Access2.ME</strong>
    </h1>
    <!-- END Login Header -->

    <!-- Login Block -->
    <div class="block animation-fadeInQuickInv">
        <!-- Login Title -->
        <div class="block-title">
            <div class="block-options pull-right">
                <a href="page_ready_reminder.php" class="btn btn-effect-ripple btn-primary" data-toggle="tooltip" data-placement="left" title="Forgot your password?"><i class="fa fa-exclamation-circle"></i></a>
                <a href="page_ready_register.php" class="btn btn-effect-ripple btn-primary" data-toggle="tooltip" data-placement="left" title="Create new account"><i class="fa fa-plus"></i></a>
            </div>
            <h2>Please Login</h2>
        </div>
        <!-- END Login Title -->

        <!-- Login Form -->
        <form id="form-login" action="login.php" method="post" class="form-horizontal">
            <?php if ($redirectTo): ?>
            <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirectTo); ?>" />
            <?php endif; ?>
            <div class="form-group">
                <div class="col-xs-12">
                    <input type="text" id="login-username" name="login-username" class="form-control" placeholder="Your username">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <input type="password" id="login-password" name="login-password" class="form-control" placeholder="Your password">
                </div>
            </div>
            <div class="form-group form-actions">
                <div class="col-xs-8">
                    <label class="csscheckbox csscheckbox-primary">
                        <input type="checkbox" id="login-remember-me" name="login-remember-me">
                        <span></span>
                    </label>
                    <label for="login-remember-me">Remember Me?</label>
                </div>
                <div class="col-xs-4 text-right">
                    <button type="submit" class="btn btn-effect-ripple btn-sm btn-primary"><i class="fa fa-check"></i> Login</button>
                </div>
            </div>
        </form>
        <!-- END Login Form -->
    </div>
    <!-- END Login Block -->

    <!-- Footer -->
    <footer class="text-muted text-center animation-pullUp">
        <small><span id="year-copy"></span> &copy; <a href="http://access2.me" target="_blank"><?php echo $template['name'] . ' ' . $template['version']; ?></a></small>
    </footer>
    <!-- END Footer -->
</div>
<!-- END Login Container -->

<?php include 'inc/template_scripts.php'; ?>

<!-- Load and execute javascript code used only in this page -->
<script src="js/pages/readyLogin.js"></script>
<script>$(function(){ ReadyLogin.init(); });</script>

<?php include 'inc/template_end.php'; ?>