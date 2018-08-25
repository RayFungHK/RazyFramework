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
  class DatabaseQuery
  {
  	private $statement;

  	public function __construct($statement)
  	{
  		$this->statement = $statement;
  	}

  	public function fetch($object = null)
  	{
  		if (Database::FETCH_ALL === $object) {
  			return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
  		}
  		if (Database::FETCH_GROUP === $object) {
  			return $this->statement->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
  		}
  		if (Database::FETCH_KEY_PAIR === $object) {
  			return $this->statement->fetchAll(\PDO::FETCH_KEY_PAIR);
  		}

  		if (is_array($object) && count($object)) {
  			foreach ($object as $mapping => $column) {
  				$object[$mapping] = null;
  				$this->statement->bindColumn($column, $object[$mapping]);
  			}
  			$this->statement->fetch(\PDO::FETCH_BOUND);

  			return $object;
  		}

  		if (is_string($object) && class_exists($object)) {
  			return $this->statement->fetch(\PDO::FETCH_CLASS, $object);
  		}

  		if (is_object($object)) {
  			return $this->statement->fetch(\PDO::FETCH_INTO, $object);
  		}

  		return $this->statement->fetch(\PDO::FETCH_ASSOC);
  	}

  	public function affected()
  	{
  		return (isset($this->statement)) ? $this->statement->rowCount() : 0;
  	}
  }
}
