<?php
namespace Core
{
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);

  define('SYSTEM_ROOT', dirname(__FILE__));
  require SYSTEM_ROOT . './system/core.inc.php';

  // Load module
  $moduleManager = new ModuleManager();

  if (CLI_MODE) {
    // Cli Mode
    $argv = $_SERVER['argv'];
    $script = array_shift($argv);

    // If no args provided, show cli command list
    if (!count($argv)) {
      $moduleManager->showCommand();
      exit(0);
    }

    $command = array_shift($argv);

    // CLI receive command
    if ($command) {
      $args = array(
        'args' => array(),
        'params' => array()
      );

      $lastParam = '';
      $paramStage = false;
      foreach ($argv as $key => $value) {
        if (preg_match('/^(-){1,2}([^\s]+)$/', $value, $matches, PREG_OFFSET_CAPTURE)) {
          $lastParam = $matches[2][0];
          $args['params'][$lastParam] = '';
          $paramStage = true;
        } else {
          if ($lastParam) {
            $args['params'][$lastParam] = $value;
            $lastParam = '';
          } else {
            if ($paramStage) {
              die("Invalid parameters\n");
            }
            $args['args'][] = $value;
          }
        }
      }

      if (!$moduleManager->cli($command, $args)) {
        die("Command [$command] not exists\n");
      }
    }
  } else {
    $urlQuery = (URL_ROOT) ? substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], URL_ROOT) + strlen(URL_ROOT)) : $_SERVER['REQUEST_URI'];

    $urlQuery = parse_url($urlQuery);
    $urlQuery['path'] = rtrim($urlQuery['path'], '/') . '/';

    if (!$moduleManager->route($urlQuery['path'])) {
      header('HTTP/1.0 404 Not Found');
      die();
    }

    TemplateManager::OutputQueued();
  }
}
?>
