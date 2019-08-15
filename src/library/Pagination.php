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
   * Used to generate the pagination.
   */
  class Pagination
  {
  	private static $parser = '';

  	/**
  	 * Item per page.
  	 *
  	 * @var int
  	 */
  	private $itemPerPage = 20;

  	/**
  	 * Maximum page tag list on the screen.
  	 *
  	 * @var int
  	 */
  	private $displayPageCount = 7;

  	/**
  	 * The URL include the query string, except the page parameter.
  	 *
  	 * @var string
  	 */
  	private $baseURL = '';

  	/**
  	 * An array conatins the query string parameter.
  	 *
  	 * @var array
  	 */
  	private $queryString = [];

  	/**
  	 * The parameter name of the page in query string.
  	 *
  	 * @var string
  	 */
  	private $pageParamName = 'page';

  	/**
  	 * Pagination constructor.
  	 */
  	public function __construct()
  	{
  		parse_str($_SERVER['QUERY_STRING'], $this->queryString);
  		$this->baseURL = SCRIPT_URL;
  	}

  	/**
  	 * Set the page parameter name in the query string.
  	 *
  	 * @param string $name The page parameter name
  	 *
  	 * @return self Chainable
  	 */
  	public function parameter(string $name)
  	{
  		$name = trim($name);
  		if (!$name) {
  			throw new ErrorHandler('The page parameter name cannot be empty.');
  		}
  		$this->pageParamName = $name;

  		return $this;
  	}

  	/**
  	 * Set the item count per page.
  	 *
  	 * @param int $itemPerPage The item count per page
  	 *
  	 * @return self Chainable
  	 */
  	public function itemPerPage(int $itemPerPage)
  	{
  		$this->itemPerPage = max($itemPerPage, 5);

  		return $this;
  	}

  	/**
  	 * The maximum page tag display on screen.
  	 *
  	 * @param int $displayPageCount The maximum page tag count
  	 *
  	 * @return self Chainable
  	 */
  	public function displayPageCount(int $displayPageCount)
  	{
  		$this->displayPageCount = max($displayPageCount, 5);

  		return $this;
  	}

  	/**
  	 * Generate the pagination parameter.
  	 *
  	 * @param int $totalRecord The total record
  	 *
  	 * @return array An array contains the pagination parameter
  	 */
  	public function generate(int $totalRecord = 0)
  	{
  		$queryString = $this->queryString;
  		$currentPage = (int) ($queryString[$this->pageParamName] ?? 1);
  		unset($queryString[$this->pageParamName]);

  		$totalRecord = (int) $totalRecord;

  		$maxPage = ceil($totalRecord / $this->itemPerPage);

  		$range = ceil(($this->displayPageCount - 1) / 2);

  		if ($maxPage > 1) {
  			$firstPageTag = max($currentPage - $range, 1);
  			if ($maxPage < $this->displayPageCount) {
  				$firstPageTag = 1;
  				$lastPageTag  = $maxPage;
  			} else {
  				// Get the last page
  				$lastPageTag  = min($maxPage, $firstPageTag + ($this->displayPageCount - 1));
  			}

  			$result      = [
  				'first'        => $firstPageTag,
  				'last'         => $lastPageTag,
  				'current'      => $currentPage,
  				'max'          => $maxPage,
  				'base_url'     => $this->baseURL,
  				'query_string' => $queryString,
  				'tags'         => [],
  			];

  			for (; $firstPageTag <= $lastPageTag; ++$firstPageTag) {
  				$queryString['page'] = $firstPageTag;

  				$result['tags'][] = [
  					'page' => $firstPageTag,
  					'url'  => $this->baseURL . '?' . http_build_query($queryString),
  				];
  			}

  			return $result;
  		}

  		return null;
  	}
  }
}
