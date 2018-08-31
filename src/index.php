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
	if (array_key_exists('module_distribution', $configuration) && is_array($configuration['module_distribution'])) {
		ModuleManager::SetModuleDistribution($configuration['module_distribution']);
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

  	// Load module
  	$moduleManager = new ModuleManager();
  	define('REQUEST_ROUTE', $moduleManager->getURLQuery());

  	if (!$moduleManager->route(REQUEST_ROUTE)) {
  		header('HTTP/1.0 404 Not Found');
  		die();
  	}

  	ob_end_flush();
  }
}
