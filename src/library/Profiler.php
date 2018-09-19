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
  class Profiler
  {
  	private static $customStatistic = [];
  	private $init                   = [];
  	private $steps                  = [];

  	public function __construct()
  	{
  		$this->init = $this->createStats();
  	}

  	public static function AddStatistic(string $parameter, callable $callback)
  	{
  		$parameter = trim($parameter);
  		if (!$parameter) {
  			new ThrowError('Statistic parameter cannot be empty.');
  		}
  		self::$customStatistic[$parameter] = $callback;
  	}

  	public function addStep(string $label = null)
  	{
  		$label               = ($label) ? trim($label) : bin2hex(random_bytes(4));
  		$this->steps[$label] = $this->createStats();

  		return $label;
  	}

  	public function createStats()
  	{
  		$ru                = getrusage();
  		$defined_functions = get_defined_functions(true);

  		$stats = [
  			'index'             => count($this->steps),
  			'memory_usage'      => memory_get_usage(),
  			'memory_allocated'  => memory_get_usage(true),
  			'user_mode_time'    => (int) $ru['ru_utime.tv_sec'] + ((int) $ru['ru_utime.tv_usec'] / 1000000),
  			'system_mode_time'  => (int) $ru['ru_stime.tv_sec'] + ((int) $ru['ru_stime.tv_usec'] / 1000000),
  			'defined_functions' => $defined_functions['user'] ?? [],
  			'declared_classes'  => get_declared_classes(),
  		];

  		if (count(self::$customStatistic)) {
  			foreach (self::$customStatistic as $parameter => $callback) {
  				$stats[$parameter] = $callback();
  			}
  		}

  		return $stats;
  	}

  	public function report(string ...$labels)
  	{
  		if (count($labels)) {
  			if (1 === count($labels)) {
  				$steps = $this->steps;
  			} else {
  				$steps = array_intersect_key($this->steps, array_flip($labels));
  				if (!count($steps)) {
  					new ThrowError('There is no profiler step to generate the report.');
  				}
  			}

  			uasort($steps, function ($a, $b) {
  				return ($a['index'] < $b['index']) ? -1 : 1;
  			});

  			$previous = null;
  			foreach ($steps as $label => $stats) {
  				if (!$previous) {
  					$previous = $label;

  					continue;
  				}

  				$report = [];

  				foreach ($this->init as $parameter => $value) {
  					if ('index' === $parameter || !array_key_exists($parameter, $stats)) {
  						continue;
  					}

  					if (is_numeric($value)) {
  						$report[$parameter] = $stats[$parameter] - $value;
  					} elseif (is_array($value)) {
  						$report[$parameter] = array_diff($stats[$parameter], $value);
  					}
  				}

  				$reports['report'][$label] = $report;
  			}

  			return $reports;
  		}

  		$compare = (0 === count($this->steps)) ? $this->createStats() : end($this->steps);
  		$report  = [];
  		foreach ($this->init as $parameter => $value) {
  			if ('index' === $parameter || !array_key_exists($parameter, $compare)) {
  				continue;
  			}

  			if (is_numeric($value)) {
  				$report[$parameter] = $compare[$parameter] - $value;
  			} elseif (is_array($value)) {
  				$report[$parameter] = array_diff($compare[$parameter], $value);
  			}
  		}

  		return $report;
  	}
  }
}
