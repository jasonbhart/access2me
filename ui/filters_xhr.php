<?php

require_once __DIR__ . '/../boot.php';

use Access2Me\Helper;
use Access2Me\Filter;
use Access2Me\Model;

$db = new Database();
$auth = Helper\Registry::getAuth();
$filterRepo = new Model\FiltersRepository($db);

if (!$auth->isAuthenticated()) {
    Helper\Http::generate403();
}

$action = isset($_GET['action']) ? $_GET['action'] : null;

// save
if ($action == 'save') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $typeId = isset($_POST['type']) ? (int)$_POST['type'] : 0;
    $propertyId = isset($_POST['property']) ? $_POST['property'] : null;
    $methodId = isset($_POST['method']) ? (int)$_POST['method'] : 0;
    $value = isset($_POST['value']) ? $_POST['value'] : null;

    // validate type
    $filterTypes = Helper\Registry::getFilterTypes();

    if (!isset($filterTypes[$typeId])) {
        Helper\Http::jsonResponse(['status' => 'error', 'message' => 'Unknown filter type']);
    }

    $filterType = $filterTypes[$typeId];

    // validate property
    if (!isset($filterType->properties[$propertyId])) {
        Helper\Http::jsonResponse(['status' => 'error', 'message' => 'Unknown property']);
    }

    // validate method
    $property = $filterType->properties[$propertyId];
    $compType = Filter\ComparatorFactory::getInstance($property['type']);

    if (!isset($compType->methods[$methodId])) {
        Helper\Http::jsonResponse(['status' => 'error', 'message' => 'Unknown method']);
    }

    if (empty($value)) {
        Helper\Http::jsonResponse(['status' => 'error', 'message' => 'Invalid value']);
    }


    $user = $auth->getLoggedUser();

    // add filter
    if ($id > 0) {      // update
        
        $filter = $filterRepo->getById($id);
        if ($filter === null) {
            Helper\Http::generate404();
        }

        // check if user is the owner
        if ($filter->getUserId() != $user['id']) {
            Helper\Http::generate403();
        }
    } else {            // insert
        $filter = new Model\Filter();
        $filter->setUserId($user['id']);
    }

    $filter->setType($typeId);
    $filter->setProperty($propertyId);
    $filter->setMethod($methodId);
    $filter->setValue($value);
    $id = $filterRepo->save($filter);

    Helper\Http::jsonResponse(['status' => 'success', 'id' => $id]);

} else if ($action == 'delete') {           // delete
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $filter = $filterRepo->getById($id);
    if ($filter === null) {
        Helper\Http::generate404();
        exit;
    }

    // check if user is the owner
    $user = $auth->getLoggedUser();
    if ($filter->getUserId() != $user['id']) {
        Helper\Http::generate403();
        exit;
    }

    $filterRepo->delete($filter->getId());
    echo json_encode(array('status' => 'success', 'id' => $id));
    exit;
}

Helper\Http::generate404();
