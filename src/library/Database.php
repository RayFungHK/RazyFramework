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
  	const DATATYPE_BIT        = 'BIT';
  	const DATATYPE_INT        = 'INT';
  	const DATATYPE_TIME       = 'TIME';
  	const DATATYPE_TIMESTAMP  = 'TIMESTAMP';
  	const DATATYPE_DATETIME   = 'DATETIME';
  	const DATATYPE_CHAR       = 'CHAR';
  	const DATATYPE_VARCHAR    = 'VARCHAR';
  	const DATATYPE_BINARY     = 'BINARY';
  	const DATATYPE_VARBINARY  = 'VARBINARY';
  	const DATATYPE_REAL       = 'REAL';
  	const DATATYPE_DOUBLE     = 'DOUBLE';
  	const DATATYPE_FLOAT      = 'FLOAT';
  	const DATATYPE_DECIMAL    = 'DECIMAL';
  	const DATATYPE_NUMERIC    = 'NUMERIC';
  	const DATATYPE_ENUM       = 'ENUM';
  	const DATATYPE_SET        = 'SET';
  	const DATATYPE_JSON       = 'JSON';
  	const DATATYPE_YEAR       = 'YEAR';
  	const DATATYPE_TINYBLOB   = 'TINYBLOB';
  	const DATATYPE_BLOB       = 'BLOB';
  	const DATATYPE_MEDIUMBLOB = 'MEDIUMBLOB';
  	const DATATYPE_LONGTEXT   = 'LONGTEXT';
  	const DATATYPE_MEDIUMTEXT = 'MEDIUMTEXT';
  	const DATATYPE_TINYTEXT   = 'TINYTEXT';
  	const DATATYPE_TEXT       = 'TEXT';

  	const COLUMN_CUSTOM    = -1;
  	const COLUMN_AUTO_ID   = 0;
  	const COLUMN_TEXT      = 1;
  	const COLUMN_LONG_TEXT = 2;
  	const COLUMN_INT       = 3;
  	const COLUMN_BOOLEAN   = 4;
  	const COLUMN_DECIMAL   = 5;
  	const COLUMN_TIMESTAMP = 6;
  	const COLUMN_DATETIME  = 7;
  	const COLUMN_DATE      = 8;
  	const COLUMN_JSON      = 9;

  	const FETCH_ASSOC    = 0;
  	const FETCH_ALL      = 1;
  	const FETCH_GROUP    = 2;
  	const FETCH_KEY_PAIR = 3;

  	private static $dbConnectionLists = [];

  	private $dba;
  	private $prepareList  = [];
  	private $queried      = 0;
  	private $prepared     = 0;

  	public function __construct(string $connectionName)
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

  	public function lazy($sql, $parameters = [])
  	{
  		return $this->query($sql, $parameters)->fetch();
  	}

  	public function createStatement(string $sql)
  	{
  		return new DatabaseStatement($this, $sql);
  	}

  	public function prepare($sql, $parameters = [])
  	{
  		if ($sql instanceof DatabaseTable) {
  			$dbs = new DatabaseStatement($this, $sql->getSyntax());
  		} elseif ($sql instanceof DatabaseStatement) {
  			$dbs = $sql;
  		} else {
  			$dbs = $this->createStatement($sql);
  		}

      $dbs->setParameter($parameters);

  		$this->prepareList[$dbs->getResourceId()] = $dbs;
  		++$this->prepared;

  		return $dbs;
  	}

  	public function commit($rollback = false)
  	{
  		if (count($this->prepareList)) {
  			$this->dba->beginTransaction();
  			foreach ($this->prepareList as $dbs) {
  				try {
  					$dbs->getStatement()->execute();
  				} catch (\PDOException $e) {
  					if ($rollback) {
  						$this->dba->rollBack();
  					}
  					new ThrowError('Database', '1001', $e->getMessage());
  				}
  			}
  			$this->dba->commit();
  		}

  		return $this;
  	}

  	public function query($sql, $parameters = [])
  	{
  		++$this->queried;
  		$dbs       = $this->prepare($sql, $parameters);
  		$statement = $dbs->getStatement();

  		try {
  			$statement->execute();
  		} catch (\PDOException $e) {
  			new ThrowError('Database', '1002', $e->getMessage());
  		}

  		unset($this->prepareList[$dbs->getResourceId()]);

  		return new DatabaseQuery($statement);
  	}

  	public function createInsertSQL(string $tableName, array $dataset)
  	{
  		$tableName = trim($tableName);
  		if (!$tableName) {
  			new ThrowError('Database', '4003', 'Table name should not be empty');
  		}

  		if (!count($dataset)) {
  			new ThrowError('Database', '4004', 'Dataset should not be empty');
  		}

  		return $this->prepare('INSERT INTO ' . $tableName . ' (' . implode(', ', $dataset) . ') VALUES (:' . implode(", :", $dataset) . ')');
  	}

  	public function createUpdateSQL(string $tableName, array $dataset)
  	{
  		$tableName = trim($tableName);
  		if (!$tableName) {
  			new ThrowError('Database', '4003', 'Table name should not be empty');
  		}

  		if (!count($dataset)) {
  			new ThrowError('Database', '4004', 'Dataset should not be empty');
  		}

  		$updateSet = [];
  		foreach ($dataset as $column) {
  			$updateSet[] = $column . ' = :' . $column;
  		}

  		return $this->prepare('UPDATE ' . $tableName . ' SET ' . implode(', ', $updateSet));
  	}

  	public function lastID()
  	{
  		return $this->dba->lastInsertId();
  	}

  	public function getQueried()
  	{
  		return $this->queried;
  	}

  	public function getPrepared()
  	{
  		return $this->prepared;
  	}

  	public function getAdapter()
  	{
  		return $this->dba;
  	}

  	public function select(string $syntax, $column = '', $subquery = [])
  	{
  		$dbs = new DatabaseStatement($this);
      $dbs->select($syntax, $column, $subquery);

  		return $dbs;
  	}
  }
}
