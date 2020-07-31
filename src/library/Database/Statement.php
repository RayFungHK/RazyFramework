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
	use RazyFramework\Database\SyntaxParser\TableJoin;
	use RazyFramework\Database\SyntaxParser\Where;
	use RazyFramework\RegexHelper;

	/**
	 * A SQL Statement object provides chainable operation.
	 */
	class Statement
	{
		/**
		 * The Adapter object.
		 *
		 * @var Adapter
		 */
		private $adapter;

		/**
		 * An array contains the assigned parameters.
		 *
		 * @var array
		 */
		private $parameters = [];

		/**
		 * The start row of the records.
		 *
		 * @var int
		 */
		private $startRecord = 0;

		/**
		 * The row count will be fetched.
		 *
		 * @var int
		 */
		private $fetchLength = 0;

		/**
		 * The TableJoin-Syntax object.
		 *
		 * @var \SyntaxParser\TableJoin
		 */
		private $tableJoinSyntax;

		/**
		 * The Where-Syntax object for "WHERE" syntax.
		 *
		 * @var \SyntaxParser\Where
		 */
		private $whereSyntax;

		/**
		 * The Where-Syntax object for "HAVING" syntax.
		 *
		 * @var \SyntaxParser\Where
		 */
		private $havingSyntax;

		/**
		 * An array contains the columns to be selected to fetch.
		 *
		 * @var array
		 */
		private $columns = [];

		/**
		 * An array contains the order by syntax.
		 *
		 * @var array
		 */
		private $orderby = [];

		/**
		 * An array contains the group by column.
		 *
		 * @var array
		 */
		private $groupby = [];

		/**
		 * The SQL query type, such as SELECT, INSERT, UPDATE, DELETE.
		 *
		 * @var string
		 */
		private $queryType = '';

		/**
		 * The raw SQL statement.
		 *
		 * @var string
		 */
		private $sql = '';

		/**
		 * Statement constructor.
		 *
		 * @param Adapter $adapter The Adapter object
		 * @param string  $sql     The SQL statement
		 */
		public function __construct(Adapter $adapter, string $sql = '')
		{
			$sql = trim($sql);
			if ($sql) {
				if (preg_match('/^(SELECT|UPDATE|DELETE\s+FROM)\s+/i', $sql, $matches)) {
					if (!$regex = RegexHelper::GetCache('statement-split')) {
						$regex = new RegexHelper('\s+(?:where|group\s+by|having|order\s+by)\s+', 'statement-split');
						$regex->exclude(RegexHelper::EXCLUDE_ALL_QUOTES);
					}

					$clips = $regex->split($sql, RegexHelper::SPLIT_DELIMITER);
					$sql   = array_shift($clips);
					foreach (array_chunk($clips, 2) as $pair) {
						$pair[0] = strtok(trim($pair[0]), ' ');
						if ('where' === $pair[0]) {
							$this->whereSyntax = $pair[1];
						} elseif ('having' === $pair[0]) {
							$this->havingSyntax = $pair[1];
						} elseif ('order' === $pair[0]) {
							$this->orderby = $this->parseColumnSyntax($pair[1]);
						} elseif ('group' === $pair[0]) {
							$this->groupby = $this->parseColumnSyntax($pair[1]);
						}
					}

					$this->queryType = strtok(trim(strtolower($matches[1])), ' ');

					if ('select' === $this->queryType) {
						if (preg_match('/^select\s+(.+?)\s+from\s+(.+)$/i', $sql, $matches)) {
							$this->columns         = $this->parseColumnSyntax($matches[1]);
							$this->tableJoinSyntax = $matches[2];
						} else {
							$this->tableJoinSyntax = new TableJoin();
						}
					} else {
						$this->sql = $sql;
					}
				} else {
					$this->sql = $sql;
				}
			} else {
				$this->whereSyntax     = new Where();
				$this->tableJoinSyntax = new TableJoin();
			}

			$this->adapter = $adapter;
			$this->columns = ['*'];
		}

		/**
		 * Generate the where syntax by the given Where-Syntax.
		 *
		 * @param string $syntax The Where-Syntax
		 *
		 * @return self Chainable
		 */
		public function where(string $syntax)
		{
			if (!$this->whereSyntax instanceof Where) {
				$this->whereSyntax = new Where();
			}
			$this->whereSyntax->parseSyntax(trim($syntax));

			return $this;
		}

		/**
		 * Generate the limit syntax.
		 *
		 * @param int $start  The start row
		 * @param int $length The number of row will be fetched
		 *
		 * @return self Chainable
		 */
		public function limit(int $start, int $length = 20)
		{
			$this->startRecord = max($start, 0);
			$this->fetchLength = max((int) $length, 1);

			return $this;
		}

		/**
		 * Generate the group syntax.
		 *
		 * @param  mixed A string of the column or an array contains the column
		 * @param mixed $columns
		 *
		 * @return self Chainable
		 */
		public function group($columns)
		{
			if (is_scalar($columns)) {
				if (!$regex = RegexHelper::GetCache('statement-split-comma')) {
					$regex = new RegexHelper('\s*,\s*', 'statement-split-comma');
					$regex->exclude(RegexHelper::EXCLUDE_ALL_QUOTES);
				}
				$columns = $regex->split($columns);
			} elseif (!\is_array($columns)) {
				throw new ErrorHandler('The given value is not a column or not contains column syntax.');
			}

			$this->groupby = [];
			foreach ($columns as &$column) {
				$this->groupby[] = '`' . trim($column) . '`';
			}

			return $this;
		}

		/**
		 * Generate the order by syntax.
		 *
		 * @param mixed $syntax "order by" syntax or an array contains the "order by" syntax
		 *
		 * @return self Chainable
		 */
		public function order($syntax)
		{
			if (is_scalar($syntax)) {
				if (!$regex = RegexHelper::GetCache('statement-split-comma')) {
					$regex = new RegexHelper('\s*,\s*', 'statement-split-comma');
					$regex->exclude(RegexHelper::EXCLUDE_ALL_QUOTES);
				}

				$syntax = $regex->split($syntax);
			} elseif (!\is_array($syntax)) {
				throw new ErrorHandler('The given value is a column or contains column syntax.');
			}

			$this->orderby = [];
			foreach ($syntax as $stx) {
				if (\is_string($stx)) {
					$stx = trim($stx);
					if ($stx) {
						if ('>' === $stx[0]) {
							$stx = trim(substr($stx, 1)) . ' DESC';
						} else {
							if ('<' === $stx[0]) {
								$stx = trim(substr($stx, 1));
							}
							$stx .= ' ASC';
						}
						$this->orderby[] = $stx;
					}
				} else {
					throw new ErrorHandler('The given object is not a syntax.');
				}
			}

			return $this;
		}

		/**
		 * Generate the having syntax by the given Where-Syntax.
		 *
		 * @param string $syntax The Where-Syntax
		 *
		 * @return self Chainable
		 */
		public function having(string $syntax)
		{
			if (!$this->havingSyntax instanceof Where) {
				$this->havingSyntax = new Where();
			}
			$this->havingSyntax->parseSyntax(trim($syntax));

			return $this;
		}

		/**
		 * Generate the select syntax.
		 *
		 * @param  mixed A string of the column or an array contains the column
		 * @param mixed $columns
		 *
		 * @return self Chainable
		 */
		public function select($columns)
		{
			if (!$regex = RegexHelper::GetCache('statement-split-comma')) {
				$regex = new RegexHelper('\s*,\s*', 'statement-split-comma');
				$regex->exclude(RegexHelper::EXCLUDE_ALL_QUOTES);
			}

			if (is_scalar($columns)) {
				$columns = $regex->split($columns);
			} elseif (!\is_array($columns)) {
				throw new ErrorHandler('The given value is not a column or not contains column syntax.');
			}

			$this->columns = [];
			foreach ($columns as &$column) {
				$column = trim($column);
				$this->columns[] = (preg_match('/^\w+$/', $column)) ? '`' . $column . '`' : $column;
			}

			return $this;
		}

		/**
		 * Generate the from table join syntax by the given Where-Syntax.
		 *
		 * @param string $syntax The Where-Syntax
		 *
		 * @return self Chainable
		 */
		public function from(string $syntax)
		{
			if (!$this->tableJoinSyntax instanceof TableJoin) {
				$this->tableJoinSyntax = new TableJoin();
			}

			$this->tableJoinSyntax->parseSyntax($syntax);
			$this->queryType = 'select';

			return $this;
		}

		/**
		 * Execute the SQL statement and fetch the first record.
		 *
		 * @param array $parameters An arary contains the parameters
		 *
		 * @return null|array the first record or return null if no record is found
		 */
		public function lazy(array $parameters = [])
		{
			return $this->adapter->lazy($this, $parameters);
		}

		/**
		 * Execute the SQL statement.
		 *
		 * @param array $parameters An arary contains the parameters
		 *
		 * @return Query The Query object
		 */
		public function query(array $parameters = [])
		{
			return $this->adapter->query($this, $parameters);
		}

		/**
		 * Assign the parameter value.
		 *
		 * @param array $parameter An arary contains the parameters
		 * @param mixed $value     The value of the parameters
		 *
		 * @return self Chainable
		 */
		public function assign($parameter, $value = null)
		{
			if (\is_array($parameter)) {
				foreach ($parameter as $key => $value) {
					$this->assign($key, $value);
				}
			} else {
				$this->parameters[$parameter] = $value;
			}

			return $this;
		}

		/**
		 * Get the TableJoin-Syntax object.
		 *
		 * @return SyntaxParser\TableJoin
		 */
		public function getTableJoinSyntax()
		{
			return $this->tableJoinSyntax;
		}

		/**
		 * Fork the target table name as a Statement.
		 *
		 * @param string $name A string of the table name to override
		 *
		 * @return Statement A Statement object
		 */
		public function fork($name)
		{
			$name = trim($name);
			if (!\strlen($name)) {
				throw new ErrorHandler('Invalid table name');
			}

			if (!($this->tableJoinSyntax instanceof TableJoin)) {
				throw new ErrorHandler('You cannot fork the statement because there is no TableJoin Syntax declared.');
			}

			$override = $this->tableJoinSyntax->getOverrideStatement($name);
			if (!($override instanceof self)) {
				$override = new self($this->adapter);
				$this->tableJoinSyntax->override($name, $override);
			}

			return $override;
		}

		/**
		 * Generate the where syntax.
		 *
		 * @return string The where syntax
		 */
		public function getSyntax()
		{
			if ('select' === $this->queryType) {
				if ($this->tableJoinSyntax) {
					$sql = 'SELECT ' . implode(', ', $this->columns) . ' FROM ';
					$sql .= (\is_string($this->tableJoinSyntax)) ? $this->tableJoinSyntax : $this->tableJoinSyntax->getStatement($this->parameters);
					if ($this->whereSyntax) {
						$syntax = $this->getWhereSyntax($this->whereSyntax);
						if ($syntax) {
							$sql .= ' WHERE ' . $syntax;
						}
					}

					if (\count($this->groupby)) {
						$sql .= ' GROUP BY ' . implode(', ', $this->groupby);
					}

					if ($this->havingSyntax) {
						$syntax = $this->getWhereSyntax($this->havingSyntax);
						if ($syntax) {
							$sql .= ' HAVING ' . $syntax;
						}
					}

					if (\count($this->orderby)) {
						$sql .= ' ORDER BY ' . implode(', ', $this->orderby);
					}

					if ($this->fetchLength > 0) {
						$sql .= ' LIMIT ' . $this->startRecord . ', ' . $this->fetchLength;
					}
				}
			} else {
				$sql = $this->sql;
				if (('select' === $this->queryType || 'delete' === $this->queryType || 'update' === $this->queryType) && $this->whereSyntax) {
					$syntax = $this->getWhereSyntax($this->whereSyntax);
					if ($syntax) {
						$sql .= ' WHERE ' . $syntax;
					}
				}
			}

			return $this->replaceParameter($sql);
		}

		public function getResourceId()
		{
			return $this->resourceId;
		}

		/**
		 * Parse the column syntax and split into an array.
		 *
		 * @param string $syntax The column syntax
		 *
		 * @return array An array conatins the columns
		 */
		private function parseColumnSyntax(string $syntax)
		{
			$syntax = trim($syntax);
			if (!$regex = RegexHelper::GetCache('statement-split-comma')) {
				$regex = new RegexHelper('\s*,\s*', 'statement-split-comma');
				$regex->exclude(RegexHelper::EXCLUDE_ALL_QUOTES);
			}

			return $regex->split($syntax);
		}

		/**
		 * Generate the where syntax by given string or Where object.
		 *
		 * @param mixed $whereSyntax The string of where syntax or the Where object
		 *
		 * @return string The where syntax
		 */
		private function getWhereSyntax($whereSyntax)
		{
			if (!$whereSyntax) {
				return '';
			}

			$statement = '';
			if (\is_string($whereSyntax)) {
				return $whereSyntax;
			}

			if ($whereSyntax instanceof Where) {
				if (\count($this->parameters)) {
					$whereSyntax->assign($this->parameters);
				}

				$syntax = $whereSyntax->getStatement();
				if ($syntax) {
					return $syntax;
				}
			}

			return '';
		}

		private function replaceParameter(string $statement)
		{
			if (!$regex = RegexHelper::GetCache('statement-parameter')) {
				$regex = new RegexHelper('/:(\w+)/', 'statement-parameter');
				$regex->exclude(RegexHelper::EXCLUDE_ALL_QUOTES);
			}

			return $regex->replace(function ($matches) {
				if (!\array_key_exists($matches[2], $this->parameters)) {
					return '""';
				}

				return '"' . addslashes($this->parameters[$matches[2]]) . '"';
			}, $statement);
		}
	}
}
