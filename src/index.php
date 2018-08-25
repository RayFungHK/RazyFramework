<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework
{
  define('SYSTEM_ROOT', __DIR__);
  require SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'system' . \DIRECTORY_SEPARATOR . 'core.inc.php';

	// Setup the module path from global config
	if (isset($configuration['module_path']) && trim($configuration['module_path'])) {
		ModuleManager::SetModuleFolder($configuration['module_path']);
	}

  if (CLI_MODE) {
  	define('REQUEST_ROUTE', null);

  	// Load module
  	$moduleManager = new ModuleManager();
  	$path          = $moduleManager->getScriptRoute();

  	if (!$moduleManager->route($path)) {
  		die("Command not found\n");
  	}
  } else {
  	// Enable gzip compression
  	if (!ob_start('ob_gzhandler')) {
  		ob_start();
  	}

  	$urlQuery = (URL_ROOT) ? substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], URL_ROOT) + strlen(URL_ROOT)) : $_SERVER['REQUEST_URI'];

  	$urlQuery         = parse_url($urlQuery);
  	$urlQuery['path'] = rtrim($urlQuery['path'], '/') . '/';
  	$urlQuery['path'] = preg_replace('/^\/index.php/', '', $urlQuery['path']);

  	define('REQUEST_ROUTE', $urlQuery['path']);

  	// Load module
  	$moduleManager = new ModuleManager();
  	if (!$moduleManager->route($urlQuery['path'])) {
  		header('HTTP/1.0 404 Not Found');
  		die();
  	}

  	ob_end_flush();
  }
}
