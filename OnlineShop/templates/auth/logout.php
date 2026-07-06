<?php
require_once '../../bootstrap.php';
require_once '../layout/header.php';

session_unset();
session_destroy();

header('Location: login.php');
exit;