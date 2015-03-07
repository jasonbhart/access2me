<?php 
require_once __DIR__ . '/../boot.php';
use Access2Me\Helper;
use Access2Me\Model;

$db = new Database;
$auth = Helper\Registry::getAuth();

if ($auth->isAuthenticated()) {
    header('Location: index.php');
    exit;
}

if ($_POST) {

    $userRepo = new Model\UserRepository($db);

    $username = isset($_POST['register-username']) ? $_POST['register-username'] : null;
    $fullname = isset($_POST['register-fullname']) ? $_POST['register-fullname'] : null;
    $email = isset($_POST['register-email']) ? $_POST['register-email'] : null;
    $mailbox = isset($_POST['register-mailbox']) ? $_POST['register-mailbox'] : null;
    $whitelistDomain = isset($_POST['whitelist-domain']) && $_POST['whitelist-domain'] == 'on';
    $password = isset($_POST['register-password']) ? $_POST['register-password'] : null;
    $password2 = isset($_POST['register-password-verify']) ? $_POST['register-password-verify'] : null;
    $terms = isset($_POST['register-terms']) ? (bool)$_POST['register-terms'] : false;

    // very basic verification
    $errors = array();
    if (!Helper\Validator::isValidUsername($username)) {
        $errors['username'] = 'Please enter a username';
    }

    if (!Helper\Validator::isValidFullname($fullname)) {
        $errors['name'] = 'Please enter your full name';
    }

    if (!Helper\Validator::isValidEmail($email)) {
        $errors['email'] = 'Please enter a valid email address';
    } else if ($userRepo->getByUsername($username) !== null) {
        $errors['username'] = 'Username <strong>"' . htmlentities($username) . '"</strong> already used.';
    }

    if (!Helper\Validator::isValidEmail($mailbox)) {
        $errors['mailbox'] = 'Please enter a valid Gmail address';
    } else if ($userRepo->getByMailbox($mailbox) !== null) {
        $errors['mailbox'] = 'Mailbox <strong>"' . htmlentities($mailbox) . '"</strong> already used.';
    }

    if (!Helper\Validator::isValidPassword($password) || $password != $password2) {
        $errors['password'] = 'Your password must be at least 5 characters long';
    }

    if (!$terms) {
        $errors['terms'] = 'Please accept the terms!';
    }

    // create user
    if (count($errors) == 0) {
        $user = [
            'mailbox' => $mailbox,
            'email' => $email,
            'name' => $fullname,
            'username' => $username,
            'password' => $auth->encodePassword($password),
            'gmail_access_token' => null
        ];
        $userId = $userRepo->save($user);

        // whitelist domain address
        if ($whitelistDomain) {
            $splitted = Helper\Email::splitEmail($mailbox);
            $entry = [
                'user_id' => $userId,
                'sender' => $splitted['domain'],
                'type' => Model\UserSenderRepository::TYPE_DOMAIN,
                'access' => Model\UserSenderRepository::ACCESS_ALLOWED
            ];

            $userListRepo = new Model\UserSenderRepository($db);
            $userListRepo->save($entry);
        }

        try {
            // login user and redirect him to the welcome page
            $auth->login($username, $password);
            $url = Helper\Registry::getRouter()->getUrl('registration_success');
            Helper\Http::redirect($url);
        } catch (\Access2Me\Helper\AuthException $ex) {
            $errors[] = 'Application error';
            Logging::getLogger()->debug('Can\' register user', array('exception' => $ex));
        }
    }
}
?>

<?php include 'inc/config.php'; ?>
<?php include 'inc/template_start.php'; ?>

<!-- Login Container -->
<div id="login-container">
    <!-- Register Header -->
    <h1 class="h2 text-light text-center push-top-bottom animation-slideDown">
        <i class="fa fa-plus"></i> <strong>Create Account</strong>
    </h1>
    <!-- END Register Header -->

    <!-- Register Form -->
    <div class="block animation-fadeInQuickInv">
        <!-- Register Title -->
        <div class="block-title">
            <div class="block-options pull-right">
                <a href="login.php" class="btn btn-effect-ripple btn-primary" data-toggle="tooltip" data-placement="left" title="Back to login"><i class="fa fa-user"></i></a>
            </div>
            <h2>Register</h2>
        </div>
        <!-- END Register Title -->

        <!-- Register Form -->
        <form id="form-register" action="page_ready_register.php" method="post" class="form-horizontal">
            <div class="form-group">
                <?php if (isset($errors)) {
                    foreach ($errors as $error) { ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php }}; ?>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <input type="text" id="register-username" name="register-username" class="form-control" placeholder="Username">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <input type="text" id="register-fullname" name="register-fullname" class="form-control" placeholder="Full name">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <input type="text" id="register-email" name="register-email" class="form-control" placeholder="Email">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <input type="text" id="register-mailbox" name="register-mailbox" class="form-control" placeholder="Gmail address">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <input type="password" id="register-password" name="register-password" class="form-control" placeholder="Password">
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <input type="password" id="register-password-verify" name="register-password-verify" class="form-control" placeholder="Verify Password">
                </div>
            </div>
            <div class="form-group form-actions">
                <div class="col-xs-12">
                    <label class="csscheckbox csscheckbox-primary" data-toggle="tooltip" title="Add email domain to the whitelist">
                        <input type="checkbox" id="whitelist-domain" name="whitelist-domain">
                        <span></span>
                    </label>
                    <label for="whitelist-domain">Add mailbox's domain to the whitelist</label>
                </div>
            </div>
            <div class="form-group form-actions">
                <div class="col-xs-6">
                    <label class="csscheckbox csscheckbox-primary" data-toggle="tooltip" title="Agree to the terms">
                        <input type="checkbox" id="register-terms" name="register-terms">
                        <span></span>
                    </label>
                    <a href="#modal-terms" data-toggle="modal">Terms</a>
                </div>
                <div class="col-xs-6 text-right">
                    <button type="submit" class="btn btn-effect-ripple btn-success"><i class="fa fa-plus"></i> Create Account</button>
                </div>
            </div>
        </form>
        <!-- END Register Form -->
    </div>
    <!-- END Register Block -->

    <!-- Footer -->
    <footer class="text-muted text-center animation-pullUp">
        <small><span id="year-copy"></span> &copy; <a href="http://access2.me" target="_blank"><?php echo $template['name'] . ' ' . $template['version']; ?></a></small>
    </footer>
    <!-- END Footer -->
</div>
<!-- END Login Container -->

<!-- Modal Terms -->
<div id="modal-terms" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title text-center"><strong>Terms and Conditions</strong></h3>
            </div>
            <div class="modal-body">
                <h4 class="page-header">1. <strong>General</strong></h4>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas ultrices, justo vel imperdiet gravida, urna ligula hendrerit nibh, ac cursus nibh sapien in purus. Mauris tincidunt tincidunt turpis in porta. Integer fermentum tincidunt auctor.</p>
                <h4 class="page-header">2. <strong>Account</strong></h4>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas ultrices, justo vel imperdiet gravida, urna ligula hendrerit nibh, ac cursus nibh sapien in purus. Mauris tincidunt tincidunt turpis in porta. Integer fermentum tincidunt auctor.</p>
                <h4 class="page-header">3. <strong>Service</strong></h4>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas ultrices, justo vel imperdiet gravida, urna ligula hendrerit nibh, ac cursus nibh sapien in purus. Mauris tincidunt tincidunt turpis in porta. Integer fermentum tincidunt auctor.</p>
                <h4 class="page-header">4. <strong>Payments</strong></h4>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas ultrices, justo vel imperdiet gravida, urna ligula hendrerit nibh, ac cursus nibh sapien in purus. Mauris tincidunt tincidunt turpis in porta. Integer fermentum tincidunt auctor.</p>
            </div>
            <div class="modal-footer">
                <div class="text-center">
                    <button type="button" class="btn btn-effect-ripple btn-sm btn-primary" data-dismiss="modal">I've read them!</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END Modal Terms -->

<?php include 'inc/template_scripts.php'; ?>

<!-- Load and execute javascript code used only in this page -->
<script src="js/pages/readyRegister.js"></script>
<script>$(function(){ ReadyRegister.init(); });</script>

<?php include 'inc/template_end.php'; ?>