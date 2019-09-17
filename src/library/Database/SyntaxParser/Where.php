<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework\Database\SyntaxParser
{
	use RazyFramework\ErrorHandler;
	use RazyFramework\RegexHelper;

	/**
	 * Where-Syntax Paser, used to create a where statement by using shorten syntax.
	 */
	class Where
	{
		/**
		 * An array contains the processed Where-Syntax.
		 *
		 * @var array
		 */
		private $extracted  = [];

		/**
		 * An array contains the data type convertor of paramaters.
		 *
		 * @var array
		 */
		private $dataType   = [];

		/**
		 * An array contains the paramaters.
		 *
		 * @var array
		 */
		private $parameters = [];

		/**
		 * WhereSyntaxParser constructor.
		 *
		 * @param string $whereSyntax The string of where syntax
		 */
		public function __construct(string $whereSyntax = '')
		{
			if ($whereSyntax) {
				$this->parseSyntax($whereSyntax);
			}
		}

		/**
		 * Assign paramerter value for generating SQL statement.
		 *
		 * @param mixed $parameter The name of paramater or an array list of parameters
		 * @param mixed $value     The value of paramater
		 *
		 * @return self Chainable
		 */
		public function assign($parameter, $value = null)
		{
			if (is_array($parameter)) {
				foreach ($parameter as $key => $value) {
					$this->assign($key, $value);
				}
			} else {
				if (!array_key_exists($parameter, $this->dataType)) {
					$this->dataType[$parameter] = [
						'type' => 'string',
					];
				}
				$this->parameters[$parameter] = $value;
			}

			return $this;
		}

		/**
		 * Parse the Where Syntax.
		 *
		 * @param string $whereSyntax The where syntax string
		 *
		 * @return self Chainable
		 */
		public function parseSyntax(string $whereSyntax)
		{
			$whereSyntax = trim($whereSyntax);

			// Extrac the parens
			$structure = RegexHelper::ParensParser($whereSyntax, RegexHelper::EXCLUDE_ALL_QUOTES | RegexHelper::EXCLUDE_CUSTOM, [
				['regex' => '[^,|(]\('],
			]);
			$this->extracted = $this->extract($structure);

			return $this;
		}

		/**
		 * Generate and return the SQL Statement.
		 *
		 * @return string The where statement
		 */
		public function getStatement()
		{
			if (!count($this->extracted)) {
				return '';
			}

			return $this->replaceParameter($this->combine($this->extracted));
		}

		/**
		 * Replace the paramater by given value.
		 *
		 * @param string $statement The SQL Statement
		 *
		 * @return string The replaced SQL Statement
		 */
		private function replaceParameter(string $statement)
		{
			if (!$regex = RegexHelper::GetCache('select-syntax-parameter')) {
				$regex = new RegexHelper('/:(\w+)/', 'select-syntax-parameter');
				$regex->exclude(RegexHelper::EXCLUDE_ALL_QUOTES);
			}

			return $regex->replace(function ($matches) {
				if (array_key_exists($matches[2], $this->dataType)) {
					$dataType = $this->dataType[$matches[2]];
					if (!isset($this->parameters[$matches[2]])) {
						return "''";
					}

					if ('json_path' === $dataType['type']) {
						$value = $this->parameters[$matches[2]];
						if (!is_string($value)) {
							throw new ErrorHandler('Invalid JSON path datatype, it should be a string.');
						}
						if (($value[0] ?? '') !== '$') {
							throw new ErrorHandler('JSON path should start with $.');
						}

						return '"' . $value . '"';
					}

					if ('json_object' === $dataType['type']) {
						$value = $this->parameters[$matches[2]];
						if (is_array($value)) {
							return '"' . addslashes(json_encode($value)) . '"';
						}
						if (is_string($value) && preg_match('/^{.+?}$/', $value)) {
							// Assume it is a JSON string
							return '"' . $value . '"';
						}

						throw new ErrorHandler('Only array and JSON string allowed for json_contains.');
					}

					if ('set' === $dataType['type']) {
						$set = [];
						if (is_array($this->parameters[$matches[2]])) {
							foreach ($this->parameters[$matches[2]] as $text) {
								$set[] = addslashes($text);
							}
						} else {
							$set[] = addslashes($this->parameters[$matches[2]]);
						}

						return '"' . implode('", "', $set) . '"';
					}

					if ('string' === $dataType['type']) {
						return '"' . addslashes($this->parameters[$matches[2]]) . '"';
					}

					return $this->wildcard($this->parameters[$matches[2]], $dataType['options']);
				}

				return $matches[0];
			}, $statement);
		}

		/**
		 * Combine the nested statement.
		 *
		 * @param array $clips The array of statement
		 *
		 * @return string The SQL statement
		 */
		private function combine(array $clips)
		{
			$statement = '';
			foreach ($clips as $clip) {
				$statement .= ' ' . ((is_array($clip)) ? '(' . $this->combine($clip) . ')' : $clip);
			}

			return substr($statement, 1);
		}

		/**
		 * Parse and convert the operand.
		 *
		 * @param string $operand The string of operand
		 *
		 * @return array The operand include its attribute
		 */
		private function convertOperand(string $operand)
		{
			// If the operand is a Column (Also contains json_contains operator)
			if (preg_match('/^((\`(?:[\x00-\x5B\x5D-\x5F\x61-x7F]++|\\\\[\\\\\`])+\`|[a-z]\w*)(?:\.((?2)))?)(?:(->>?)([\'"])\$(.+)\5)?$/', $operand, $matches)) {
				$object = [
					'type'    => 'column',
					'operand' => $operand,
				];

				if (isset($matches[3]) && $matches[3]) {
					$object['table_alias']  = trim($matches[2], '`');
					$object['column_name']  = trim($matches[3], '`');
				} else {
					$object['column_name'] = trim($matches[2], '`');
				}

				if (isset($matches[4])) {
					$object['json_operator'] = $matches[4];
					$object['json_path']     = $matches[6];
				}

				return $object;
			}

			// If the operand is a string wrapped by quotes
			if (preg_match('/^([\'"])((?:[^\\\\\'"]++|\\\\.)*)\1$/', $operand, $matches)) {
				return [
					'type'    => 'string',
					'operand' => $operand,
					'content' => $matches[2],
				];
			}

			// If the operand is a parameter
			if (preg_match('/^:(\w+)$/', $operand, $matches)) {
				return [
					'type'      => 'parameter',
					'operand'   => $operand,
					'parameter' => $matches[1],
				];
			}

			// If the operand is a MySQL command
			if (preg_match('/^{\?(.+)}$/', $operand, $matches)) {
				return [
					'type'    => 'command',
					'operand' => $matches[1],
				];
			}

			// If the operand is a number
			if (preg_match('/^-?\d+(\.\d+)?$/', $operand)) {
				return [
					'type'    => 'number',
					'operand' => (float) $operand,
				];
			}

			// If the operand is funtiocn STX
			if (preg_match('/^STX\((?<quote>[\'"])((?:(?!(?&quote))[^\\\\]|\\\\.)+?)(?&quote)\)$/', $operand, $matches)) {
				return [
					'type'    => 'syntax',
					'operand' => stripcslashes($matches[2]),
				];
			}

			if (preg_match('/^([a-zA-Z_]\w*)\((.+?)\)$/', $operand, $matches)) {
				return [
					'type'      => 'function',
					'function'  => $matches[1],
					'arguments' => stripcslashes($matches[2]),
					'operand'   => $operand,
				];
			}

			return [
				'type'    => 'syntax',
				'operand' => $operand,
			];
		}

		/**
		 * Parse the clip of Where Syntax, determine the operand type and its operator.
		 *
		 * @param string $statement The clip of Where Syntax
		 *
		 * @return string The SQL statement
		 */
		private function parse(string $statement)
		{
			// Operator Table
			// ======================================================================
			// Symbol      Human-Readable Syntax       Datatype    Description
			// =           is                          string      Equal
			// |=          in                          array       Search in list
			// *=          contains                    string      Contain string
			// ^=          start with                  string      Start with string
			// $=          end with                    string      End with string
			// !=          is not                      string      Not equal
			// <           less than                   number      Less than
			// >           greater than                number      Greater than
			// <=          less than and equal to      number      Less than and equal to
			// >=          greater than and equal to   number      Greater than and equal to
			// :=          json path exists            string      json_extract
			// ~=          json object contains        array       json_contains
			// &=          json search value in        string      json_search
			//
			// Function
			// ======================================================================
			// STX("Mysql Syntax")                The MySQL Command
			//
			// Usage
			// ======================================================================
			// Basic       column_name="a"        Equal to "a"
			// Parameter   column_name=:abc       Equal to the paramater abc
			// Reference   column_name=?          Equal to the paramater column_name
			// HRS         column_name is "a"     Equal to "a"

			if (!$regex = RegexHelper::GetCache('select-syntax-operator')) {
				$regex = new RegexHelper('\s*[|*^$!:~&]?=\s*|\s*[><]=?\s*|\s+(?|(?:start|end) with|(?:less|greater) than(?: and equal to)?|json (?:path exists|object contains|search value in)|contains|is(?: not)?|in)\s+', 'select-syntax-operator');
				$regex->exclude(RegexHelper::EXCLUDE_ALL_QUOTES | RegexHelper::EXCLUDE_ROUND_BRACKET);
			}

			$matches = $regex->split($statement, RegexHelper::SPLIT_DELIMITER | RegexHelper::SPLIT_BODY_ONLY, 3);
			if (1 === count($matches) || 3 === count($matches)) {
				$leftHand = trim($matches[0]);
				$negative = false;
				if ('!' === $leftHand[0]) {
					$negative = true;
					$leftHand = substr($leftHand, 1);
				}

				if (3 === count($matches)) {
					$operator  = trim($matches[1]);
					$rightHand = trim($matches[2]);
					if ($leftHand === $rightHand) {
						// Prevent using reference in both operand
						if ('?' === $leftHand) {
							throw new ErrorHandler('You cannot set both operand as reference.');
						}
					}

					if ('?' === $leftHand) {
						$rightHand = $this->convertOperand($rightHand);
						if ($rightHand && 'column' === $rightHand['type']) {
							$leftHand = [
								'type'      => 'parameter',
								'parameter' => $rightHand['column_name'],
								'operand'   => ':' . $rightHand['column_name'],
							];
						} else {
							throw new ErrorHandler('You cannot refer the non-column operand as a parameter.');
						}
					} elseif ('?' === $rightHand) {
						$leftHand = $this->convertOperand($leftHand);
						if ($leftHand && 'column' === $leftHand['type']) {
							$rightHand = [
								'type'      => 'parameter',
								'parameter' => $leftHand['column_name'],
								'operand'   => ':' . $leftHand['column_name'],
							];
						} else {
							throw new ErrorHandler('You cannot refer the non-column operand as a parameter.');
						}
					} else {
						$leftHand  = $this->convertOperand($leftHand);
						$rightHand = $this->convertOperand($rightHand);
					}

					if (!$leftHand || !$rightHand) {
						throw new ErrorHandler('Syntax Error (Invalid operand)');
					}

					$this->setDefaultDataType($leftHand)->setDefaultDataType($rightHand);

					return $this->comparison($operator, $leftHand, $rightHand, $negative);
				}

				// Only true or false
				$leftHand = $this->convertOperand($leftHand);
				if ('column' === $leftHand['type']) {
					return $leftHand['operand'] . ' = ' . (($negative) ? '0' : '1');
				}

				return '!(' . $leftHand['operand'] . ')';
			}

			throw new ErrorHandler('Syntax Error (Invalid operator)');
		}

		/**
		 * Set the default data type if the operand object is a paramater.
		 *
		 * @param array $operand The operand object
		 *
		 * @return self Chainable
		 */
		private function setDefaultDataType(array $operand)
		{
			if ('parameter' === $operand['type'] && !array_key_exists($operand['parameter'], $this->dataType)) {
				$this->dataType[$operand['parameter']] = [
					'type' => 'string',
				];
			}

			return $this;
		}

		/**
		 * Add wildcard symbol to the string by its parameter's data type.
		 *
		 * @param string $text The paramater value
		 * @param int    $flag The wildcard's flag
		 *
		 * @return string The paramater value with wildcard symbol
		 */
		private function wildcard(string $text, int $flag = 0b11)
		{
			$text = addslashes($text);
			if (0b01 === ($flag & 0b01)) {
				$text = $text . '%';
			}
			if (0b10 === ($flag & 0b10)) {
				$text = '%' . $text;
			}

			return "'" . $text . "'";
		}

		/**
		 * Identify the operand, set up its the paramater data type or add wildcard symbol if the operand is a string.
		 *
		 * @param array $operand The object of operand
		 * @param int   $flag    The wildcard's flag
		 *
		 * @return string The statement
		 */
		private function createWildcard(array $operand, int $flag = 0b11)
		{
			if ('parameter' === $operand['type']) {
				$this->dataType[$operand['parameter']] = [
					'type'    => 'wildcard',
					'options' => $flag,
				];

				return $operand['operand'];
			}

			if ('string' === $operand['type']) {
				return $this->wildcard($operand['content'], $flag);
			}

			return $operand['operand'];
		}

		/**
		 * Set up its the paramater data type or convert the JSON string.
		 *
		 * @param array $operand The object of operand
		 *
		 * @return string The statement
		 */
		private function createJSONObject(array $operand)
		{
			if ('column' === $operand['type'] || 'syntax' === $operand['type']) {
				// If the column is NULL it may cause JSON error in some MySQL/MariaDB version
				return 'COALESCE(' . $operand['operand'] . ", '')";
			}

			if ('string' === $operand['type']) {
				return '"' . addslashes($operand['operand']) . '"';
			}

			if ('parameter' === $operand['type']) {
				$this->dataType[$operand['parameter']] = [
					'type' => 'json_object',
				];

				return $operand['operand'];
			}

			throw new ErrorHandler('Syntax Error (Invalid JSON Object)');
		}

		/**
		 * Parse the comparison syntax.
		 *
		 * @param string $operator  The operator of the comparison
		 * @param array  $leftHand  LHS operand
		 * @param array  $rightHand RHS operand
		 * @param bool   $negative  Negative flag, it will add !( ... ) the reverse the result
		 *
		 * @return string The complete comparison statement
		 */
		private function comparison(string $operator, array $leftHand, array $rightHand, bool $negative = false)
		{
			$operand = '';
			// Special operator
			if ('|=' === $operator || 'in' === $operator) {
				// MySQL IN
				// column_name IN(data)
				if ('parameter' === $rightHand['type']) {
					$this->dataType[$rightHand['parameter']] = [
						'type' => 'set',
					];

					$text = $rightHand['operand'];
				} else {
					$text = $rightHand[('string' === $rightHand['type']) ? 'content' : 'operand'];
				}

				$operand = $leftHand['operand'] . ' IN(' . $text . ')';
			} elseif ('*=' === $operator || '^=' === $operator || '$=' === $operator || 'contains' === $operator || 'start with' === $operator || 'end with' === $operator) {
				// MySQL LIKE
				// column_name LIKE "%content%"
				$text = '';

				if ('^=' === $operator || 'start with' === $operator) {
					$flag = 0b01;
				} elseif ('$=' === $operator || 'end with' === $operator) {
					$flag = 0b10;
				} else {
					$flag = 0b11;
				}

				$leftHand  = $leftHand['operand'];
				$rightHand = $this->createWildcard($rightHand, $flag);

				$operand = $leftHand . ' LIKE ' . $rightHand;
			} elseif (':=' === $operator || 'json path exists' === $operator) {
				// MySQL JSON_EXTRACT
				// JSON_EXTRACT(column_name, "$.json.path")
				if ('string' === $rightHand['type'] && '$' !== ($rightHand['text'][0] ?? '')) {
					throw new ErrorHandler('JSON_EXTRACT only allows json path.');
				}

				if ('string' === $rightHand['type']) {
					if ('$' !== ($rightHand['operand'][0] ?? '')) {
						throw new ErrorHandler('JSON path should start with $.');
					}
					$rightHand = '"' . $rightHand['operand'] . '"';
				} elseif ('parameter' === $rightHand['type']) {
					$this->dataType[$rightHand['parameter']] = [
						'type' => 'json_path',
					];

					$rightHand = $rightHand['operand'];
				} else {
					throw new ErrorHandler('Invalid value passed to JSON_EXTRACT');
				}

				$operand = 'JSON_EXTRACT(' . $leftHand['operand'] . ', ' . $rightHand . ') IS NOT NULL';
			} elseif ('~=' === $operator || 'json object contains' === $operator || '&=' === $operator || 'json search value in' === $operator) {
				$leftHand = $this->createJSONObject($leftHand);

				if ('~=' === $operator || 'json object contains' === $operator) {
					// MySQL JSON_CONTAINS
					// JSON_CONTAINS(column_name, '{"json": "object"}')
					$rightHand = $this->createJSONObject($rightHand);
					$operand   = 'JSON_CONTAINS(' . $leftHand . ', ' . $rightHand . ') = 1';
				} else {
					// MySQL JSON_SEARCH
					// JSON_SEARCH(column_name, "one", "text")
					if ('string' === $rightHand['type']) {
						$rightHand = '"' . $rightHand['content'] . '"';
					} else {
						$rightHand = $rightHand['operand'];
					}

					$operand = 'JSON_SEARCH(' . $leftHand . ', "one", ' . $rightHand . ') IS NOT NULL';
				}
			}

			// Basic operator
			if (!$operand) {
				if ('!=' === $operator || 'is not' === $operator) {
					$operator = '<>';
				} elseif ('=' === $operator || 'is' === $operator) {
					$operator = '=';
				} elseif ('>' === $operator || 'greater than' === $operator) {
					$operator = '>';
				} elseif ('<' === $operator || 'less than' === $operator) {
					$operator = '<';
				} elseif ('>=' === $operator || 'greater than and equal to' === $operator) {
					$operator = '>=';
				} elseif ('<=' === $operator || 'less than and equal to' === $operator) {
					$operator = '<=';
				}

				$operand = $leftHand['operand'] . ' ' . $operator . ' ' . $rightHand['operand'];
			}

			if ($negative) {
				$operand = '!(' . $operand . ')';
			}

			return $operand;
		}

		/**
		 * Parse the syntax and split the comparison into an array.
		 *
		 * @param string $statement A clip of Where Syntax
		 *
		 * @return array An array contains splitted comparison
		 */
		private function syntaxParser(string $statement)
		{
			if (!$regex = RegexHelper::GetCache('select-syntax-andor')) {
				$regex = new RegexHelper('(,|\|(?!=))', 'select-syntax-andor');
				$regex->exclude(RegexHelper::EXCLUDE_ALL_QUOTES | RegexHelper::EXCLUDE_ROUND_BRACKET);
			}

			$clips    = $regex->split($statement, RegexHelper::SPLIT_DELIMITER);
			$joinType = false;
			foreach ($clips as &$clip) {
				$clip = trim($clip);
				if (!$clip) {
					throw new ErrorHandler('Syntax Error (Empty operand)');
				}

				if (preg_match('/^[,|]$/', $clip)) {
					$clip = (',' === $clip) ? 'AND' : 'OR';
				} else {
					$clip = $this->parse($clip);
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
				if (is_string($content)) {
					$clips = array_merge($clips, $this->syntaxParser($content));
				} else {
					$extracted = $this->extract($content);
					if (count($clips) && !preg_match('/^(AND|OR)$/', end($clips))) {
						// If the previous clip is a statement or bracketed syntax and
						// the first clip is not start with operator, throw error
						throw new ErrorHandler('Syntax Error (Missing operator)');
					}
					$clips[] = $extracted;
				}
			}

			return $clips;
		}
	}
}
