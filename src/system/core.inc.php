<?php
namespace RazyFramework
{
	session_start();

	if (php_sapi_name() == 'cli' OR defined('STDIN')) {
		define('CLI_MODE', true);
	} else {
		define('CLI_MODE', false);
		// Get the System path
		define('URL_ROOT', preg_replace('/\\\\+/', '/', substr(SYSTEM_ROOT, strpos(SYSTEM_ROOT, $_SERVER['DOCUMENT_ROOT']) + strlen($_SERVER['DOCUMENT_ROOT']))));

		// Get the hostname or domain name
		define('HTTP_PATH_ROOT', (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : ((isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'UNKNOWN'));

		// Get the server port
		define('PORT', $_SERVER['SERVER_PORT']);

		// Generate the URL path
		define('URL_BASE', (((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || PORT == '443') ? 'https://' : 'http://') . HTTP_PATH_ROOT . ((PORT != '80' && PORT != '443') ? ':' . PORT : '') . URL_ROOT);
	}

	define('MATERIAL_PATH', SYSTEM_ROOT . DIRECTORY_SEPARATOR . 'material' . DIRECTORY_SEPARATOR);

	// Register Autoloader
	spl_autoload_register(function($class) {
		$classes = explode('\\', $class);
		$package = (count($classes) > 1) ? array_shift($classes) : '';

		// Load Razy core library from root library folder
		if ($package == 'RazyFramework') {
			$path = implode(DIRECTORY_SEPARATOR, $classes);

			$library_path = SYSTEM_ROOT . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $path . '.php';
			if (file_exists($library_path)) {
				include $library_path;
				return class_exists($class);
			}
			return false;
		} else {
			// If the autoload class is not in RazyFramework namespace, load external library from module folder
			$manager = ModuleManager::GetInstance();
			return $manager->loadLibrary($class);
		}
		return false;
	});
}
?>
