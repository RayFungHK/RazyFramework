<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('SYSTEM_ROOT', dirname(__FILE__));
require './system/core.inc.php';

// Load module
$moduleManager = new \Core\ModuleManager();

if (php_sapi_name() == 'cli' || defined('STDIN')) {
  // Cli Mode
  $argv = $_SERVER['argv'];
  $script = array_shift($argv);
  $route = array_shift($argv);

  // Route
  if ($route) {
    $cliArgs = array(
      'args' => array(),
      'params' => array()
    );

    $lastParam = '';
    foreach ($argv as $key => $value) {
      if (preg_match('/^(-){1,2}([^\s]+)$/', $value, $matches, PREG_OFFSET_CAPTURE)) {
        $lastParam = $matches[2][0];
        $cliArgs['params'][$lastParam] = '';
      } else {
        if ($lastParam) {
          $cliArgs['params'][$lastParam] = $value;
          $lastParam = '';
        } else {
          $cliArgs['args'][] = $value;
        }
      }
    }

    if (!$moduleManager->cli($route, $cliArgs)) {
      die("No CLI found\n");
    }
  } else {
    die("The syntax of command is incorrect.\n");
  }
} else {
  if (URL_ROOT) {
    $urlQuery = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], URL_ROOT) + strlen(URL_ROOT));
  } else {
    $urlQuery = $_SERVER['REQUEST_URI'];
  }
echo $urlQuery;
die();
  $urlQuery = parse_url($urlQuery);
  $urlQuery['path'] = rtrim($urlQuery['path'], '/') . '/';

  if (!$moduleManager->route($urlQuery['path'])) {
    header('HTTP/1.0 404 Not Found');
    die();
  }
}
?>
