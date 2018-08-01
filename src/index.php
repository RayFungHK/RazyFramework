<?php
namespace Core
{
  define('SYSTEM_ROOT', dirname(__FILE__));
<<<<<<< HEAD
  require './system/core.inc.php';
=======
  require . './system/core.inc.php';
>>>>>>> ba86e7dc3ca6ab821a273a0f47d2e9fb59cc6691

  // Load module
  $moduleManager = new ModuleManager();

  if (CLI_MODE) {
    $path = $moduleManager->getScriptRoute();

    if (!$moduleManager->route($path)) {
      die("Command not found\n");
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
