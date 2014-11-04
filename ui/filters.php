<?php require_once __DIR__ . "/login-check.php"; ?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>

<?php
use Access2Me\Helper;
use Access2Me\Model;

$db = new Database;
$auth = new Helper\Auth($db);
$userId = $auth->getLoggedUser()['id'];
$records = Filter::getFiltersByUserId($userId, $db);
$filters = $records ? Filter::getDescriptions($records) : array();

?>

<div id="page-content">
    <?php include('inc/page_status_icons.php'); ?>

    <div class="block">
        <!-- Table Styles Title -->
        <div class="block-title clearfix">
            <h1>Filtering</h1>
        </div>

        <button id="add-new-filter" class="btn-effect-ripple btn-success btn-sm">Add new filter</button>
        
        <form id="form-filter-edit" class="form-inline" style="display: block">
            <div class="form-group" style="vertical-align: top">
                <select id="field-name" name="field-name" class="form-control">
                    <?php foreach (Model\Profile\ProfileRepository::getFilterableFields() as $prop=>$name): ?>
                        <option value="<?php echo htmlentities($prop); ?>"><?php echo htmlentities($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="vertical-align: top">
                <select id="filter-type" name="filter-type" class="form-control">
                    <?php foreach (Filter::getTypes() as $type): ?>
                        <option value="<?php echo $type; ?>"><?php echo htmlentities(Filter::getConditionNameByType($type)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="vertical-align: top">
                <input type="text" id="value" name="value" class="form-control" placeholder="Value">
            </div>
            <div class="form-group" style="vertical-align: top">
                <button type="submit" class="btn btn-effect-ripple btn-sm btn-primary"><i class="fa fa-check"></i> Save</button>
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
                        <th>Filter</th>
                        <th style="width: 120px;" class="text-center">
                            <i class="fa fa-flash"></i>
                        </th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    foreach ($filters as $filter):
                        $descr = $filter['description'];
                ?>
                    <tr>
                        <td class="text-center">
                            <label class="csscheckbox csscheckbox-primary">
                                <input type="checkbox">
                                <span></span>
                            </label>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($descr['field']); ?>
                            <?php echo htmlspecialchars($descr['action']); ?>
                            <?php echo htmlspecialchars($descr['value']); ?>
                        </td>
                        <td class="text-center">
                            <a href="javascript:void(0)" data-toggle="tooltip" title="Edit filter"
                               class="btn btn-effect-ripple btn-sm btn-success"><i class="fa fa-pencil"></i></a>
                            <a href="javascript:void(0)" data-toggle="tooltip" title="Delete filter"
                               class="btn btn-effect-ripple btn-sm btn-danger"><i class="fa fa-times"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!--
<div class="container">
    <div class="page-header">
        <h1>Filters<span class="pull-right label label-default">Access2.me</span></h1>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">"Must Be" Filters</div>
            <table class="table">
                <tr>
                    <?php if (!empty($filterTypes['must_be'])) { ?>
                    <th width="40%">Field</th>
                    <th width="20%">Condition</th>
                    <th width="40%">Value</th>
                </tr>
                <?php
                    foreach ($filterTypes['must_be'] AS $filter) {
                        echo "<tr>";
                        echo "<td width=\"40%\">" . $filter['field'] . "</td>";
                        echo "<td width=\"20%\"><span style=\"color: #008000;\">" . Filter::getConditionNameByType($filter['type']) . "</span></td>";
                        echo "<td width=\"40%\">" . $filter['value'] . "</td>";
                        echo "</tr>";
                    }
                } else { ?>
                <center>-- None Found -- </center>
                <?php } ?>
            </table>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">"Must NOT Be" Filters</div>
            <table class="table">
                <tr>
                    <?php if (!empty($filterTypes['must_not_be'])) { ?>
                    <th width="40%">Field</th>
                    <th width="20%">Condition</th>
                    <th width="40%">Value</th>
                </tr>
                <?php
                    foreach ($filterTypes['must_not_be'] AS $filter) {
                        echo "<tr>";
                        echo "<td width=\"40%\">" . $filter['field'] . "</td>";
                        echo "<td width=\"20%\"><span style=\"color: #ff0000;\">" . Filter::getConditionNameByType($filter['type']) . "</td>";
                        echo "<td width=\"40%\">" . $filter['value'] . "</td>";
                        echo "</tr>";
                    }
                } else { ?>
                <center>-- None Found -- </center>
                <?php } ?>
            </table>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">"Must Be Greater" Filters</div>
            <table class="table">
                <tr>
                    <?php if (!empty($filterTypes['must_be_greater'])) { ?>
                    <th width="40%">Field</th>
                    <th width="20%">Condition</th>
                    <th width="40%">Value</th>
                </tr>
                <?php
                    foreach ($filterTypes['must_be_greater'] AS $filter) {
                        echo "<tr>";
                        echo "<td width=\"40%\">" . $filter['field'] . "</td>";
                        echo "<td width=\"20%\"><span style=\"color: #008000;\">" . Filter::getConditionNameByType($filter['type']) . "</td>";
                        echo "<td width=\"40%\">" . $filter['value'] . "</td>";
                        echo "</tr>";
                    }
                } else { ?>
                <center>-- None Found -- </center>
                <?php } ?>
            </table>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">"Must NOT Be Greater" Filters</div>
            <table class="table">
                <tr>
                    <?php if (!empty($filterTypes['must_not_be_greater'])) { ?>
                    <th width="40%">Field</th>
                    <th width="20%">Condition</th>
                    <th width="40%">Value</th>
                </tr>
                <?php
                    foreach ($filterTypes['must_not_be_greater'] AS $filter) {
                        echo "<tr>";
                        echo "<td width=\"40%\">" . $filter['field'] . "</td>";
                        echo "<td width=\"20%\"><span style=\"color: #ff0000;\">" . Filter::getConditionNameByType($filter['type']) . "</td>";
                        echo "<td width=\"40%\">" . $filter['value'] . "</td>";
                        echo "</tr>";
                    }
                } else { ?>
                <center>-- None Found -- </center>
                <?php } ?>
            </table>
    </div>
</div>-->

<?php include 'inc/page_footer.php'; ?>
<?php include 'inc/template_scripts.php'; ?>

<!-- Load and execute javascript code used only in this page -->
<script src="js/pages/formsWizard.js"></script>
<script src="js/pages/filters.js"></script>
<script>$(function(){ FormsWizard.init(); });</script>
<script>$(function(){ Filters.init(); });</script>

<?php include 'inc/template_end.php'; ?>