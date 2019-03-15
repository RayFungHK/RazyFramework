<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

// Skip
// (?:(?:(["`])(?:[^\\"`]++|\\.)*\1)|{\?[^}]+?})(*SKIP)(*FAIL)
// Find AND OR
// (?:(?:(["`])(?:[^\\"`]++|\\.)*\1)|{\?[^}]+?})(*SKIP)(*FAIL)|[,|]

namespace RazyFramework
{
  class DatabaseStatement
  {
  	const REGEX_SKIP             = '(?:(?:(["`\'])(?:[^\\\\"`\']++|\\\\.)*\1)|\((?:[^\\\\}]++|\\\\.)*?\))(*SKIP)(*FAIL)|';
  	const REGEX_EXTRA            = '/^' . self::REGEX_SKIP . '(?:(?:(?:group|order)\s+by|having|window).+)+$/i';
  	const REGEX_SPLIT_WHERE      = '/' . self::REGEX_SKIP . '(\s+where\s+)/i';
  	const REGEX_SPLIT_FROM       = '/^select\s+(.+?)\s+from\s+(.++)$/i';
  	const REGEX_COLUMN_DELIMITER = '/\s*,\s*/';
  	const REGEX_PDO_PARAM        = '/(?:[^\'"`:]++|(?:(["`\'])(?:[^\\\\"`\']++|\\\\.)*\1))(*SKIP)(*FAIL)|:(\w+)/';

  	private static $lastResourceId       = 0;

  	private $dbObject;
  	private $parameters        = [];
  	private $parameterRequired = [];
  	private $isSelectStatement = false;
  	private $whereable         = false;
  	private $cached            = false;
  	private $startRecord       = '';
  	private $fetchLength       = '';
  	private $selectSyntax;
  	private $whereSyntax;
  	private $extraSyntax       = '';
  	private $sql               = '';
  	private $subqueries        = [];

  	public function __construct(Database $dbObject, string $sql = '')
  	{
  		if (null === $dbObject->getAdapter()) {
  			new ThrowError('Database adapter is null or it does not connect to databse.');
  		}

  		if ($sql) {
  			$splittedWhereSyntax = '';
  			if (preg_match(self::REGEX_SPLIT_WHERE, $sql, $matches, PREG_OFFSET_CAPTURE)) {
  				$this->sql           = substr($sql, 0, $matches[0][1]);
  				$splittedWhereSyntax = substr($sql, $matches[0][1] + strlen($matches[0][0]));
  			} else {
  				$this->sql = $sql;
  			}

  			if (preg_match('/^(SELECT|UPDATE|DELETE\s+FROM)\s+/i', $this->sql)) {
  				$this->whereable   = true;
  				if (isset($splittedWhereSyntax)) {
  					$this->whereSyntax = $splittedWhereSyntax;
  				} else {
  					$this->whereSyntax = new WhereSyntaxParser($this);
  				}

  				if (preg_match(self::REGEX_SPLIT_FROM, $this->sql, $matches)) {
  					$columns                 = preg_split(self::REGEX_COLUMN_DELIMITER, $matches[2]);
  					$this->selectSyntax      = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $matches[3];
  					$this->isSelectStatement = true;
  				}
  			}
  		} else {
  			$this->whereSyntax  = new WhereSyntaxParser($this);
  			$this->selectSyntax = new SelectSyntaxParser($this);
  		}

  		$this->dbObject     = $dbObject;
  		$this->resourceId   = self::CreateInstance();
  	}

  	public function where(string $syntax)
  	{
  		if (!$this->whereSyntax instanceof WhereSyntaxParser) {
  			$this->whereSyntax = new WhereSyntaxParser($this);
  		}
  		$syntax            = trim($syntax);
  		$this->whereSyntax->parseSyntax($syntax);
  		$this->cached      = false;

  		return $this;
  	}

  	public function limit(int $start, int $length = 20)
  	{
  		$this->startRecord = max($start, 0);
  		$this->fetchLength = max((int) $length, 5);

  		return $this;
  	}

  	public function extra(string $syntax)
  	{
  		$syntax = trim($syntax);
  		if (!preg_match(self::REGEX_EXTRA, $syntax)) {
  			new ThrowError('Exta syntax only allowed ORDER BY, GROUP BY, HAVING and WINDOW.');
  		}
  		$this->extraSyntax = $syntax;

  		return $this;
  	}

  	public function select(string $syntax, string $selectColumnStatement = '')
  	{
  		if (!$this->selectSyntax instanceof SelectSyntaxParser) {
  			$this->whereSyntax = new SelectSyntaxParser($this);
  		}

  		if (!$selectColumnStatement || !is_string($selectColumnStatement)) {
  			$selectColumnStatement = '*';
  		}

  		$this->selectSyntax->parseSyntax($syntax)->parseColumn($selectColumnStatement);
  		$this->whereable         = true;
  		$this->cached            = false;
  		$this->isSelectStatement = true;

  		return $this;
  	}

  	public function lazy(array $parameters = [])
  	{
  		return $this->dbObject->lazy($this, $parameters);
  	}

  	public function query(array $parameters = [])
  	{
  		return $this->dbObject->query($this, $parameters);
  	}

  	public function prepare(array $parameters = [])
  	{
  		return $this->dbObject->prepare($this, $parameters);
  	}

  	public function setParameter($parameter, $value = null)
  	{
  		if (is_array($parameter)) {
  			foreach ($parameter as $key => $value) {
  				$this->setParameter($key, $value);
  			}
  		} else {
  			$this->parameters[$parameter]        = $value;
  			$this->parameterRequired[$parameter] = Database::PARAM_STRING;
  		}

  		return $this;
  	}

  	public function getParameters()
  	{
  		return $this->parameters;
  	}

  	public function getParameter(string $parameter)
  	{
  		if (isset($this->parameters[$parameter])) {
  			return $this->parameters[$parameter];
  		}

  		return null;
  	}

  	public function assignSubQuery(string $name, self $subquery)
  	{
  		$this->subqueries[$name] = $subquery;

  		return $this;
  	}

  	public function getSubQuery(string $name)
  	{
  		return $this->subqueries[$name] ?? null;
  	}

  	public function getStatement(array $parameters = [])
  	{
  		if ($this->isSelectStatement) {
  			if (!$this->cached) {
  				if ($this->selectSyntax) {
  					$sql = (is_string($this->selectSyntax)) ? $this->selectSyntax : $this->selectSyntax->getStatement();
  					if ($this->whereable && $this->whereSyntax) {
  						$sql .= $this->getWhereStatement();

  						if ($this->extraSyntax) {
  							$sql .= ' ' . $this->extraSyntax;
  						}
  					}

  					if ($this->startRecord > 0) {
  						$sql .= ' LIMIT ' . $this->startRecord . ', ' . $this->fetchLength;
  					}
  					$this->sql = $sql;
  				}
  				$this->cached = true;
  			}
  			$sql = $this->sql;
  		} else {
  			$sql = $this->sql;
  			if ($this->whereable && $this->whereSyntax) {
  				$sql .= $this->getWhereStatement();
  			}
  		}

  		$sql = $this->setParam($sql);

  		return $this->dbObject->getAdapter()->prepare($sql);
  	}

  	public function getResourceId()
  	{
  		return $this->resourceId;
  	}

  	private function getWhereStatement()
  	{
  		$statement = '';
  		if (is_string($this->whereSyntax)) {
  			$statement .= $this->whereSyntax;
  		} else {
  			$whereSyntax = $this->whereSyntax->getStatement();
  			if ($whereSyntax) {
  				$statement .= ' WHERE ' . $whereSyntax;
  			}
  		}

  		return $statement;
  	}

  	private function setParam(string $statement)
  	{
  		return preg_replace_callback(self::REGEX_PDO_PARAM, function ($matches) {
  			if (!isset($this->parameters[$matches[2]])) {
  				return "''";
  			}
  			return "'" . addslashes($this->parameters[$matches[2]]) . "'";
  		}, $statement);
  	}

  	private static function CreateInstance()
  	{
  		return ++self::$lastResourceId;
  	}
  }
}
