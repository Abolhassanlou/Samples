<?php

require_once '../bootstrap.php';
require_once '../model/DB.php';

$db = new DB();
$connection = $db->getConnection();

if ($connection instanceof PDO) {
    echo "Connection successful";
} else {
    echo "Connection failed";
}