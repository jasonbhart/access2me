<?php require_once __DIR__ . "/login-check.php"; ?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>

<?php
use Access2Me\Helper;
use Access2Me\Model;
use Access2Me\Service;

class SidebarController
{
    /**
     * @var array
     */
    protected $appConfig;

    /**
     * @var \Database
     */
    protected $db;

    /**
     * @var Helper\Auth
     */
    protected $auth;

    /**
     * @var Helper\Router
     */
    protected $router;

    /**
     * @param array $appConfig
     * @param \Database $db
     * @param Helper\Auth $auth
     * @param Helper\Router $router
     */
    public function __construct($appConfig, $db, $auth, $router)
    {
        $this->appConfig = $appConfig;
        $this->db = $db;
        $this->auth = $auth;
        $this->router = $router;
    }

    public function process()
    {
         if (isset($_GET['linkto'])) {
             $this->linkTo($_GET['linkto']);
             return true;
         } elseif (isset($_GET['unlink'])) {
             $this->unlinkFrom($_GET['unlink']);
             return true;
         } elseif (isset($_POST['side-profile'])) {
             $this->saveSidebarProfile();
             return true;
         } elseif(isset($_GET['set-option'])) {
             $this->setOption();
             return true;
         }

        Helper\Http::redirect($this->router->getUrl('home'));
    }

    /**
     *  Link user to external service (linkedin, twitter, etc.)
     *
     * @param string $type
     */
    public function linkTo($type)
    {
        if ($type == 'linkedin') {
            $user = $this->auth->getLoggedUser();
            $request = new Service\Auth\Linkedin\UserAuthRequest($user['id'], $this->router->getUrl('home'));
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
            $user = $this->auth->getLoggedUser();
            $user['linkedin_access_token'] = null;
            $userRepo = new Model\UserRepository($this->db);
            $userRepo->save($user);
            Helper\FlashMessages::add('You have successfully unlinked LinkedIn account', Helper\FlashMessages::SUCCESS);
            // redirect back to this page
            Helper\Http::redirect($this->router->getUrl('home'));
        }
    }

    /**
     * Saves profile data from sidebar
     */
    public function saveSidebarProfile()
    {
        $fullName = isset($_POST['side-profile-fullname']) ? $_POST['side-profile-fullname'] : null;
        $email = isset($_POST['side-profile-email']) ? $_POST['side-profile-email'] : null;
        $password = isset($_POST['side-profile-password']) ? $_POST['side-profile-password'] : null;
        $password2 = isset($_POST['side-profile-password-confirm']) ? $_POST['side-profile-password-confirm'] : null;
        $whitelistDomain = isset($_POST['whitelist-domain']);

        $userRepo = new Model\UserRepository($this->db);
        $user = $this->auth->getLoggedUser();

        // validate profile data
        $errors = [];
        if (!Helper\Validator::isValidFullname($fullName)) {
            $errors['name'] = 'Please enter your full name';
        }

        if (!Helper\Validator::isValidEmail($email)) {
            $errors['email'] = 'Please enter a valid email address';
        } else {
            $anotherUser = $userRepo->getByEmail($email);
            if ($anotherUser !== null && $anotherUser['id'] != $user['id']) {
                $errors['username'] = 'Email <strong>"' . htmlentities($email) . '"</strong> already used.';
            }
        }

        // did user enter password ?
        if (!empty($password) && (!Helper\Validator::isValidPassword($password) || $password != $password2)) {
            $errors['password'] = 'Your password must be at least 5 characters long';
        }

        // update user
        if (!$errors) {
            $user['name'] = $fullName;
            $user['email'] = $email;
            if (!empty($password)) {
                $user['password'] = $this->auth->encodePassword($password);
            }

            $userRepo->save($user);

            // whitelist domain
            if ($whitelistDomain) {
                $splitted = Helper\Email::splitEmail($email);
                $userListRepo = new Model\UserSenderRepository($this->db);
                $entry = $userListRepo->getByUserAndSender($user['id'], $splitted['domain']);
                if (!$entry) {
                    $entry = [
                        'user_id' => $user['id'],
                        'sender' => $splitted['domain'],
                        'type' => Model\UserSenderRepository::TYPE_DOMAIN
                    ];
                }

                $entry['access'] = Model\UserSenderRepository::ACCESS_ALLOWED;

                $userListRepo->save($entry);
            }

            Helper\FlashMessages::add('Profile updated!', Helper\FlashMessages::SUCCESS);
        } else {
            // show errors on next page load
            foreach ($errors as $error) {
                Helper\FlashMessages::add($error, Helper\FlashMessages::ERROR);
            }
        }

        Helper\Http::redirect($this->router->getUrl('home'));
    }

    // handles switches on the sidebar
    public function setOption()
    {
        $name = isset($_POST['name']) ? $_POST['name'] : null;
        $value = isset($_POST['value']) ? $_POST['value'] : null;

        // attach email header option
        if ($name == 'email-header' && ($value == 'true' || $value == 'false')) {
            $user = $this->auth->getLoggedUser();
            $user['attach_email_header'] = $value == 'true';
            $userRepo = new Model\UserRepository($this->db);
            $userRepo->save($user);

            Helper\Http::jsonResponse(['status' => 'success']);
        }

        Helper\Http::generate404();
    }
}


// process userlist actions
$db = Helper\Registry::getDatabase();
$auth = Helper\Registry::getAuth();
$router = Helper\Registry::getRouter();
$ctrl = new SidebarController($appConfig, $db, $auth, $router);
$ctrl->process();
