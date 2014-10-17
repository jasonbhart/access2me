<?php

if ($_POST) {

    require_once __DIR__ . "/../boot.php";
    $db = new Database;

    if ($_POST['login-username']) {
        $sql = "SELECT `password` FROM `users` WHERE `username` = '" . $_POST['login-username'] . "' LIMIT 1;";
        $password = $db->getArray($sql);

        if (!isset($password[0]['password'])) {
            $errorMessage = 'Invalid username';
        } else {
            if ($password[0]['password'] == md5('bacon' . $_POST['login-password'])) {
                if (isset($_POST['remember'])) {
                    $expire = time()+(60 * 60 * 24 * 30);
                } else {
                    $expire = time()+3600;
                }

                setcookie('a2muser', $_POST['login-username'], $expire);
                setcookie('a2mauth', md5('bacon' . $_POST['login-password']), $expire);

                header('Location: index.php');
            } else {
                $errorMessage = 'Invalid password';
            }
        }
    }
}

if ($_GET['action'] == 'logout') {
    unset($_COOKIE['a2muser']);
    unset($_COOKIE['a2mauth']);
    setcookie('a2muser', null, -1, '/');
    setcookie('a2mauth', null, -1, '/');

    header('Location: login.php');
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
                    Remember Me?
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
        <small><span id="year-copy"></span> &copy; <a href="http://goo.gl/RcsdAh" target="_blank"><?php echo $template['name'] . ' ' . $template['version']; ?></a></small>
    </footer>
    <!-- END Footer -->
</div>
<!-- END Login Container -->

<?php include 'inc/template_scripts.php'; ?>

<!-- Load and execute javascript code used only in this page -->
<script src="js/pages/readyLogin.js"></script>
<script>$(function(){ ReadyLogin.init(); });</script>

<?php include 'inc/template_end.php'; ?>