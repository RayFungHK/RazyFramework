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
	 * A database adapter used to connect the MySQL server.
	 */
	class Adapter
	{
		/**
		 * An array contains Adapter object.
		 *
		 * @var array
		 */
		private static $adapters = [];

		/**
		 * An array contains the queried SQL statement.
		 *
		 * @var array
		 */
		private $queried = [];

		/**
		 * The database adapter resource.
		 *
		 * @var resource
		 */
		private $adapter;

		/**
		 * The support charset and its default collation.
		 *
		 * @var array
		 */
		private $charset = [];

		/**
		 * Set true if the database is connected.
		 *
		 * @var array
		 */
		private $connected = false;

		/**
		 * Adapter constructor.
		 *
		 * @param string $name The name of the adapter
		 */
		public function __construct(string $name = '')
		{
			$name = trim($name);
			if ($name) {
				self::$adapters[$name] = $this;
			}
		}

		/**
		 * Get the existing Adapter by given name or create it instead.
		 *
		 * @param string $name The adapter name
		 *
		 * @return Adapter The Adapter object
		 */
		public static function GetAdapter(string $name)
		{
			$name = trim($name);
			if ($name && isset(self::$adapters[$name])) {
				return self::$adapters[$name];
			}

			return new self($name);
		}

		/**
		 * Start connect to MySQL.
		 *
		 * @param string $host     The hostname or IP of the MySQL server
		 * @param string $username The username
		 * @param string $password The password
		 * @param string $database The database to open
		 *
		 * @return bool Return true if the connect is success
		 */
		public function connect(string $host, string $username, string $password, string $database)
		{
			try {
				$connectionString = 'mysql:host=' . $host . ';dbname=' . $database . ';charset=UTF8';
				$this->adapter    = new \PDO($connectionString, $username, $password, [
					\PDO::ATTR_PERSISTENT => true,
					\PDO::ATTR_TIMEOUT    => 5,
					\PDO::ATTR_ERRMODE    => \PDO::ERRMODE_EXCEPTION,
				]);

				$this->connected = true;

				return true;
			} catch (\PDOException $e) {
				$this->connected = false;

				return false;
			}
			$this->connected = false;

			return false;
		}

		/**
		 * Get the connect status of adapter.
		 *
		 * @return bool Return true if it is connected
		 */
		public function isConnected()
		{
			return $this->connected;
		}

		/**
		 * Execute the statement and return the first result.
		 *
		 * @param mixed $sql        A string of SQL statement or a Statement object
		 * @param array $parameters An array contains the parameter
		 *
		 * @return mixed Return null if no result returned or return an array contains the result
		 */
		public function lazy($sql, array $parameters = [])
		{
			return $this->query($sql, $parameters)->fetch();
		}

		/**
		 * Convert the given object to a Statement object.
		 *
		 * @param mixed $sql A string of statement or a statement object
		 *
		 * @return Statement The Statement object
		 */
		public function prepare($sql = null)
		{
			if ($sql instanceof Table) {
				return new Statement($this, $sql->getSyntax());
			}

			if ($sql instanceof Statement) {
				return $sql;
			}

			if (\is_string($sql)) {
				return new Statement($this, $sql);
			}

			return new Statement($this);
		}

		/**
		 * Execute the SQL statement.
		 *
		 * @param mixed $sql        A string of SQL statement or a Statement object
		 * @param array $parameters An array contains the parameters
		 *
		 * @return Query The Query object
		 */
		public function query($sql, array $parameters = [])
		{
			$dbs       = $this->prepare($sql);
			$sql       = $dbs->assign($parameters)->getSyntax();
			$statement = $this->adapter->prepare($sql);

			$statement->execute();

			$this->queried[] = $sql;

			return new Query($statement);
		}

		/**
		 * Return the last insert id.
		 *
		 * @return int The last insert id
		 */
		public function lastID()
		{
			return $this->adapter->lastInsertId();
		}

		/**
		 * Get the latest executed SQL statement.
		 *
		 * @return string The SQL statement
		 */
		public function getLastQueried()
		{
			return end($this->queried);
		}

		/**
		 * Get a list of the executed SQL statement.
		 *
		 * @return array An array contains the executed SQL statement
		 */
		public function getQueried()
		{
			return $this->queried;
		}

		/**
		 * Get the database adapter resource.
		 *
		 * @return resource The database adapter resource
		 */
		public function getDBAdapter()
		{
			return $this->adapter;
		}

		/**
		 * Create an insert statement.
		 *
		 * @param string $tableName The table name
		 * @param array  $dataset   An array contains the column namd and its value
		 *
		 * @return Statement The Statement object
		 */
		public function insert(string $tableName, array $dataset)
		{
			$tableName = trim($tableName);
			if (!$tableName) {
				throw new ErrorHandler('Table name should not be empty');
			}

			if (!\count($dataset)) {
				throw new ErrorHandler('Dataset should not be empty');
			}

			return $this->prepare('INSERT INTO ' . $tableName . ' (' . implode(', ', $dataset) . ') VALUES (:' . implode(', :', $dataset) . ')');
		}

		/**
		 * Create a update statement.
		 *
		 * @param string $tableName The table name
		 * @param array  $dataset   An array contains the column namd and its value
		 *
		 * @return Statement The Statement object
		 */
		public function update(string $tableName, array $dataset)
		{
			$tableName = trim($tableName);
			if (!$tableName) {
				throw new ErrorHandler('Table name should not be empty');
			}

			if (!\count($dataset)) {
				throw new ErrorHandler('Dataset should not be empty');
			}

			$updateSet = [];
			foreach ($dataset as $column) {
				$updateSet[] = $column . ' = :' . $column;
			}

			return $this->prepare('UPDATE ' . $tableName . ' SET ' . implode(', ', $updateSet));
		}

		/**
		 * Get the support charset list.
		 *
		 * @return array The support charset list
		 */
		public function getCharset()
		{
			if (!\count($this->charset)) {
				$query = $this->query('SHOW CHARACTER SET');
				while ($result = $query->fetch()) {
					$this->charset[$result['Charset']] = [
						'default'   => $result['Default collation'],
						'collation' => [],
					];
				}
			}

			return $this->charset;
		}

		/**
		 * Get the collation list.
		 *
		 * @param string $charset The support charset name
		 *
		 * @return array The collation list
		 */
		public function getCollation(string $charset)
		{
			$charset = strtolower(trim($charset));

			// Get all supported charset from MySQL
			$this->getCharset();

			if (isset($this->charset[$charset])) {
				if (!\count($this->charset[$charset]['collation'])) {
					$query = $this->query("SHOW COLLATION WHERE Charset = '" . $charset . "'");
					while ($result = $query->fetch()) {
						$this->charset[$charset]['collation'][$result['Collation']] = $result['Charset'];
					}
				}

				return $this->charset[$charset]['collation'];
			}

			return [];
		}
	}
}
