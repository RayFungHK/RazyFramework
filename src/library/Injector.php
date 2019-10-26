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
  /**
   * Inject a wrapper method for any classes.
   */
  trait Injector
  {
  	/**
  	 * Get the wrapper and bind the tunnel by given method name.
  	 *
  	 * @param ?array $methods An array contains the method name
  	 *
  	 * @return Wrapper The wrapper object
  	 */
  	private function wrapper(?array $methods = null)
  	{
  		$wrapper = new Wrapper();
  		if (\is_array($methods)) {
  			foreach ($methods as $method) {
  				if (\is_string($method)) {
  					$wrapper->bindTunnel($method, function () use ($method) {
  						$stack = debug_backtrace(0, 1);
  						$args = $stack[0]['args'];

  						return \call_user_func_array([$this, $method], $args);
  					});
  				}
  			}
  		}

  		return $wrapper;
  	}
  }
}
