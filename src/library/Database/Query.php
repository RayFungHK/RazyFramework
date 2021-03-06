<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework\Database
{
	/**
	 * A wrapper to used to control PDOStatement to fetch the data.
	 */
	class Query
	{
		/**
		 * The PDO Statement object.
		 *
		 * @var \PDOStatement
		 */
		private $statement;

		/**
		 * Query constructor.
		 *
		 * @param string $statement The PDO Statement object
		 */
		public function __construct(\PDOStatement $statement)
		{
			$this->statement = $statement;
		}

		/**
		 * Fetch the data.
		 *
		 * @param mixed $object The fetch method
		 *
		 * @return mixed The result returned by fetch
		 */
		public function fetch($object = null)
		{
			if (\is_string($object)) {
				if ('all' === $object) {
					return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
				}
				if ('group' === $object) {
					return $this->statement->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_GROUP);
				}
				if ('keypair' === $object) {
					return $this->statement->fetchAll(\PDO::FETCH_KEY_PAIR);
				}
			}

			if (\is_array($object) && \count($object)) {
				foreach ($object as $mapping => $column) {
					$object[$mapping] = null;
					$this->statement->bindColumn($column, $object[$mapping]);
				}
				$this->statement->fetch(\PDO::FETCH_BOUND);

				return $object;
			}

			if (\is_string($object) && class_exists($object)) {
				return $this->statement->fetch(\PDO::FETCH_CLASS, $object);
			}

			if (\is_object($object)) {
				return $this->statement->fetch(\PDO::FETCH_INTO, $object);
			}

			return $this->statement->fetch(\PDO::FETCH_ASSOC);
		}

		/**
		 * Get the affected row count.
		 *
		 * @return int The count of affected row
		 */
		public function affected()
		{
			return (isset($this->statement)) ? $this->statement->rowCount() : 0;
		}
	}
}
