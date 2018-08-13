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
  class DatabaseQuery
  {
  	private $statement;
  	private $dba;

  	public function __construct($dba, $statement)
  	{
  		$this->statement = $statement;
  		$this->dba       = $dba;
  	}

  	public function fetch($object = null)
  	{
  		if (is_array($object) && count($object)) {
  			foreach ($object as $mapping => $column) {
  				$object[$mapping] = null;
  				$this->statement->bindColumn($column, $object[$mapping]);
  			}
  			$this->statement->fetch(\PDO::FETCH_BOUND);

  			return $object;
  		}
  		if (is_string($object) && class_exists($object)) {
  			$this->statement->fetch(\PDO::class, $object);
  		} else {
  			return $this->statement->fetch(\PDO::FETCH_ASSOC);
  		}
  	}

  	public function fetchAll()
  	{
  		return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
  	}

  	public function fetchGroup()
  	{
  		return $this->statement->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
  	}

  	public function fetchPair()
  	{
  		return $this->statement->fetchAll(\PDO::FETCH_KEY_PAIR);
  	}

  	public function lastInsertID()
  	{
  		return (isset($this->dba)) ? $this->dba->lastInsertId() : 0;
  	}

  	public function affectedRow()
  	{
  		return (isset($this->statement)) ? $this->statement->rowCount() : 0;
  	}
  }
}
