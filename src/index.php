<?php
namespace Core
{
  define('SYSTEM_ROOT', dirname(__FILE__));
  require . './system/core.inc.php';

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
