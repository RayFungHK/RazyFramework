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
  class Pagination
  {
  	private static $parser = '';

  	private $itemPrePage     = 20;
  	private $currentPage     = 0;
  	private $maxDisplay      = 7;
  	private $baseURL         = '';
  	private $queryString     = '';

  	public function __construct($settings = [])
  	{
  		parse_str($_SERVER['QUERY_STRING'], $this->queryString);

  		$pageParam         = (isset($settings['page_param'])) ? $settings['page_param'] : 'page';
  		$https             = (!empty($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS']);
  		$sp                = $_SERVER['SERVER_PROTOCOL'];
  		$protocol          = strtolower(substr($sp, 0, strpos($sp, '/')) . (($https) ? 's' : ''));
  		$this->baseURL     = $protocol . '://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
  		$this->itemPerPage = (isset($settings['item_per_page'])) ? min($settings['item_per_page'], 5) : 20;
  		$this->currentPage = max((isset($this->queryString[$pageParam])) ? (int) $this->queryString[$pageParam] : 1, 1);

  		unset($this->queryString['page']);
  	}

  	public static function SetParser(callable $callback)
  	{
  		self::$parser = $callback;
  	}

  	public function parse($totalRecord = 0, $getsource = false)
  	{
  		$totalRecord = (int) $totalRecord;
  		$maxPage     = ceil($totalRecord / $this->itemPerPage);
  		$range       = ceil(($this->maxDisplay - 1) / 2);

  		if ($maxPage > 1) {
  			$firstPageTag = max($this->currentPage - $range, 1);
  			if ($maxPage < $this->maxDisplay) {
  				$firstPageTag = 1;
  				$lastPageTag  = $maxPage;
  			} else {
  				// Get the last page
  				$lastPageTag  = min($maxPage, $firstPageTag + ($this->maxDisplay - 1));
  			}

  			$result = [
  				'start'        => $firstPageTag,
  				'end'          => $lastPageTag,
  				'current'      => $this->currentPage,
  				'max'          => $maxPage,
  				'base_url'     => $this->baseURL,
  				'query_string' => $queryString,
  				'tags'         => [],
  			];

  			$queryString = $this->queryString;
  			for (; $firstPageTag <= $lastPageTag; ++$firstPageTag) {
  				$queryString['page'] = $firstPageTag;

  				$result['tags'][] = [
  					'page' => $firstPageTag,
  					'url'  => $this->baseURL . '?' . http_build_query($queryString),
  				];
  			}

  			if (is_callable(self::$parser) && !$getsource) {
  				return \Closure::bind(self::$parser, (object) $result);
  			}

  			return $result;
  		}

  		return null;
  	}
  }
}
