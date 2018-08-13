<?php

/*
 * This file is part of RazyFramwork.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework
{
  define('SYSTEM_ROOT', __DIR__);
  require './system/core.inc.php';

  // Load module
  $moduleManager = new ModuleManager();

  if (CLI_MODE) {
  	$path = $moduleManager->getScriptRoute();

  	if (!$moduleManager->route($path)) {
  		die("Command not found\n");
  	}
  } else {
  	$urlQuery = (URL_ROOT) ? substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], URL_ROOT) + strlen(URL_ROOT)) : $_SERVER['REQUEST_URI'];

  	$urlQuery         = parse_url($urlQuery);
  	$urlQuery['path'] = rtrim($urlQuery['path'], '/') . '/';

  	if (!$moduleManager->route($urlQuery['path'])) {
  		header('HTTP/1.0 404 Not Found');
  		die();
  	}

  	TemplateManager::OutputQueued();
  }
}
