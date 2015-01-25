<?php

require_once '../boot.php';

$db = new Database();

// drop column recipients_import
$query = 'ALTER TABLE users DROP COLUMN recipients_imported';
$db->execute($query);


