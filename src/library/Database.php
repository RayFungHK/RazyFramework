<?php

/*
 * This file is part of RazyFramwork.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework
{
  class Database
  {
  	const DATATYPE_BIT       = 'BIT';
  	const DATATYPE_INT       = 'INT';
  	const DATATYPE_TIME      = 'TIME';
  	const DATATYPE_TIMESTAMP = 'TIMESTAMP';
  	const DATATYPE_DATETIME  = 'DATETIME';
  	const DATATYPE_CHAR      = 'CHAR';
  	const DATATYPE_VARCHAR   = 'VARCHAR';
  	const DATATYPE_BINARY    = 'BINARY';
  	const DATATYPE_VARBINARY = 'VARBINARY';

  	const DATATYPE_REAL    = 'REAL';
  	const DATATYPE_DOUBLE  = 'DOUBLE';
  	const DATATYPE_FLOAT   = 'FLOAT';
  	const DATATYPE_DECIMAL = 'DECIMAL';
  	const DATATYPE_NUMERIC = 'NUMERIC';

  	const DATATYPE_ENUM       = 'ENUM';
  	const DATATYPE_SET        = 'SET';
  	const DATATYPE_JSON       = 'JSON';
  	const DATATYPE_YEAR       = 'YEAR';
  	const DATATYPE_TINYBLOB   = 'TINYBLOB';
  	const DATATYPE_BLOB       = 'BLOB';
  	const DATATYPE_MEDIUMBLOB = 'MEDIUMBLOB';
  	const DATATYPE_LONGTEXT   = 'LONGTEXT';
  	const DATATYPE_TINYTEXT   = 'TINYTEXT';
  	const DATATYPE_TEXT       = 'TEXT';
  	const DATATYPE_MEDIUMTEXT = 'MEDIUMTEXT';

  	private static $dbConnectionLists = [];

  	private $dba;
  	private $tableList  = [];
  	private $queryCount = 0;

  	public function __construct($connectionName)
  	{
  		$connectionName                           = trim($connectionName);
  		self::$dbConnectionLists[$connectionName] = $this;
  	}

  	public static function GetConnection($connectionName)
  	{
  		if (!isset(self::$dbConnectionLists[$connectionName])) {
  			self::$dbConnectionLists[$connectionName] = new self($connectionName);
  		}

  		return self::$dbConnectionLists[$connectionName];
  	}

  	public function connect($host, $username, $password, $database)
  	{
  		try {
  			$connectionString = 'mysql:host=' . $host . ';dbname=' . $database . ';charset=UTF8';
  			$this->dba        = new \PDO($connectionString, $username, $password, [
  				\PDO::ATTR_PERSISTENT => true,
  				\PDO::ATTR_TIMEOUT    => 5,
  				\PDO::ATTR_ERRMODE    => \PDO::ERRMODE_EXCEPTION,
  			]);

  			return true;
  		} catch (\PDOException $e) {
  			return false;
  		}
  	}

  	public function query($sql, $parameters = [])
  	{
  		++$this->queryCount;
  		$boundParam = [];

  		if ($sql instanceof DatabaseTable) {
  			$statement = $this->dba->prepare($sql->getSyntax());
  		} else {
  			$sql = trim($sql);

  			if (preg_match_all('/(@|:)([a-zA-Z0-9_]+)/i', $sql, $matches, PREG_SET_ORDER)) {
  				foreach ($matches as $offset => $match) {
  					if (!array_key_exists($match[2], $parameters)) {
  						// Error: No parameters were bound
  						new ThrowError('Database', '3001', 'Parameter [' . $match[2] . '] value is missing.');
  					}
  					$boundParam[$match[0]] = $parameters[$match[2]];
  				}
  			}

  			$statement = $this->dba->prepare($sql);
  		}

  		try {
  			$statement->execute($boundParam);
  		} catch (\PDOException $e) {
  			new ThrowError('Database', '1001', $e->getMessage());
  		}

  		return new DatabaseQuery($this->dba, $statement);
  	}

  	public function queryReturn($sql, $parameters = [])
  	{
  		$query = $this->query($sql, $parameters);

  		return $query->fetch();
  	}

  	public function insert($table, $dataSet, $columnSpecified = [])
  	{
  		$columnList      = '';
  		$paramList       = '';
  		$columnSpecified = array_flip($columnSpecified);
  		if (count($dataSet)) {
  			foreach ($dataSet as $column => $value) {
  				$isOption = false;
  				if ('!' === $column[0]) {
  					$column   = substr($column, 1);
  					$isOption = true;
  				}
  				if (count($columnSpecified) && !isset($columnSpecified[$column])) {
  					continue;
  				}
  				$columnMapping = ($isOption) ? $value : ':' . $column;
  				$columnList .= ($columnList) ? ', ' . $column : $column;
  				$paramList .= ($paramList) ? ', ' . $columnMapping : $columnMapping;
  			}
  		}

  		return $this->query('INSERT INTO ' . $table . ' (' . $columnList . ') VALUES (' . $paramList . ')', $dataSet);
  	}

  	public function newTable($tableName)
  	{
  		$tableName = trim($tableName);
  		if (!isset($this->tableList[$tableName])) {
  			$this->tableList[$tableName] = new DatabaseTable($this, $tableName);
  		}

  		return $this->tableList[$tableName];
  	}
  }
}
