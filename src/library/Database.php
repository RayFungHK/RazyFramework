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
  	private $prepareList  = [];
  	private $queryCount   = 0;

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

  	public function lazy(string $sql, $parameters = [])
  	{
  		return $this->query($sql, $parameters)->fetch();
  	}

  	public function prepare($sql, $parameters = [])
  	{
  		if ($sql instanceof DatabaseTable) {
  			$statement = $this->dba->prepare($sql->getSyntax());
  		} else {
  			$statement = $this->dba->prepare($sql);

  			if (preg_match_all('/:([\w]+)/', $sql, $matches, PREG_SET_ORDER)) {
  				foreach ($matches as $offset => $match) {
  					if (!array_key_exists($match[1], $parameters)) {
  						// Error: No parameters were bound
  						new ThrowError('Database', '3001', 'Parameter [' . $match[1] . '] value is missing.');
  					}

  					$datatype = \PDO::PARAM_STR;
  					if (null === $parameters[$match[1]]) {
  						$datatype = \PDO::PARAM_NULL;
  					} elseif (is_int($parameters[$match[1]])) {
  						$datatype = \PDO::PARAM_INT;
  					} elseif (is_bool($parameters[$match[1]])) {
  						$datatype = \PDO::PARAM_BOOL;
  					}

  					$statement->bindParam($match[0], $parameters[$match[1]], $datatype);
  				}
  			}
  		}

  		$this->prepareList[(int) $statement] = $statement;

  		return $statement;
  	}

  	public function commit($rollback = false)
  	{
  		if (count($this->prepareList)) {
  			$dba->beginTransaction();
  			foreach ($this->prepareList as $statement) {
  				try {
  					$statement->execute();
  				} catch (\PDOException $e) {
  					if ($rollback) {
  						$dba->rollBack();
  					}
  					new ThrowError('Database', '1001', $e->getMessage());
  				}
  			}
  			$dba->commit();
  		}

  		return $this;
  	}

  	public function query($sql, $parameters = [])
  	{
  		++$this->queryCount;
  		$boundParam = [];

  		$statement = $this->prepare($sql, $parameters);

  		try {
  			$statement->execute();
  		} catch (\PDOException $e) {
  			new ThrowError('Database', '1001', $e->getMessage());
  		}

  		unset($this->prepareList[(int) $statement]);

  		return new DatabaseQuery($this->dba, $statement);
  	}

  	public static function Insert(string $tableName, array $dataset)
  	{
  	}
  }
}
