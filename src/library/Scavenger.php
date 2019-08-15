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
   * It used to handle all shutdown handling when PHP script end.
   */
  class Scavenger
  {
  	use \RazyFramework\Injector;

  	/**
  	 * The Scavenger instance.
  	 *
  	 * @var Scavenger
  	 */
  	private static $instance;

  	/**
  	 * An array contains the wrapper.
  	 *
  	 * @var array
  	 */
  	private $wrappers = [];

  	/**
  	 * If it is true means the wrapper has ejected.
  	 *
  	 * @var bool
  	 */
  	private $ejected = false;

  	/**
  	 * Scavenger constructor.
  	 */
  	public function __construct()
  	{
  		if (self::$instance) {
  			throw new ErrorHandler('Allow one Scavenger instance.');
  		}

  		self::$instance = $this;
  	}

  	/**
  	 * Get the Scavenger instance.
  	 *
  	 * @return Scavenger The Scavenger instance
  	 */
  	public static function GetWorker()
  	{
  		if (!self::$instance) {
  			new self();
  		}

  		return self::$instance;
  	}

  	/**
  	 * Eject a Wrapper object, only allowed to eject one time.
  	 *
  	 * @return Wrapper The Wrapper object
  	 */
  	public function eject()
  	{
  		if ($this->ejected) {
  			return null;
  		}
  		$wrapper       = $this->wrapper(['start']);
  		$this->ejected = true;

  		return $wrapper;
  	}

  	/**
  	 * Register a wrapper to execute in shutdown stage.
  	 *
  	 * @param Wrapper $wrapper The Wrapper object
  	 *
  	 * @return self Chainable
  	 */
  	public function register(Wrapper $wrapper)
  	{
  		$hash                  = spl_object_hash($wrapper);
  		$this->wrappers[$hash] = $wrapper;

  		return $this;
  	}

  	/**
  	 * Start to execute all wrapper tunnel.
  	 *
  	 * @return self Chainable
  	 */
  	private function start()
  	{
  		foreach ($this->wrappers as $wrapper) {
  			$wrapper->walk(function ($method, $callback) {
  				$callback();
  			});
  		}

  		return $this;
  	}
  }
}
