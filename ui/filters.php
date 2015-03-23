<?php require_once __DIR__ . "/login-check.php"; ?>
<?php include 'inc/config.php'; $template['header_link'] = 'THE END OF SPAM AS WE KNOW IT'; ?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>

<?php
use Access2Me\Filter;
use Access2Me\Filter\Comparator;
use Access2Me\Helper;
use Access2Me\Model;


/**
 * Returns filter descriptions (metadata)
 * Used to display add/edit filter form
 *
 * @param Filter\Type\AbstractType[] $types
 * @return array
 */
function getFilterMetadata($types)
{
    $data = [
        'types' => [],
    ];

    $comparators = [];

    // process types
    foreach ($types as $pid=>$type) {
        // collect available properties
        $properties = [];
        foreach ($type->properties as $id => $property) {
            $properties[] = [
                'id' => $id,
                'type' => $property['type'],
                'name' => $property['name']
            ];

            // collect comparator ids
            $comparators[$property['type']] = true;
        }

        $data['types'][] = [
            'id' => $pid,
            'name' => $type->name,
            'properties' => $properties
        ];
    }

    // methods (lesser, equals, ...)
    foreach ($comparators as $type=>$val) {
        $methods = [];
        foreach (Filter\ComparatorFactory::getInstance($type)->methods as $id=>$m)
            $methods[] = array_merge(['id'=>$id], $m);
        $data['compTypes'][$type] = $methods;
    }

    return $data;
}

$db = new Database;
$auth = Helper\Registry::getAuth();
$userId = $auth->getLoggedUser()['id'];

$filterRepo = new Model\FiltersRepository($db);
// prepare filters for render
$filters = array_map(
    function(Model\Filter $filter) {
        return [
            'id' => $filter->getId(),
            'type' => $filter->getType(),
            'property' => $filter->getProperty(),
            'method' => $filter->getMethod(),
            'value' => $filter->getValue()
        ];
    },
    $filterRepo->findByUserId($userId)
);

$filterTypes = Helper\Registry::getFilterTypes();
$metadata = getFilterMetadata($filterTypes);
?>

<div id="page-content" ng-app="access2me">
    <div class="block" ng-controller="filtersController">
        <!-- Table Styles Title -->
        <div class="block-title clearfix">
            <h1>Filtering</h1>
        </div>

        <div>
            <button class="btn-effect-ripple btn-success btn-sm" ng-click="addNew=true">Add new filter</button>
        </div>

        <a2m-filter-edit on-cancel="addNew=false" on-save="create(filter)" visible="addNew"></a2m-filter-edit>

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
                    <th>Filter</th>
                    <th style="width: 120px;" class="text-center">
                        <i class="fa fa-flash"></i>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr ng-repeat="filter in filters track by filter.id">
                    <td class="text-center">
                        <label class="csscheckbox csscheckbox-primary">
                            <input type="checkbox">
                            <span></span>
                        </label>
                    </td>
                    <td>
                        <div ng-show="!filter.editing">{{ formatFilter(filter) }}</div>
                        <a2m-filter-edit filter="filter" on-cancel="filter.editing=false" on-save="update(filter, formData)" visible="filter.editing"/>
                    </td>
                    <td class="text-center">
                        <a href="javascript:void(0)" data-toggle="tooltip" title="Edit filter"
                           class="btn btn-effect-ripple btn-sm btn-success filter-edit"
                           ng-click="filter.editing=true"><i class="fa fa-pencil"></i></a>
                        <a href="javascript:void(0)" data-toggle="tooltip" title="Delete filter"
                           class="btn btn-effect-ripple btn-sm btn-danger filter-delete"
                           ng-click="delete(filter)"><i class="fa fa-times"></i></a>
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
<script src="js/pages/filters.js"></script>
<script>$(function(){ FormsWizard.init(); });</script>
<script>
    var data = {
        metadata: <?php echo json_encode($metadata); ?>,
        filters: <?php echo json_encode($filters); ?>
    };

    Filters.init(window.angular, data);
</script>
<?php include 'inc/template_end.php'; ?>