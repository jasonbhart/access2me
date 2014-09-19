<?php

require_once __DIR__ . "/../boot.php";

$db = new Database;

$filters = Filter::getFiltersByUserId('1', new Database);

foreach ($filters AS $filter) {
    switch ($filter['type']) {
        case '1':
            $filterTypes['must_be'][] = $filter;
            break;
        case '2':
            $filterTypes['must_not_be'][] = $filter;
            break;
        case '3':
            $filterTypes['must_be_greater'][] = $filter;
            break;
        case '4':
            $filterTypes['must_not_be_greater'][] = $filter;
            break;
    }
}
?>

<html>
<head>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">

    <!-- Latest compiled and minified JavaScript -->
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
</head>
<body>

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


</body>
</html>