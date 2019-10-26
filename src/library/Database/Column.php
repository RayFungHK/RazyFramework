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
	use RazyFramework\ErrorHandler;

	/**
	 * Contains the column parameter and its data type.
	 */
	class Column
	{
		/**
		 * Specify the column is an auto increment column.
		 *
		 * @var bool
		 */
		private $autoIncrement = false;

		/**
		 * The column data type.
		 *
		 * @var string
		 */
		private $type = 'VARCHAR';

		/**
		 * The column indexing type.
		 *
		 * @var string
		 */
		private $index = '';

		/**
		 * The column length.
		 *
		 * @var string
		 */
		private $length = '255';

		/**
		 * The column NULL or NOT NULL setting. Set true for NULL.
		 *
		 * @var bool
		 */
		private $nullable = false;

		/**
		 * The column default value.
		 *
		 * @var string
		 */
		private $defaultValue = '';

		/**
		 * Enable to give the default value current_timestamp().
		 *
		 * @var bool
		 */
		private $currentTimestamp = false;

		/**
		 * The column charset.
		 *
		 * @var string
		 */
		private $charset = '';

		/**
		 * The column collation.
		 *
		 * @var string
		 */
		private $collation = '';

		/**
		 * The column name.
		 *
		 * @var string
		 */
		private $name = '';

		/**
		 * The table name.
		 *
		 * @var string
		 */
		private $table = '';

		/**
		 * The specified column used to add column after.
		 *
		 * @var string
		 */
		private $after = '';

		/**
		 * Column constructor.
		 *
		 * @param string $columnName The column name
		 * @param string $tableName  The table name
		 */
		public function __construct(string $columnName, string $tableName = '')
		{
			$columnName = trim($columnName);
			if (!preg_match('/^(\`(?:[\x00-\x5B\x5D-\x5F\x61-x7F]++|\\\\[\\\\\`])+\`|[a-z]\w*)$/', $columnName)) {
				throw new ErrorHandler('The column name ' . $columnName . ' is not in a correct format,');
			}
			$this->name  = $columnName;
			$this->table = trim($tableName);
		}

		/**
		 * Set the column data type.
		 *
		 * @param string $type The column data type
		 *
		 * @return self Chainable
		 */
		public function type(string $type)
		{
			$type = strtolower(trim($type));
			if (!$type) {
				throw new ErrorHandler('The column data type cannot be empty.');
			}

			$this->defaultValue = '';
			if ('auto_id' === $type || 'ai' === $type || 'auto_increment' === $type) {
				$this->type          = 'INT';
				$this->autoIncrement = true;
				$this->index         = 'primary';
				$this->length        = '8';
				$this->defaultValue  = '0';
			} elseif ('text' === $type) {
				$this->type   = 'VARCHAR';
				$this->length = '255';
			} elseif ('long_text' === $type) {
				$this->type   = 'LONGTEXT';
				$this->length = '';
			} elseif ('int' === $type) {
				$this->type         = 'INT';
				$this->length       = '8';
				$this->defaultValue = '0';
			} elseif ('bool' === $type || 'boolean' === $type) {
				$this->type         = 'TINYINT';
				$this->length       = '1';
				$this->defaultValue = '0';
			} elseif ('decimal' === $type || 'float' === $type) {
				$this->type         = 'DECIMAL';
				$this->length       = '8,2';
				$this->defaultValue = '0';
			} elseif ('timestamp' === $type) {
				$this->type         = 'TIMESTAMP';
				$this->defaultValue = null;
				$this->nullable     = true;
			} elseif ('datetime' === $type) {
				$this->type         = 'DATETIME';
				$this->defaultValue = null;
				$this->nullable     = true;
			} elseif ('date' === $type) {
				$this->type         = 'DATE';
				$this->defaultValue = null;
				$this->nullable     = true;
			} elseif ('json' === $type) {
				$this->type         = 'JSON';
				$this->defaultValue = '{}';
				$this->nullable     = true;
			} else {
				$this->type = strtoupper($type);
			}

			return $this;
		}

		/**
		 * Set the default value.
		 *
		 * @param null|string $value The default value
		 *
		 * @return self Chainable
		 */
		public function defaultValue(?string $value)
		{
			$this->defaultValue = $value;

			return $this;
		}

		/**
		 * Set column data type.
		 *
		 * @param string $type The column data type
		 *
		 * @return self Chainable
		 */
		public function index(string $type)
		{
			$type = strtolower(trim($type));
			if (!preg_match('/^primary|index|unique|fulltext|spatial$/', $type)) {
				$this->index = '';
			} else {
				$this->type = $type;
			}

			return $this;
		}

		/**
		 * Set the column is auto increment or not.
		 *
		 * @param bool $enable Set true to set the column becomes auto increment
		 *
		 * @return self Chainable
		 */
		public function autoIncrement(bool $enable = true)
		{
			$this->autoIncrement = $enable;

			return $this;
		}

		/**
		 * Set the column length.
		 *
		 * @param string $length the length of column
		 *
		 * @return self Chainable
		 */
		public function length(string $length)
		{
			$this->length = trim($length);

			return $this;
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
			$charset = trim($charset);
			if ($charset && !preg_match('/\w+^$', $charset)) {
				throw new ErrorHandler($charset . ' is not in a correct character set format.');
			}
			$this->charset = $charset;

			return $this;
		}

		/**
		 * Specify the timestamp column using the default value current_timestamp().
		 *
		 * @param bool $enable Set true to use current_timestamp()
		 *
		 * @return self Chainable
		 */
		public function current(bool $enable = true)
		{
			$this->currentTimestamp = $enable;

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
			if ($collation && $this->charset) {
				$charset = strtok($collation, '_');
				if ($charset !== $this->charset) {
					$collation = '';
				}
			}

			if ($collation && !preg_match('/^\w+?_\w+$', $collation)) {
				throw new ErrorHandler($collation . ' is not in a correct collation format.');
			}

			$this->collation = $collation;

			return $this;
		}

		/**
		 * Set the column is allow null or not.
		 *
		 * @param bool $enable Enable to allow the columns store null value
		 *
		 * @return self Chainable
		 */
		public function nullable(bool $enable = true)
		{
			$this->nullable = $enable;

			return $this;
		}

		/**
		 * Alter table add column after the specified column.
		 *
		 * @param string $columnName The column name
		 *
		 * @return self Chainable
		 */
		public function after(string $columnName)
		{
			$columnName = trim($columnName);
			if (!preg_match('/^(\`(?:[\x00-\x5B\x5D-\x5F\x61-x7F]++|\\\\[\\\\\`])+\`|[a-z]\w*)$/', $columnName)) {
				throw new ErrorHandler('The after column name ' . $columnName . ' is not in a correct format,');
			}
			$this->after = $columnName;

			return $this;
		}

		/**
		 * Return the alter table add syntax.
		 *
		 * @return string The SQL Syntax
		 */
		public function getAddSyntax()
		{
			if ($this->table) {
				return 'ALTER TABLE ' . $this->table . ' ADD ' . $this->getSyntax() . (($this->after) ? ' AFTER ' . $this->after : '');
			}

			return '';
		}

		/**
		 * Return the alter table drop syntax.
		 *
		 * @return string The SQL Syntax
		 */
		public function getDropSyntax()
		{
			if ($this->table) {
				return 'ALTER TABLE ' . $this->table . ' DROP ' . $this->name;
			}

			return '';
		}

		/**
		 * Get the column syntax.
		 *
		 * @return string The column syntax
		 */
		public function getSyntax()
		{
			$syntax = '`' . $this->name . '`';

			if (\strlen($this->length)) {
				if (!preg_match('/^(BIT|BOOL(EAN)?|DATE(TIME)?|TIMESTAMP|(TINY|MEDIUM)?BLOB|TEXT|GEOMETRY|JSON)$/i', $this->type)) {
					if (preg_match('/^(REAL|DOUBLE|FLOAT|DEC(IMAL)?|NUMERIC|FIXED)$/i', $this->type)) {
						list($integer, $decimal) = explode(',', $this->length);
						$integer                 = (int) $integer;
						$decimal                 = (int) $decimal;
						if ($decimal >= $integer) {
							$decimal = 0;
						}
						$this->length = max($integer, 1) . ',' . max($decimal, 0);
					} else {
						$this->length = max((int) $this->length, 0);
						if ('YEAR' === $this->type) {
							if (2 !== $this->type && 4 !== $this->type) {
								$this->type = 4;
							}
						}
					}
				} else {
					$this->length = '';
				}
			}

			$syntax .= ' ' . $this->type . (($this->length) ? '(' . $this->length . ')' : '');
			if ($this->autoIncrement) {
				$syntax .= ' NOT NULL AUTO_INCREMENT';
			} else {
				$syntax .= ($this->nullable) ? ' NULL' : ' NOT NULL';

				if ('TIMESTAMP' === $this->type) {
					$syntax .= ' DEFAULT ' . (($this->currentTimestamp) ? 'CURRENT_TIMESTAMP()' : 'NULL');
				} elseif ($this->nullable || null === $this->defaultValue) {
					$syntax .= ' DEFAULT NULL';
				} elseif (null !== $this->defaultValue) {
					$syntax .= " DEFAULT '" . $this->defaultValue . "'";
				}
			}

			if (preg_match('/^TEXT|CHAR|VARCHAR$/', $this->type)) {
				if ($this->charset) {
					$syntax .= ' CHARACTER SET ' . $this->charset;
				}

				if ($this->collation) {
					$syntax .= ' COLLATE ' . $this->collation;
				}
			}

			return $syntax;
		}

		/**
		 * Get the column index.
		 *
		 * @return string The column index
		 */
		public function getIndex()
		{
			return $this->index;
		}

		/**
		 * Get the column name.
		 *
		 * @return string The column name
		 */
		public function getName()
		{
			return $this->name;
		}

		/**
		 * Check the column is auto increment.
		 *
		 * @return bool Return true if the column is auto increment
		 */
		public function isAuto()
		{
			return $this->autoIncrement;
		}
	}
}
