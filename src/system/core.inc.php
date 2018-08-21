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
	// Prevent Resubmission
	session_start();

	// Remove useless header
	header_remove('X-Powered-By');

  // Load global config
  $configuration = [];
  if (file_exists(SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'configuration' . \DIRECTORY_SEPARATOR . 'global.php')) {
  	try {
  		$configuration = require SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'configuration' . \DIRECTORY_SEPARATOR . 'global.php';
  	} catch (\Exception $e) {
  		header('HTTP/1.0 500 Internal Server Error');
  		die();
  	}
  }

	if (\PHP_SAPI === 'cli' || defined('STDIN')) {
		define('CLI_MODE', true);
	} else {
		define('CLI_MODE', false);

		// Declare `URL_ROOT`
		// The absolute path, if your Razy locate in http://yoursite.com/Razy/Framework, the URL_ROOT will declare as /Razy/Framework
		define('URL_ROOT', preg_replace('/\\\\+/', '/', substr(SYSTEM_ROOT, strpos(SYSTEM_ROOT, $_SERVER['DOCUMENT_ROOT']) + strlen($_SERVER['DOCUMENT_ROOT']))));

		// Declare `HOSTNAME`
		// The hostname, if the REQUEST PATH is http://yoursite.com:8080/Razy, the HOSTNAME will declare as yoursite.com:8080
		define('HOSTNAME', (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : ((isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'UNKNOWN'));

		// Declare `PORT`
		// The protocal, if the REQUEST PATH is http://yoursite.com:8080/Razy, the PORT will declare as 8080
		define('PORT', $_SERVER['SERVER_PORT']);

		// Declare `HTTPS`
		// Determine of HTTPS protocol
		if (isset($configuration['identify_ssl']) && is_callable($configuration['identify_ssl'])) {
			define('HTTPS', $configuration['identify_ssl']());
		} else {
			define('HTTPS', (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS']) || PORT === '443');
		}

		// Declare `URL_BASE`
		// The full URL path of Razy Framework, combined with http/https protocal, HOSTNAME and URL_ROOT
		define('URL_BASE', ((HTTPS) ? 'https://' : 'http://') . HOSTNAME . ((PORT !== '80' && PORT !== '443') ? ':' . PORT : '') . URL_ROOT);

		// Force using HTTPS if global config declared parameter `force_ssl` as true
		if (isset($configuration['force_ssl']) && $configuration['force_ssl']) {
			if (!HTTPS) {
				header('location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			}
		}
	}

	define('VIEW_PATH', SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'view');
	define('VIEW_PATH_URL', URL_BASE . '/view');
	define('MATERIAL_PATH', SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'material');

	// Register Autoloader
	spl_autoload_register(function ($class) use ($configuration) {
		$classes = explode('\\', $class);
		$package = (count($classes) > 1) ? array_shift($classes) : '';

		// Load Razy core library from root library folder
		if ('RazyFramework' === $package) {
			$path = implode(\DIRECTORY_SEPARATOR, $classes);

			// Setup the library path
			if (isset($configuration['library_path']) && trim($configuration['library_path'])) {
				$libraryFolder = trim($configuration['library_path']);
				$libraryFolder = realpath(preg_replace('/[\\\\\/]+/', \DIRECTORY_SEPARATOR, $libraryFolder . \DIRECTORY_SEPARATOR));

				if (false === $libraryFolder || !is_dir($libraryFolder)) {
					// If the library folder does not exists or not a directory
					header('HTTP/1.0 500 Internal Server Error');
					die();
				}
			} else {
				$libraryFolder = SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'library';
			}

			$libraryPath = $libraryFolder . \DIRECTORY_SEPARATOR . $path . '.php';

			if (file_exists($libraryPath)) {
				include $libraryPath;

				return class_exists($class);
			}

			return false;
		}

		// If the autoload class is not in RazyFramework namespace, load external library from module folder
		$manager = ModuleManager::GetInstance();

		return $manager->loadLibrary($class);

		return false;
	});
}
