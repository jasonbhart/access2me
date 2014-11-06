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

// save
if ($action == 'save') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $fieldName = isset($_POST['field']) ? $_POST['field'] : null;
    $filterType = isset($_POST['type']) ? (int)$_POST['type'] : 0;
    $filterValue = isset($_POST['value']) ? $_POST['value'] : null;

    $fields = \Filter::getFilterableFields();
    $filterTypes = \Filter::getTypes();

    if (!isset($fields[$fieldName])
        || !isset($filterTypes[$filterType])
        || empty($filterValue)
    ) {
        echo json_encode(array('status' => 'error'));
        exit;
    }

    // add filter
    $user = $auth->getLoggedUser();
    if ($id > 0) {      // update
        
        $filter = \Filter::getFilterById($id, $db);
        if ($filter === null) {
            header('HTTP/1.0 404 Not Found');
            exit;
        }

        // check if user is the owner
        if ($filter['user_id'] != $user['id']) {
            header('HTTP/1.0 403 Access Denied');
            exit;
        }

        $db->update(
            'filters',
            array('type', 'field', 'value'),
            array($filterType, $fieldName, $filterValue),
            'id = ?',
            array($id)
        );
    } else {            // insert
        $id = $db->insert('filters',
                array('user_id', 'type', 'field', 'value'),
                array($user['id'], $filterType, $fieldName, $filterValue)
        );
    }
    
    echo json_encode(array('status' => 'success', 'id' => $id));
    exit;
} else if ($action == 'delete') {           // delete
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $filter = \Filter::getFilterById($id, $db);
    if ($filter === null) {
        header('HTTP/1.0 404 Not Found');
        exit;
    }

    // check if user is the owner
    $user = $auth->getLoggedUser();
    if ($filter['user_id'] != $user['id']) {
        header('HTTP/1.0 403 Access Denied');
        exit;
    }
    
    Filter::delete($id, $db);
    echo json_encode(array('status' => 'success', 'id' => $id));
    exit;
}

header('HTTP/1.0 404 Not Found');
