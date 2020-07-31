<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework\Database\SyntaxParser {
	use RazyFramework\Database\Statement;
	use RazyFramework\ErrorHandler;
	use RazyFramework\RegexHelper;

	/**
	 * TableJoin-Syntax Paser, used to create a table join statement by using shorten syntax.
	 */
	class TableJoin
	{
		/**
		 * An array contains the join type symbol and syntax.
		 *
		 * @var array
		 */
		private const JOIN_TYPE = [
			'<<' => 'LEFT OUTER JOIN',
			'>>' => 'RIGHT OUTER JOIN',
			'<'  => 'LEFT JOIN',
			'>'  => 'RIGHT JOIN',
			'-'  => 'JOIN',
			'*'  => 'CROSS JOIN',
		];

		/**
		 * An array contains the processed TableJoin-Syntax.
		 *
		 * @var array
		 */
		private $extracted = [];

		/**
		 * An array contains the SQL statement for table overriding.
		 *
		 * @var array
		 */
		private $overrides = [];

		/**
		 * TableJoin constructor.
		 *
		 * @param string $sql [description]
		 */
		public function __construct(string $sql = '')
		{
			if ($sql) {
				$this->parseSyntax($sql);
			}
		}

		/**
		 * Override the table name with a custom SQL statement or a Statement object.
		 *
		 * @param array|string $name      A string of the table name or an array contains the name and the statement
		 * @param mixed        $statement The statement to override the table name
		 *
		 * @return self Chainable
		 */
		public function override($name, $statement = null)
		{
			if (\is_array($name)) {
				foreach ($name as $key => $statement) {
					$this->override($key, $statement);
				}
			} else {
				$name = trim($name);
				if (!\strlen($name)) {
					return $this;
				}

				$this->overrides[$name] = $statement;
			}

			return $this;
		}

		/**
		 * Obtain the statement used to override the table name by givan name.
		 *
		 * @param string $name A string of the table name that has be overrided
		 *
		 * @return mixed The statement used to override the table name
		 */
		public function getOverrideStatement($name)
		{
			$name = trim($name);
			if (!\strlen($name)) {
				return null;
			}

			return $this->overrides[$name] ?? null;
		}

		/**
		 * Generate and return the SQL Statement.
		 *
		 * @param null|array $parameters An array of parameters
		 *
		 * @return string The table join syntax
		 */
		public function getStatement(?array $parameters = null)
		{
			$parameters = $parameters ?? [];
			if (!\count($this->extracted)) {
				return '';
			}

			return $this->combine($this->extracted, $parameters);
		}

		/**
		 * Parse the TableJoin-Syntax.
		 *
		 * @param string $syntax the TableJoin-Syntax
		 *
		 * @return self Chinable
		 */
		public function parseSyntax(string $syntax)
		{
			$syntax = trim($syntax);

			// Extract the parens
			$structure = RegexHelper::ParensParser($syntax, RegexHelper::EXCLUDE_ALL_QUOTES | RegexHelper::EXCLUDE_SQUARE_BRACKET | RegexHelper::EXCLUDE_CUSTOM, [
				['regex' => '[^><\-\*(]\('],
			]);
			$this->extracted = $this->extract($structure);

			return $this;
		}

		/**
		 * Get the "using" syntax for table joining.
		 *
		 * @param string $syntax A string of the column name separated by comma
		 *
		 * @return string The converted syntax
		 */
		private function getUsingSyntax(string $syntax)
		{
			$syntax = trim($syntax);
			if (!$regex = RegexHelper::GetCache('tablejoin-syntax-using')) {
				$regex = new RegexHelper('\s*,\s*', 'tablejoin-syntax-using');
				$regex->exclude(RegexHelper::EXCLUDE_ALL_QUOTES);
			}

			$columns = $regex->split($syntax);

			foreach ($columns as &$column) {
				$column = trim($column);
				if (!preg_match('/^(?:\`(?:[\x00-\x5B\x5D-\x5F\x61-x7F]++|\\[\\\`])+\`|[a-z]\w*)$/', $column)) {
					throw new ErrorHandler('USING clause only allow column name.');
				}
			}

			return '(' . implode(', ', $columns) . ')';
		}

		private function extractCondition(string &$syntax)
		{
			$condition = '';
			$type      = '';
			$result    = [];

			if (preg_match('/\[([?:])?(.+)\]$/', $syntax, $matches)) {
				$condition = $matches[2];
				$type      = $matches[1] ?? ':';
				$syntax    = substr($syntax, 0, -\strlen($matches[0]));
			}

			$result['condition'] = $condition;
			if ($condition) {
				if ('?' === $type) {
					$result['condition_type'] = 'where';
				} elseif (':' === $type) {
					$result['condition_type']  = 'using';
					$result['condition_alias'] = '';
					$result['condition']       = $this->getUsingSyntax($object['condition']);
				} elseif ($result['condition']) {
					if ($result['condition'] && '<' === $result['condition'][0]) {
						$result['condition_alias'] = $this->getAlias($result['condition']);
					}
					$result['condition_type'] = 'column';
				}
			}

			return $result;
		}

		private function buildCondition(array $condition, string $primaryAlias, string $tableAlias)
		{
			if ($condition['condition']) {
				if (' CROSS JOIN ' === $joinType) {
					throw new ErrorHandler('CROSS JOIN is not allow using ON or USING clause.');
				}

				if ('where' === $condition['condition_type']) {
					$whereSyntax = new Where($condition['condition']);
					// Where Syntax
					return ' ON ' . $whereSyntax->getStatement($parameters);
				}
				if ('using' === $condition['condition_type']) {
					// Using Syntax
					return ' USING ' . $condition['condition'];
				}
				$alias   = $condition['condition_alias'] ?? $primaryAlias;
				$columns = preg_split('/\s+,\s+/', $condition['condition']);

				// Shorten Join Condition with Primary Table
				$statement = ' ON ';
				$clips     = [];
				foreach ($columns as $column) {
					$clips[] = $alias . '.' . $condition['condition'] . ' = ' . $tableAlias . '.' . $column;
				}

				return $statement . implode(' AND ', $clips);
			}
			$statement .= ' NATURAL' . $joinType . $condition['syntax'];
		}

		/**
		 * Parse the table, it's alias and the joining condition.
		 *
		 * @param string $syntax the TableJoin-Syntax
		 *
		 * @return array An array contains the table property
		 */
		private function parseTable(string $syntax)
		{
			$object = $this->extractCondition($syntax);

			if (preg_match('/^(\`(?:[\x00-\x5B\x5D-\x5F\x61-x7F]++|\\\\[\\\\\`])+\`|[a-z]\w*)(?:\.(?:((?1))|{\$(\w+)}))?$/', $syntax, $matches)) {
				if (isset($matches[2]) && $matches[2]) {
					$object['alias'] = trim($matches[1], '`');
					$object['name']  = trim($matches[2], '`');
				} else {
					$object['alias'] = trim($matches[1], '`');
					$object['name']  = $object['alias'];
				}

				if (isset($this->overrides[$object['name']])) {
					$statement = $this->overrides[$object['name']];

					if ($statement instanceof Statement) {
						$statement = $statement->getSyntax();
					} elseif ($statement instanceof self) {
						$statement = $statement->getStatement();
					} elseif (\is_string($statement)) {
						$statement = $statement;
					} else {
						throw new ErrorHandler('The statement of ' . $object['name'] . ' is not a statement.');
					}

					$object['syntax'] = '(' . $statement . ') AS ' . $object['alias'];
				} else {
					$object['syntax'] = $object['name'] . (($object['name'] !== $object['alias']) ? ' AS ' . $object['alias'] : '');
				}

				return $object;
			}

			throw new ErrorHandler('Invalid table name and alias');
		}

		/**
		 * Get the table alias.
		 *
		 * @param string $condition The condition syntax
		 *
		 * @return string The table alias
		 */
		private function getAlias(string &$condition)
		{
			if (preg_match('/^<(.+?)>/', $condition, $matches)) {
				$condition = substr($condition, \strlen($matches[0]));

				return $matches[1];
			}

			return '';
		}

		/**
		 * Combine all the statement clips together.
		 *
		 * @param array      $clips        An array contains all table property
		 * @param null|array $parameters   An array of parameters
		 * @param array      $primaryTable An array conatins the primary table property
		 *
		 * @return string The SQL statement
		 */
		private function combine(array $clips, array $parameters = [], array &$primaryTable = null)
		{
			$statement = '';
			$clip      = array_shift($clips);
			if (\is_array($clip)) {
				$statement .= '(' . $this->combine($clip, $parameters, $primaryTable) . ')';
			} else {
				$clip = $this->parseTable($clip);
				if (!$primaryTable) {
					if ($clip['condition']) {
						throw new ErrorHandler('The primary table does not need any join condition');
					}
					$primaryTable = $clip;
				}
				$statement .= $clip['syntax'];
			}

			while (\count($clips)) {
				// Extract the join type
				$joinType = array_shift($clips);
				if (!isset(self::JOIN_TYPE[$joinType])) {
					throw new ErrorHandler('Invalid table join syntax');
				}
				$joinType = ' ' . self::JOIN_TYPE[$joinType] . ' ';

				// Extract the table name with condition if givan
				$table = array_shift($clips);
				if (!$table) {
					throw new ErrorHandler('Invalid table join syntax');
				}

				if (\is_array($table)) {
					$statement .= $joinType . '(' . $this->combine($table, $parameters) . ')';
				} else {
					$table = $this->parseTable($table);
					if ($table['condition']) {
						if (' CROSS JOIN ' === $joinType) {
							throw new ErrorHandler('CROSS JOIN is not allow using ON or USING clause.');
						}

						if ('where' === $table['condition_type']) {
							$whereSyntax = new Where($table['condition']);
							// Where Syntax
							$statement .= $joinType . $table['syntax'] . ' ON ' . $whereSyntax->getStatement($parameters);
						} elseif ('using' === $table['condition_type']) {
							// Using Syntax
							$statement .= $joinType . $table['syntax'] . ' USING ' . $table['condition'];
						} else {
							$alias   = $table['condition_alias'] ?? $primaryTable['alias'];
							$columns = preg_split('/\s+,\s+/', $table['condition']);

							// Shorten Join Condition with Primary Table
							$statement .= $joinType . $table['syntax'] . ' ON ';
							$set = [];
							foreach ($columns as $column) {
								$set[] = $alias . '.' . $table['condition'] . ' = ' . $table['alias'] . '.' . $column;
							}
							$statement .= implode(' AND ', $set);
						}
					} else {
						$statement .= ' NATURAL' . $joinType . $table['syntax'];
					}
				}
			}

			return $statement;
		}

		/**
		 * Parse the table join syntax and extract it into an array.
		 *
		 * @param string $statement The table join syntax
		 *
		 * @return array An array contains the table join syntax
		 */
		private function parseTableJoin(string $statement)
		{
			if (!$regex = RegexHelper::GetCache('tablejoin-syntax-tablejoin')) {
				$regex = new RegexHelper('/(?:>>|<<|[><\-\*])(?!=)/', 'tablejoin-syntax-tablejoin');
				$regex->exclude(RegexHelper::EXCLUDE_ALL_QUOTES | RegexHelper::EXCLUDE_ROUND_BRACKET | RegexHelper::EXCLUDE_SQUARE_BRACKET);
			}

			$clips = $regex->split($statement, RegexHelper::SPLIT_DELIMITER);

			$joinType = false;
			foreach ($clips as &$clip) {
				// TODO: Maybe empty string?
				if (preg_match('/^>>|<<|[><\-\*]$/', $clip)) {
					if ($joinType) {
						throw new ErrorHandler('Syntax Error (Misplaced operator)');
					}
					$joinType = true;
				} else {
					$joinType = false;
				}
			}

			return $clips;
		}

		/**
		 * Iterate the every parentheses level and parse the syntax.
		 *
		 * @param array $structure An array contains each level parentheses structure
		 *
		 * @return array An array contains the parsed structure
		 */
		private function extract(array $structure)
		{
			$clips = [];
			foreach ($structure as $content) {
				if (\is_string($content)) {
					$clips = array_merge($clips, $this->parseTableJoin($content));
				} else {
					$extracted = $this->extract($content);
					if (\count($clips) && !preg_match('/^(>>|<<|[><\-\*])$/', end($clips))) {
						// If the previous clip is a statement or bracketed syntax and
						// the first clip is not start with join type, throw error
						throw new ErrorHandler('Syntax Error (Missing join type)');
					}
					$clips[] = $extracted;
				}
			}

			return $clips;
		}
	}
}
