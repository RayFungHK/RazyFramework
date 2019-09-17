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
	// Remove useless header
	header_remove('X-Powered-By');

	define('PLUGIN_FOLDER', append(SYSTEM_ROOT, 'plugins'));
	define('SITES_FOLDER', append(SYSTEM_ROOT, 'sites'));

	// Register Autoloader
	spl_autoload_register(function ($class) {
		$classes = explode('\\', $class);
		$namespace = (count($classes) > 1) ? array_shift($classes) : '';

		// Load Razy core library from root library folder
		if ('RazyFramework' === $namespace) {
			$libraryFolder = realpath(append(SYSTEM_ROOT, 'library'));
			if ($libraryFolder && is_dir($libraryFolder)) {
				$libraryPath = append($libraryFolder, $classes) . '.php';

				if (is_file($libraryPath)) {
					include $libraryPath;

					return class_exists($class);
				}
			}

			return false;
		}

		// If the class is not in the RazyFramework namespace, search and load in all registered module
		return Modular\Manager::SPLAutoload($class);
	});

	if (\PHP_SAPI === 'cli' || defined('STDIN')) {
		define('CLI_MODE', true);
	} else {
		define('CLI_MODE', false);

		// Declare `RELATIVE_ROOT`
		// The absolute path, if your Razy locate in http://yoursite.com/Razy/Framework, the RELATIVE_ROOT will declare as /Razy/Framework
		define('RELATIVE_ROOT', preg_replace('/\\\\+/', '/', substr(SYSTEM_ROOT, strpos(SYSTEM_ROOT, $_SERVER['DOCUMENT_ROOT']) + strlen($_SERVER['DOCUMENT_ROOT']))));

		// Declare `HOSTNAME`
		// The hostname, if the REQUEST PATH is http://yoursite.com:8080/Razy, the HOSTNAME will declare as yoursite.com
		define('HOSTNAME', (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : ((isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'UNKNOWN'));

		// Declare `PORT`
		// The protocal, if the REQUEST PATH is http://yoursite.com:8080/Razy, the PORT will declare as 8080
		define('PORT', $_SERVER['SERVER_PORT']);

		// Declare `SITE_URL_ROOT`
		$protocal = (is_ssl()) ? 'https' : 'http';
		define('SITE_URL_ROOT', $protocal . '://' . HOSTNAME . ((PORT !== '80') ? ':' . PORT : ''));

		// Declare `RAZY_URL_ROOT`
		define('RAZY_URL_ROOT', append(SITE_URL_ROOT, RELATIVE_ROOT));

		// Declare `RAZY_URL_ROOT`
		define('SCRIPT_URL', append(SITE_URL_ROOT, strtok($_SERVER['REQUEST_URI'], '?')));
	}

	if (isset($configuration['debug'])) {
		ErrorHandler::SetDebug((bool) $configuration['debug']);
	}

  set_exception_handler(function ($exception) {
  	if (!defined('EXCEPTION_HALT') && $exception) {
  		define('EXCEPTION_HALT', true);
  		ErrorHandler::ShowException($exception);
  	}
  });

	// Eject the wrapper
	$wrapper = (Scavenger::GetWorker())->eject();
	register_shutdown_function(function ($wrapper) {
		$wrapper->start();
	}, $wrapper);
	$wrapper = null;
}
