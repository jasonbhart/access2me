<?php

require_once __DIR__ . "/login-check.php";

use Access2Me\Helper;
use Access2Me\Model;

$user = Helper\Registry::getAuth()->getLoggedUser();
$db = Helper\Registry::getDatabase();

// get user's emails, convert them and pass to view
$userEmailRepo = new Model\UserEmailRepository($db);
$userEmails = array_map(
    function(Model\UserEmail $userEmail) {
        return [
            'id' => $userEmail->getId(),
            'email' => $userEmail->getEmail()
        ];
    },
    $userEmailRepo->findByUserId($user['id'])
);

?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>
<div id="page-content" ng-app="access2me">
    <div class="block" ng-controller="userEmailsController">
        <!-- Table Styles Title -->
        <div class="block-title clearfix">
            <h1>User emails</h1>
        </div>

        <div>
            <button class="btn-effect-ripple btn-success btn-sm" ng-click="addNew=true">Add new email</button>
        </div>

        <a2m-user-email-edit on-cancel="addNew=false" on-save="create(record)" visible="addNew"></a2m-user-email-edit>

        <div class="table-responsive">
            <table class="table table-striped table-bordered table-vcenter table-hover">
                <thead>
                <tr>
                    <th style="width: 80px;" class="text-center">
                        <label class="csscheckbox csscheckbox-primary">
                            <input type="checkbox">
                            <span></span>
                        </label>
                    </th>
                    <th>Email</th>
                    <th style="width: 120px;" class="text-center">
                        <i class="fa fa-flash"></i>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr ng-repeat="record in records track by record.id">
                    <td class="text-center">
                        <label class="csscheckbox csscheckbox-primary">
                            <input type="checkbox">
                            <span></span>
                        </label>
                    </td>
                    <td>
                        <div ng-show="!record.editing">{{ format(record) }}</div>
                        <a2m-user-email-edit record="record" on-cancel="record.editing=false" on-save="update(record, formData)" visible="record.editing"></a2m-user-email-edit>
                    </td>
                    <td class="text-center">
                        <a href="javascript:void(0)" data-toggle="tooltip" title="Edit"
                           class="btn btn-effect-ripple btn-sm btn-success filter-edit"
                           ng-click="record.editing=true"><i class="fa fa-pencil"></i></a>
                        <a href="javascript:void(0)" data-toggle="tooltip" title="Delete"
                           class="btn btn-effect-ripple btn-sm btn-danger filter-delete"
                           ng-click="delete(record)"><i class="fa fa-times"></i></a>
                    </td>
                </tr>
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
<script src="js/vendor/angular.min.js"></script>
<script src="js/pages/formsWizard.js"></script>
<script src="js/pages/userEmails.js"></script>
<script>$(function(){ FormsWizard.init(); });</script>
<script>
    var data = {
        records: <?php echo json_encode($userEmails); ?>
    };

    UserEmails.init(window.angular, data);
</script>
<?php include 'inc/template_end.php'; ?>