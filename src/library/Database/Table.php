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
	use \RazyFramework\ErrorHandler;
	
	/**
	 * Used to create a database table.
	 */
	class Table
	{
		/**
		 * The table name.
		 *
		 * @var string
		 */
		private $name = '';

		/**
		 * An array contains the column object.
		 *
		 * @var array
		 */
		private $columns = [];

		/**
		 * The column charset.
		 *
		 * @var string
		 */
		private $charset = 'utf8';

		/**
		 * The column collation.
		 *
		 * @var string
		 */
		private $collation = 'utf8_unicode_ci';

		/**
		 * Table constructor.
		 *
		 * @param string $tableName The table name
		 */
		public function __construct(string $tableName)
		{
			$this->name = $tableName;
		}

		/**
		 * Create a new Column object or return the existing one.
		 *
		 * @param string $columnName The column name
		 *
		 * @return Column The column object
		 */
		public function column(string $columnName)
		{
			$columnName = trim($columnName);
			if (!isset($this->columns[$columnName])) {
				$this->columns[$columnName] = new Column($columnName);
			}

			return $this->columns[$columnName];
		}

		/**
		 * Set the column charset.
		 *
		 * @param string $charset The column charset
		 *
		 * @return self Chainable
		 */
		public function charset(string $charset)
		{
			$charset       = trim($charset);
			if (!preg_match('/\w+^$', $charset)) {
				throw new ErrorHandler($charset . ' is not in a correct character set format.');
			}
			$this->charset = $charset;

			return $this;
		}

		/**
		 * Set the column collation.
		 *
		 * @param string $collation The column collation
		 *
		 * @return self Chainable
		 */
		public function collation(string $collation)
		{
			$collation = trim($collation);
			if ($collation && !preg_match('/^\w+?_\w+$', $collation)) {
				throw new ErrorHandler($collation . ' is not in a correct collation format.');
			}

			$charset = strtok($collation, '_');
			if ($charset !== $this->charset) {
				$this->charset = $charset;
			}

			$this->collation = $collation;

			return $this;
		}

		/**
		 * Generate the CREATE TABLE syntax.
		 *
		 * @return string The CREATE TABLE syntax
		 */
		public function getSyntax()
		{
			$autoColumn = null;
			$indexKey   = [
				'primary'  => [],
				'index'    => [],
				'unique'   => [],
				'fulltext' => [],
				'spatial'  => [],
			];

			$clips = [];
			foreach ($this->columns as $column) {
				$clips[] = $column->getSyntax();
				if ($column->isAuto()) {
					if ($autoColumn) {
						throw new ErrorHandler('The column ' . $column->getName() . ' cannot declare as auto increment that ' . $autoColumn->getName() . ' is already declared.');
					}
					$indexKey['primary'][] = $column->getName();
					$autoColumn            = $column;
				} elseif ($index = $column->getIndex()) {
					if (array_key_exists($index, $indexKey)) {
						$indexKey[$index][] = $column->getName();
					}
				}
			}

			$syntax = 'CREATE TABLE ' . $this->name . ' (';

			// Primary key
			if (count($indexKey['primary'])) {
				$clips[] = 'PRIMARY KEY(`' . implode('`, `', $indexKey['primary']) . '`)';
			}
			unset($indexKey['primary']);

			foreach ($indexKey as $index => $columns) {
				foreach ($columns as $column) {
					$clips[] = strtoupper($index) . '(`' . $column . '`)';
				}
			}

			$syntax .= implode(', ', $clips) . ') ENGINE InnoDB CHARSET=' . $this->charset . ' COLLATE ' . $this->collation . ';';

			return $syntax;
		}
	}
}
