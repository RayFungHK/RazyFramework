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
  class ThrowError
  {
  	private static $debug = false;

  	public function __construct($message = null)
  	{
  		if (CLI_MODE) {
  			die('[' . $errorModule . '] #' . $errorCode . ': ' . $message);
  		}
  		ob_get_clean();

  		echo '<html><head>';
  		echo '<style>body {font-family: sans-serif; color: hsl(215, 10%, 30%); background: hsl(215, 10%, 96%);} * {box-sizing: border-box;} body, pre, .debug > * {margin: 0; padding: 1rem;} pre {border-left: 3px solid hsl(215, 10%, 90%); background: hsl(215, 10%, 95%); word-wrap: normal; overflow: auto; line-height: 1.5;} pre > code {display: inline; max-width: none; padding: 0; margin: 0; overflow: visible; line-height: inherit; word-wrap: normal; word-break: normal; white-space: pre;}  .debug {border-bottom: 1px solid hsl(215, 10%, 95%);} .debug:hover {background: hsl(215, 10%, 98%);} .debug-box {background: #fff; border-radius: 3px; box-shadow: 0 2px 2px hsl(215, 10%, 92%);} .caller {margin-bottom: 1rem; color: hsl(215, 10%, 50%)} .stack {background: hsl(215, 10%, 95%); border: 1px solid hsl(215, 10%, 90%); border-radius: 3px; padding: 3px 6px; margin-right: .5rem;} .error-message {color: hsl(215, 10%, 50%); margin-bottom: 1rem;}</style>';
  		echo '</head><body>';
  		echo '<h1>There seems to is something wrong...</h1>';

  		$backtraces = null;
  		if (is_string($message)) {
  			echo '<div class="error-message">' . $message . '</div>';
  		} elseif (is_object($message)) {
  			$implemented = class_implements($message);
  			if (isset($implemented['Throwable'])) {
  				echo '<div class="error-message">' . $message->getMessage() . '</div>';
  				$backtraces = $message->getTrace();
          array_unshift($backtraces, [
  					'file' => $message->getFile(),
  					'line' => $message->getLine(),
          ]);
  			}
  		}

  		if (self::$debug) {
  			if (!$backtraces) {
  				$backtraces = debug_backtrace();
  				$caller     = array_shift($backtraces);
  			}

  			$index = count($backtraces);
  			echo '<div class="debug-box">';
  			foreach ($backtraces as $backtrace) {
  				echo '<div class="debug"><div>';

  				echo '<div class="caller"><span class="stack">' . $index . '</span>';
          if (isset($backtrace['function'])) {
    				// Print class and function caller
    				if (isset($backtrace['class'])) {
    					echo $backtrace['class'] . $backtrace['type'];
    				}
    				echo $backtrace['function'] . '(';
    				$arguments = [];
    				if (isset($backtrace['args'])) {
    					foreach ($backtrace['args'] as $args) {
    						$arguments[] = gettype($args);
    					}
    				}
    				echo implode(', ', $arguments) . ')</div>';
          } else {
            echo '<i>Internal Code</i></div>';
          }

  				echo '<pre><code>';
  				echo 'File: ' . ((isset($backtrace['file'])) ? $backtrace['file'] : $caller['file']) . "\n";
  				echo 'Line: ' . ((isset($backtrace['line'])) ? $backtrace['line'] : $caller['line']) . "\n";
  				if (isset($backtrace['file'])) {
  					$caller = $backtrace;
  				}
  				echo '</code></pre></div></div>';
  				--$index;
  			}
  			echo '</div></body></html>';
  		}

  		ob_end_flush();
  		die();
  	}

  	public static function SetDebugMode(bool $debug)
  	{
  		self::$debug = $debug;
  	}
  }
}
