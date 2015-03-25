<?php require_once __DIR__ . "/login-check.php"; ?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>

<?php
use Access2Me\Helper;
use Access2Me\Model;

$db = new Database;
$auth = Helper\Registry::getAuth();
$userId = $auth->getLoggedUser()['id'];

$type = isset($_GET['type']) ? $_GET['type'] : null;

// redirect to the home page if invalid type requested
if ($type != 'whitelisted' && $type != 'blacklisted') {
    header('Location: ' . $appConfig['projectUrl'] . '/ui');
    exit;
}

// process request
$userSenderRepo = new Model\UserSenderRepository($db);

if ($type == 'whitelisted') {
    $access = Model\UserSenderRepository::ACCESS_ALLOWED;
    $title = 'Whitelisted senders';
} else {    // blacklisted
    $access = Model\UserSenderRepository::ACCESS_DENIED;
    $title = 'Blacklisted senders';
}

// prepare view data
$senders = [];
foreach ($userSenderRepo->findByUserAndAccess($userId, $access) as $sender) {
    $senders[] = [
        'id' => $sender['id'],
        'sender' => $sender['sender'],
        'type' => $sender['type']
    ];
}

$types = [
    Model\UserSenderRepository::TYPE_DOMAIN => 'domain',
    Model\UserSenderRepository::TYPE_EMAIL => 'email'
];

$viewData = [
    'entries' => $senders,
    'types' => $types,
    'access' => $access
];
?>

<div id="page-content">
    <div class="block">
        <!-- Table Styles Title -->
        <div class="block-title clearfix">
            <h1><?php echo $title; ?></h1>
        </div>

        <div>
            <button id="ctrl-new-entry" class="btn-effect-ripple btn-success btn-sm">Add new entry</button>
        </div>
        
        <form id="form-entry-edit" class="form-inline" style="display: none">
            <input type="hidden" class="entry-id" />
            <div class="form-group" style="vertical-align: top">
                <input name="entry-sender" type="text" class="form-control entry-sender" placeholder="Sender"/>
            </div>
            <div class="form-group" style="vertical-align: top">
                <select name="entry-type" class="entry-type form-control">
                    <?php foreach ($types as $id=>$name): ?>
                        <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="vertical-align: top">
                <button type="submit" class="btn btn-effect-ripple btn-sm btn-primary form-save"><i class="fa fa-check"></i> Save</button>
            </div>
            <div class="form-group" style="vertical-align: top">
                <button type="button" class="btn btn-effect-ripple btn-sm btn-primary form-cancel"><i class="fa fa-fire"></i> Cancel</button>
            </div>
        </form>

        <!-- filters -->
        <div class="table-responsive">
            <table id="general-table" class="table table-striped table-bordered table-vcenter table-hover">
                <thead>
                    <tr>
                        <th style="width: 80px;" class="text-center">
                            <label class="csscheckbox csscheckbox-primary">
                                <input type="checkbox">
                                <span></span>
                            </label>
                        </th>
                        <th>Sender</th>
                        <th>Type</th>
                        <th style="width: 120px;" class="text-center">
                            <i class="fa fa-flash"></i>
                        </th>
                    </tr>
                </thead>
                <tbody id="entries-holder">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script id="entry-content-template" type="text/x-jsrender">
    <td>{{:sender}}</td>
    <td>{{:type}}</td>
</script>

<script id="entry-template" type="text/x-jsrender">
<tr data-id="{{:id}}">
    <td class="text-center">
        <label class="csscheckbox csscheckbox-primary">
            <input type="checkbox">
            <span></span>
        </label>
    </td>
    {{include tmpl="#entry-content-template" /}}
    <td class="text-center">
        <a href="javascript:void(0)" data-toggle="tooltip" title="Edit"
           class="btn btn-effect-ripple btn-sm btn-success entry-edit"><i class="fa fa-pencil"></i></a>
        <a href="javascript:void(0)" data-toggle="tooltip" title="Delete"
           class="btn btn-effect-ripple btn-sm btn-danger entry-delete"><i class="fa fa-times"></i></a>
    </td>
</tr>
</script>

<?php include 'inc/page_footer.php'; ?>
<?php include 'inc/template_scripts.php'; ?>

<!-- Load and execute javascript code used only in this page -->
<script src="js/vendor/jsrender.min.js"></script>
<script src="js/vendor/lodash.min.js"></script>
<script src="js/pages/formsWizard.js"></script>
<script src="js/pages/userSenders.js"></script>
<script>$(function(){ FormsWizard.init(); });</script>
<script>
    $(function() {
        var data = <?php echo json_encode($viewData); ?>;
        UserSenders.init(data);
    });
</script>

<?php include 'inc/template_end.php'; ?>