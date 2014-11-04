<?php

require_once __DIR__ . '/../boot.php';

use Access2Me\Helper;
use Access2Me\Model;

$db = new Database();
$auth = new Helper\Auth($db);

if (!$auth->isAuthenticated()) {
    header('HTTP/1.0 403 Access Denied');
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($action == 'addfilter') {
    $fieldName = isset($_POST['field-name']) ? $_POST['field-name'] : null;
    $filterType = isset($_POST['filter-type']) ? (int)$_POST['filter-type'] : 0;
    $value = isset($_POST['value']) ? $_POST['value'] : null;

    $fields = Model\Profile\ProfileRepository::getFilterableFields();
    $filterTypes = \Filter::getTypes();
    
    if (!isset($fields[$fieldName])
        || !isset($filterTypes[$filterType])
        || empty($value)
    ) {
        echo json_encode(array('status' => 'error'));
        exit;
    }
    
    // add filter
    $user = $auth->getLoggedUser();
    $db->insert('filters',
            array('user_id', 'type', 'field', 'value'),
            array($user['id'], $filterType, $fieldName, $value)
    );
    
    echo json_encode(array('status' => 'success'));
    exit;
}

header('HTTP/1.0 404 Not Found');
