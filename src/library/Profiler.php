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
  	private static $customStatistic   = [];
  	private $init                     = [];
  	private $samples                  = [];

  	public function __construct()
  	{
  		$this->init = $this->createSample();
  	}

  	public static function AddStatistic(string $parameter, callable $callback)
  	{
  		$parameter = trim($parameter);
  		if (!$parameter) {
  			new ThrowError('Statistic parameter cannot be empty.');
  		}
  		self::$customStatistic[$parameter] = $callback;
  	}

  	public function addSample(string $label = '')
  	{
  		$label = trim($label);
  		if (!$label) {
  			new ThrowError('You should provide the sample with a label.');
  		}

  		if (isset($this->samples[$label])) {
  			new ThrowError('The label ' . $label . ' already exists.');
  		}

  		$this->samples[$label] = $this->createSample();

  		return $this;
  	}

  	public function createSample()
  	{
  		$ru                = getrusage();
  		$defined_functions = get_defined_functions(true);

  		$stats = [
  			'index'             => count($this->samples),
  			'memory_usage'      => memory_get_usage(),
  			'memory_allocated'  => memory_get_usage(true),
  			'output_buffer'     => ob_get_length(),
  			'user_mode_time'    => (int) $ru['ru_utime.tv_sec'] + ((int) $ru['ru_utime.tv_usec'] / 1000000),
  			'system_mode_time'  => (int) $ru['ru_stime.tv_sec'] + ((int) $ru['ru_stime.tv_usec'] / 1000000),
  			'execution_time'    => microtime(true),
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

  	public function reportTo(string $label)
  	{
  		if (!isset($this->samples[$label])) {
  			new ThrowError('Label ' . $label . ' sample was not found.');
  		}

  		$compare = $this->samples[$label];
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

  	public function report(bool $compareWithInit = false, string ...$labels)
  	{
  		if (count($labels)) {
  			if (1 < count($labels)) {
  				$samples = array_intersect_key($this->samples, array_flip($labels));
  				if (!count($samples)) {
  					new ThrowError('There is no profiler step to generate the report.');
  				}
  			} else {
  				$samples = $this->samples;
  			}

  			// If $compareWithInit set to true, put init sample into sample list
  			if ($compareWithInit) {
  				$samples['@init'] = $this->init;
  			}

  			if (count($samples) < 2) {
  				new ThrowError('Not enough samples to generate a report.');
  			}

  			uasort($samples, function ($a, $b) {
  				return ($a['index'] < $b['index']) ? -1 : 1;
  			});

  			$previous = null;
  			foreach ($samples as $label => $stats) {
  				if (!$previous) {
  					$previous = $stats;

  					continue;
  				}
  				$report = [];

  				foreach ($previous as $parameter => $value) {
  					if ('index' === $parameter || !array_key_exists($parameter, $stats)) {
  						continue;
  					}

  					if (is_numeric($value)) {
  						$report[$parameter] = $stats[$parameter] - $value;
  					} elseif (is_array($value)) {
  						$report[$parameter] = array_diff($stats[$parameter], $value);
  					} else {
  						$report[$parameter] = $value;
  					}
  				}

  				$statistics[$label] = $report;
  				$previous           = $stats;
  			}

  			return $statistics;
  		}

  		// If $compareWithInit set to true, put init sample into sample list
  		if (!$compareWithInit && count($this->samples) < 2) {
  			new ThrowError('Not enough samples to generate a report.');
  		}

  		$start   = ($compareWithInit) ? $this->init : reset($this->samples);
  		$compare = (0 === count($this->samples)) ? $this->createSample() : end($this->samples);
  		$report  = [];
  		foreach ($start as $parameter => $value) {
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
