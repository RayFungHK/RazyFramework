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
	define('CORE_FOLDER', SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'system' . \DIRECTORY_SEPARATOR);
  require CORE_FOLDER . 'functions.php';
  require CORE_FOLDER . 'core.inc.php';

  Modular\Manager::Initialize();
  ErrorHandler::SetDebug(true);

  if (CLI_MODE) {
  	// Load module
  	$moduleManager = new Modular\Manager();
  	$path          = $moduleManager->getScriptRoute();

  	if (!$moduleManager->route($path)) {
  		die("Command not found\n");
  	}
  } else {
  	// Enable gzip compression
  	if (!ob_start('ob_gzhandler')) {
  		ob_start();
  	}

    session_set_cookie_params(0, RELATIVE_ROOT, HOSTNAME);
    session_name(md5(HOSTNAME . RELATIVE_ROOT));
    session_start();

  	// Create the pirmary module manager
  	$manager = new Modular\Manager();

  	if (!$manager->route()) {
  		ErrorHandler::Show404();
  		exit;
  	}

  	ob_end_flush();
  }
}
