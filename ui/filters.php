<?php require_once __DIR__ . "/login-check.php"; ?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>

<?php
use Access2Me\Helper;

$db = new Database;
$auth = Helper\Registry::getAuth();
$userId = $auth->getLoggedUser()['id'];

// prepare filters for render
$filters = array();
foreach (Filter::getFiltersByUserId($userId, $db) as $filter) {
    $filters[] = array(
        'id' => $filter['id'],
        'field' => $filter['field'],
        'type' => $filter['type'],
        'value' => $filter['value']
    );
}

?>

<div id="page-content">
    <div class="block">
        <!-- Table Styles Title -->
        <div class="block-title clearfix">
            <h1>Filtering</h1>
        </div>

        <div>
            <button id="filter-new" class="btn-effect-ripple btn-success btn-sm">Add new filter</button>
        </div>
        
        <form id="form-filter-edit" class="form-inline" style="display: none">
            <input type="hidden" class="filter-id" />
            <div class="form-group" style="vertical-align: top">
                <select name="field-name" class="field-name form-control">
                    <?php foreach (Filter::getFilterableFields() as $field=>$name): ?>
                        <option value="<?php echo htmlentities($field); ?>"><?php echo htmlentities($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="vertical-align: top">
                <select name="filter-type" class="filter-type form-control">
                    <?php foreach (Filter::getTypes() as $type): ?>
                        <option value="<?php echo $type; ?>"><?php echo htmlentities(Filter::getConditionNameByType($type)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="vertical-align: top">
                <input type="text" name="value" class="filter-value form-control" placeholder="Value">
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
                        <th>Filter</th>
                        <th style="width: 120px;" class="text-center">
                            <i class="fa fa-flash"></i>
                        </th>
                    </tr>
                </thead>
                <tbody id="filters-holder">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script id="filter-content-template" type="text/x-jsrender">
    <div class="filter-content" data-id="{{:id}}">
        {{:field}} {{:condition}} {{:value}}
    </div>
</script>

<script id="filter-template" type="text/x-jsrender">
<tr>
    <td class="text-center">
        <label class="csscheckbox csscheckbox-primary">
            <input type="checkbox">
            <span></span>
        </label>
    </td>
    <td>
        {{include tmpl="#filter-content-template" /}}
    </td>
    <td class="text-center">
        <a href="javascript:void(0)" data-toggle="tooltip" title="Edit filter"
           class="btn btn-effect-ripple btn-sm btn-success filter-edit"><i class="fa fa-pencil"></i></a>
        <a href="javascript:void(0)" data-toggle="tooltip" title="Delete filter"
           class="btn btn-effect-ripple btn-sm btn-danger filter-delete"><i class="fa fa-times"></i></a>
    </td>
</tr>
</script>

<?php include 'inc/page_footer.php'; ?>
<?php include 'inc/template_scripts.php'; ?>

<!-- Load and execute javascript code used only in this page -->
<script src="js/vendor/jsrender.min.js"></script>
<script src="js/vendor/lodash.min.js"></script>
<script src="js/pages/formsWizard.js"></script>
<script src="js/pages/filters.js"></script>
<script>$(function(){ FormsWizard.init(); });</script>
<script>
    $(function() {
        var data = {
            fields: <?php echo json_encode(\Filter::getFilterableFields()); ?>,
            conditions: <?php echo json_encode(\Filter::getConditions()); ?>,
            filters: <?php echo json_encode($filters); ?>
        };
        Filters.init(data);
    });
</script>

<?php include 'inc/template_end.php'; ?>