<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('SYSTEM_ROOT', dirname(__FILE__));
include './system/core.inc.php';
include './module/account/user/user.php';

$moduleManager = new \Core\ModuleManager();
?>
