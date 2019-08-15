<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework {
	/**
	 * ErrorHandler class.
	 */
	class ErrorHandler extends \Exception
	{
		/**
		 * Display HTML throw message.
		 *
		 * @var int
		 */
		const MODE_HTML = 0;

		/**
		 * Display Text only throw message.
		 *
		 * @var int
		 */
		const MODE_CLI = 1;
		/**
		 * Display JSON object throw message.
		 *
		 * @var int
		 */
		const MODE_JSON = 2;

		/**
		 * The heading display on throw message.
		 *
		 * @var string
		 */
		private $heading = '';

		/**
		 * Debug mode.
		 *
		 * @var bool
		 */
		private static $debug = false;

		/**
		 * The display method of throw message.
		 *
		 * @var int
		 */
		private static $mode = self::MODE_HTML;

		/**
		 * ErrorHandler constructor.
		 *
		 * @param string $message    The error message
		 * @param int    $statusCode The response status code
		 * @param string $heading    The heading display in messgae
		 */
		public function __construct(string $message, int $statusCode = 400, string $heading = 'There seems to is something wrong...')
		{
			$this->heading = $heading;
			parent::__construct($message, $statusCode);
			self::ShowException($this);
		}

		/**
		 * Get the heading.
		 *
		 * @return [type] [description]
		 */
		public function getHeading()
		{
			return $this->heading;
		}

		/**
		 * Display 404 Not Found error page.
		 */
		public static function Show404()
		{
			new self('Page Not Found', 404);
		}

		/**
		 * Display the exception via ErrorHandler.
		 *
		 * @param \Throwable $exception The throwable object, such as Error or Exception
		 */
		public static function ShowException(\Throwable $exception)
		{
			// Get the top stack exception
			while ($previous = $exception->getPrevious()) {
				$exception = $previous;
			}

			ob_get_clean();
			// Load the error page template file, if the template file not found, use 'any.html' instead
			$errorTemplateFile = SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'error_handling' . \DIRECTORY_SEPARATOR . 'any.html';
			if (is_file(SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'error_handling' . \DIRECTORY_SEPARATOR . $exception->getCode() . '.html')) {
				$errorTemplateFile = SYSTEM_ROOT . \DIRECTORY_SEPARATOR . 'error_handling' . \DIRECTORY_SEPARATOR . $exception->getCode() . '.html';
			}
			$content = file_get_contents($errorTemplateFile);

			// If the debug mode is on, display the error detail such as file name, line and its backtrace detail
			$content = self::ReplaceBlock($content, 'debug', function ($matches) {
				if (self::$debug) {
					return $matches[1];
				}

				return '';
			});

			// Display the full list of stack trace
			$content = self::ReplaceBlock($content, 'backtrace', function ($matches) use ($exception) {
				$backtraceContent = '';
				$stacktraceRow = $matches[1];

				$stacktrace = explode("\n", $exception->getTraceAsString());
				$stacktrace = array_reverse($stacktrace);
				array_shift($stacktrace);
				array_pop($stacktrace);

				$index = 0;
				foreach ($stacktrace as $trace) {
					preg_match('/^#\d+ (.+)$/', $trace, $matches);
					$backtraceContent .= self::ReplaceTag($stacktraceRow, [
						'index' => $index++,
						'stack' => $matches[1],
					]);
				}

				return $backtraceContent;
			});

			// Replace the parameter tag outside the backtrace block
			$content = self::ReplaceTag($content, [
				'file'    => $exception->getFile(),
				'line'    => $exception->getLine(),
				'message' => $exception->getMessage(),
				'heading' => ($exception instanceof self) ? $exception->getHeading() : 'There seems to is something wrong...',
			]);

			if (CLI_MODE) {
				echo $content;
				exit;
			}

			// Set the status code
			http_response_code(is_numeric($exception->getCode()) ? $exception->getCode() : 400);

			// Output the exception message
			echo $content;

			if (ob_get_length()) {
				ob_end_flush();
			}
			exit;
		}

		/**
		 * Enable/Disable the debug mode.
		 *
		 * @param bool $debug Set true to enable the debug mode
		 */
		public static function SetDebug(bool $debug)
		{
			self::$debug = $debug;
		}

		/**
		 * Create the arguments list.
		 *
		 * @param array $arguments The arguments list determine to convert as a string
		 *
		 * @return string A string of arguments
		 */
		private function getArguments(array $arguments)
		{
			$args = [];
			foreach ($arguments as $arg) {
				if (is_object($arg)) {
					$args[] = 'Object(' . get_class($arg) . ')';
				} else {
					$type = gettype($arg);
					if ('string' === $type) {
						$arg    = (strlen($arg) > 15) ? substr($arg, 0, 15) . '...' : $arg;
						$args[] = "'" . $arg . "'";
					} elseif ('array' === $type) {
						$args[] = 'Array';
					} else {
						$args[] = $arg;
					}
				}
			}

			return (count($args)) ? '<span>' . implode('</span>, <span>', $args) . '</span>' : '';
		}

		/**
		 * Replace the block and return the replaced content.
		 *
		 * @param string        $content   The content going to replace
		 * @param string        $blockName Block name
		 * @param null|callable $callback  The callable object to process matched content
		 *
		 * @return string The replaced content
		 */
		private static function ReplaceBlock(string $content, string $blockName, callable $callback = null)
		{
			return preg_replace_callback('/{->>' . $blockName . '}(.+?){<<-' . $blockName . '}/s', function ($matches) use ($callback) {
				if ($callback) {
					return $callback($matches);
				}

				return '';
			}, $content);
		}

		/**
		 * Replace the paramater tag.
		 *
		 * @param string $content     The content going to replace
		 * @param array  $replacement An array of replacement
		 *
		 * @return string The replaced content
		 */
		private static function ReplaceTag(string $content, array $replacement = [])
		{
			return preg_replace_callback('/{\$(\w+)}/i', function ($matches) use ($replacement) {
				if (array_key_exists($matches[1], $replacement)) {
					return $replacement[$matches[1]];
				}

				return $matches[0];
			}, $content);
		}
	}
}
