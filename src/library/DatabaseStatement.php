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
  class DatabaseStatement
  {
  	private static $lastResourceId = 0;

  	private $dbObject;
  	private $parameters        = [];
  	private $isSelectStatement = false;
  	private $whereable         = false;
  	private $cached            = false;
  	private $columns           = '';
  	private $startRecord       = '';
  	private $fetchLength       = '';
  	private $selectSyntax      = '';
  	private $whereSyntax       = '';
  	private $whereRegex        = '([|,])?(!)?((:?[\w]+|`(?:[\x00-\x5B\x5D-\x5F\x61-x7F]++|\\\\[\\\\`])*`)(?:\.(?4))?|\{\$(?:[^{}\\\\]++|\\\\.)*\}|\"(?:[^"\\\\]++|\\\\.)*\"|\?|(?:-?\d+(?:\.\d+)?)|\{\?(?:[^{}\\\\]++|\\\\.)*\})(?(2)|(?:(!=|[<>]=?|=\*|=)((?3))))?(?(1)|([|,])?)';
  	private $selectRegex       =  '([><+\-\*]|\G)?(?:(?:(([\w]+|`(?:[\x00-\x5B\x5D-\x5F\x61-x7F]++|\\\\[\\\\`])*`)(?:\.((?3)))?)|\{\$([\w-]+) ((?3))\})(?:\[(?:({\?(?:[^{}\\\\]++|\\\\.)*}|(?2)|"(?:[^"\\\\]++|\\\\.)*"|(?:-?\d+(?:\.\d+)?)|:[\w]+)(?:(!=|=\||=\*|=)((?5)))?|\?((?:[^\[\]\\\\]++|\\\\.)+))\])?)(?(1)|([><+\-\*])?)';

  	public function __construct(Database $dbObject, $sql = '')
  	{
  		$sql = trim($sql);
  		if (preg_match('/^\(/', $sql)) {
  			new ThrowError('DatabaseStatement', 1001, 'Invalid SQL syntax, it cannot start with parentheses.');
  		}

  		if ($sql) {
  			$splitted  = preg_split('/(?:([\'"`])|(\()).+(?(1)\1|\))(*SKIP)(*FAIL)|\s+WHERE\s+/i', $sql);
  			$this->sql = $splitted[0];

  			if (preg_match('/^(SELECT|UPDATE|DELETE\s+FROM)\s+/i', $this->sql)) {
  				$this->whereable   = true;
  				$this->whereSyntax = (isset($splitted[1])) ? $splitted[1] : '';
  				if (preg_match('/^((?:[\'"`])|(\()).+?(?(2)\)|\1)(*SKIP)(*FAIL)|SELECT\s+(.+?)\s+FROM\s+(.++)/i', $this->sql, $matches)) {
  					if (!isset($matches[3])) {
  						new ThrowError('DatabaseStatement', 1003, 'SELECT syntax does not contain any column.');
  					}
  					$this->columns           = preg_split('/((?:[\'"`])|(\()).+?(?(2)\)|\1)(*SKIP)(*FAIL)|\s*,\s*/', $matches[3]);
  					$this->selectSyntax      = trim($matches[4]);
  					$this->isSelectStatement = true;
  					$this->searchParameters();
  				} else {
  					$this->searchParameters($sql);
  				}
  			} else {
  				$this->searchParameters($sql);
  			}
  		}

  		if (null === $dbObject->getAdapter()) {
  			new ThrowError('DatabaseStatement', 1004, 'Database adapter is null or it does not connect to databse.');
  		}

  		$this->dbObject   = $dbObject;
  		$this->resourceId = self::CreateInstance();
  	}

  	public function where(string $syntax)
  	{
  		if ($this->whereable) {
  			$syntax            = trim($syntax);
  			$this->whereSyntax = $this->parseWhereSyntax($this->parseBracket($syntax));
  			$this->cached      = false;

  			$this->searchParameters();
  		}

  		return $this;
  	}

  	public function limit(int $start, $length = 20)
  	{
  		$this->startRecord = max($start, 0);
  		$this->fetchLength = max((int) $length, 5);

  		return $this;
  	}

  	public function select(string $syntax, $column = '', $subquery = [])
  	{
  		if (!$column || !is_string($column)) {
  			$column = '*';
  		}

  		$syntax                  = trim($syntax);
  		$this->selectSyntax      = $this->parseSelectSyntax($this->parseBracket($syntax), $subquery);
  		$this->columns           = preg_split('/((?:[\'"`])|(\()).+?(?(2)\)|\1)(*SKIP)(*FAIL)|\s*,\s*/', $column);
  		$this->whereable         = true;
  		$this->cached            = false;
  		$this->isSelectStatement = true;

  		$this->searchParameters();

  		return $this;
  	}

  	public function lazy($parameters = [])
  	{
  		return $this->dbObject->lazy($this, $parameters);
  	}

  	public function query($parameters = [])
  	{
  		return $this->dbObject->query($this, $parameters);
  	}

  	public function prepare($parameters = [])
  	{
  		return $this->dbObject->prepare($this, $parameters);
  	}

  	public function setParameter($parameter, $value = null)
  	{
  		if (is_array($parameter)) {
  			foreach ($parameter as $key => $value) {
  				$this->setParameter($key, $value);
  			}
  		} else {
  			if (array_key_exists(':' . $parameter, $this->parameters)) {
  				$this->parameters[':' . $parameter] = $value;
  			}
  		}

  		return $this;
  	}

  	public function getParameter(string $parameter)
  	{
  		if (isset($this->parameters[$parameter])) {
  			return $this->parameters[$parameter];
  		}

  		return null;
  	}

  	public function getStatement()
  	{
  		if ($this->isSelectStatement) {
  			if (!$this->cached) {
  				if ($this->selectSyntax) {
  					$sql = 'SELECT ' . ((count($this->columns)) ? implode(', ', $this->columns) : '*') . ' FROM ' . $this->selectSyntax;
  					if ($this->whereable && $this->whereSyntax) {
  						$sql .= ' WHERE ' . $this->whereSyntax;
  					}

  					if ($this->startRecord > 0) {
  						$sql .= ' LIMIT ' . $this->startRecord . ', ' . $this->fetchLength;
  					}
  					$this->sql = $sql;
  				}
  				$this->cached = true;
  			}
  			$sql = $this->sql;
  		} else {
  			$sql = $this->sql;
  			if ($this->whereable && $this->whereSyntax) {
  				$sql .= ' WHERE ' . $this->whereSyntax;
  			}
  		}

  		$statement = $this->dbObject->getAdapter()->prepare($sql);

  		foreach ($this->parameters as $parameter => $value) {
  			$datatype = \PDO::PARAM_STR;
  			if (null === $value) {
  				$datatype = \PDO::PARAM_NULL;
  			} elseif (is_int($value)) {
  				$datatype = \PDO::PARAM_INT;
  			} elseif (is_bool($value)) {
  				$datatype = \PDO::PARAM_BOOL;
  			}
  			$statement->bindValue($parameter, $value, $datatype);
  		}

  		return $statement;
  	}

  	public function getResourceId()
  	{
  		return $this->resourceId;
  	}

  	private function searchParameters($sql = '')
  	{
  		$this->parameters = [];
  		if ($sql) {
  			if (preg_match_all('/:([\w]+)/', $sql, $matches, PREG_SET_ORDER)) {
  				foreach ($matches as $offset => $match) {
  					$this->parameters[$match[0]] = null;
  				}
  			}
  		} else {
  			if ($this->isSelectStatement) {
  				if (preg_match_all('/:([\w]+)/', $this->selectSyntax, $matches, PREG_SET_ORDER)) {
  					foreach ($matches as $offset => $match) {
  						$this->parameters[$match[0]] = null;
  					}
  				}
  			} else {
  				if (preg_match_all('/:([\w]+)/', $this->sql, $matches, PREG_SET_ORDER)) {
  					foreach ($matches as $offset => $match) {
  						$this->parameters[$match[0]] = null;
  					}
  				}
  			}

  			if (preg_match_all('/:([\w]+)/', $this->whereSyntax, $matches, PREG_SET_ORDER)) {
  				foreach ($matches as $offset => $match) {
  					$this->parameters[$match[0]] = null;
  				}
  			}
  		}

  		return $this;
  	}

  	private static function CreateInstance()
  	{
  		return ++self::$lastResourceId;
  	}

  	private function parseField(string $text)
  	{
  		// 1: Column or Parameter
  		// 2: Prefix or Column
  		// 3: Variable
  		// 4: String
  		// 5: Column-parameter
  		// 6: Digi
  		// 7: Mysql Syntax
  		preg_match('/((:?[\w]+|`(?:[\x00-\x5B\x5D-\x5F\x61-x7F]++|\\\\[\\\\`])*`)(?:\.(?2))?)|\{\$((?:[^{}\\\\]++|\\\\.)*)\}|\"((?:[^"\\\\]++|\\\\.)*)\"|(\?)|(-?\d+(?:\.\d+)?)|\{\?((?:[^{}\\\\]++|\\\\.)*)\}/', $text, $matches);

  		if (isset($matches[7])) {
  			// Return the MySQL Syntax
  			return $matches[7];
  		}

  		if (isset($matches[1]) || isset($matches[5]) || isset($matches[6])) {
  			return $matches[0];
  		}

  		if (isset($matches[3])) {
  			if (array_key_exists($matches[3], $this->parameters)) {
  				if (is_string($this->parameters[$matches[3]])) {
  					return "'" . $this->parameters[$matches[3]] . "'";
  				}
  			}

  			return `''`;
  		}

  		if (isset($matches[4])) {
  			return "'" . addslashes($matches[4]) . "'";
  		}
  	}

  	private function getJoinType(string $flag, $isNaturalJoin = false)
  	{
  		$joinType = '';

  		if ($flag) {
  			if ('<' === $flag) {
  				$joinType = ' LEFT JOIN ';
  			} elseif ('>' === $flag) {
  				$joinType = ' RIGHT JOIN ';
  			} elseif ('+' === $flag) {
  				$joinType = ' FULL JOIN ';
  				if ($isNaturalJoin) {
  					new ThrowError('Database', 3005, 'Full join does not allow natural join.');
  				}
  			} elseif ('-' === $flag) {
  				$joinType = ' JOIN ';
  			} elseif ('*' === $flag) {
  				$joinType = ' CROSS JOIN ';
  				if (!$isNaturalJoin) {
  					new ThrowError('Database', 3006, 'Cross join does not allow condition syntax.');
  				}
  			}

  			if ($isNaturalJoin) {
  				$joinType = ' NATURAL' . $joinType;
  			}
  		}

  		return $joinType;
  	}

  	private function parseSelectSyntax(array $parsedStatement, $subquery = [])
  	{
  		$result      = [];
  		$syntaxEnded = false;
  		foreach ($parsedStatement as $statement) {
  			if (is_object($statement)) {
  				$result[] = '(' . $this->parseSyntax($statement->text) . ')' . $statement->joinType;
  			} elseif (is_array($statement)) {
  				$result[] = $this->parseSyntax($statement);
  			} else {
  				if (!preg_match('/^(?:' . $this->selectRegex . ')+?$/', $statement)) {
  					new ThrowError('Database', 4001, 'Invalid Select-Syntax format.');
  				} else {
  					if (preg_match_all('/' . $this->selectRegex . '/', $statement, $matches, PREG_SET_ORDER)) {
  						$firstTable = null;
  						foreach ($matches as $clip) {
  							// 1: Prefix Join
  							// 2: Full Table Name
  							// 3: Alias
  							// 4: Table Name
  							// 5: Sub Query
  							// 6: Sub Query Alias
  							// 7: Left side condition
  							// 8: Operator
  							// 9: Right side condidion
  							// 10: Where-Syntax
  							// 11: Postfix Join

  							if (isset($clip[5]) && $clip[5]) {
  								if (!isset($subquery[$clip[5]]) || !($subquery[$clip[5]] instanceof self)) {
  									new ThrowError('Database', 4002, $clip[5] . ' is not a DatabaseStatement object.');
  								}
  								$subquerySQL   = $subquery[$clip[5]]->getStatement()->queryString;
  								$tableName     = $clip[6];
  								$alias         = $clip[6];
  								$fullTableName = '(' . $subquerySQL . ') AS ' . $clip[6];
  							} else {
  								if (!isset($clip[4]) || !$clip[4]) {
  									$alias         = $clip[3];
  									$fullTableName = $clip[3];
  								} else {
  									$alias         = $clip[3];
  									$fullTableName = $clip[4] . ' AS ' . $clip[3];
  								}
  							}

  							$isNaturalJoin   = (!isset($clip[10]) || !$clip[10]) && (!isset($clip[7]) || !$clip[7]);
  							$prefixJoinType  = $this->getJoinType($clip[1], $isNaturalJoin);
  							$postfixJoinType = (!isset($clip[11])) ? '' : $this->getJoinType($clip[11], $isNaturalJoin);

  							if (!$firstTable) {
  								$firstTable =[
  									'alias'     => $alias,
  									'fullename' => $fullTableName,
  								];
  								$result[] = $fullTableName . $postfixJoinType;

  								continue;
  							}

  							$condition = '';
  							if (isset($clip[10]) && $clip[10]) {
  								$condition = $this->parseWhereSyntax($this->parseBracket($clip[10]));
  							} elseif ($clip[7]) {
  								$left = $this->parseField($clip[7]);

  								if (isset($clip[8])) {
  									$operator = $clip[8];
  									$right    = $this->parseField($clip[9]);

  									if ('=*' === $operator) {
  										$operator = ' LIKE ';
  									} elseif ('!=' === $operator) {
  										$operator = ' <> ';
  									} else {
  										$operator = ' ' . $operator . ' ';
  									}

  									$condition = $left . $operator . $right;
  								} else {
  									$condition = $firstTable['alias'] . '.' . $clip[7] . ' = ' . $alias . '.' . $clip[7];
  								}
  							}
  							$result[] = $prefixJoinType . $fullTableName . (($condition) ? ' ON ' . $condition : '') . $postfixJoinType;
  						}
  					}
  				}
  			}
  		}

  		return implode('', $result);
  	}

  	private function parseWhereSyntax(array $parsedStatement)
  	{
  		$result = [];

  		foreach ($parsedStatement as $statement) {
  			if (is_object($statement)) {
  				$result[] = '(' . $this->parseWhereSyntax($statement->text) . ')';
  			} elseif (is_array($statement)) {
  				$result[] = $this->parseWhereSyntax($statement);
  			} else {
  				if (!preg_match('/^(?:' . $this->whereRegex . ')+?$/', $statement)) {
  					new ThrowError('DatabaseStatement', 3001, 'Invalid Where-Syntax format.');
  				} else {
  					if (preg_match_all('/' . $this->whereRegex . '/', $statement, $matches, PREG_SET_ORDER)) {
  						foreach ($matches as $clip) {
  							// 1: Prefix Condition
  							// 2: Negative symbol (!)
  							// 3: Column, String, Variable, Parameter or Column-parameter
  							// 4: Recurse Column or Parameter Group
  							// 5: Operator
  							// 6: Recurse index 2 group
  							// 7: Postfix Condition

  							$left  = $this->parseField($clip[3]);
  							$right = (isset($clip[6]) && $clip[6]) ? $this->parseField($clip[6]) : null;

  							if ('?' === $left || '?' === $right) {
  								// If there is still have another syntax but the flag is marked ended, throw an error.
  								if ($left === $right) {
  									new ThrowError('DatabaseStatement', 3002, 'Missing column name.');
  								}

  								if (!preg_match('/^(?|([\w]+)|`((?:[\x00-\x5B\x5D-\x5F\x61-x7F]++|\\\\[\\\\`])*)`)(?:\.(?|([\w]+)|`((?:[\x00-\x5B\x5D-\x5F\x61-x7F]++|\\\\[\\\\`])*)`))?$/', ('?' === $right) ? $left : $right, $columnMatches)) {
  									new ThrowError('DatabaseStatement', 3003, 'Column-Parameter only support column name.');
  								}

  								$parameter = ':' . ((isset($columnMatches[2])) ? $columnMatches[2] : $columnMatches[1]);

  								if ('?' === $left) {
  									$left = $parameter;
  								} else {
  									$right = $parameter;
  								}
  							}

  							$prefixCondition = '';
  							if ($clip[1]) {
  								$prefixCondition = (',' === $clip[1]) ? ' AND ' : ' OR ';
  							}

  							$postfixCondition = '';
  							if (isset($clip[7]) && $clip[7]) {
  								$postfixCondition = (',' === $clip[7]) ? ' AND ' : ' OR ';
  							}

  							if (isset($clip[5]) && $clip[5]) {
  								if ('=*' === $clip[5]) {
  									$operator = ' LIKE ';
  								} elseif ('!=' === $clip[5]) {
  									$operator = ' <> ';
  								} else {
  									$operator = ' ' . $clip[5] . ' ';
  								}

  								$result[] = $prefixCondition . $left . $operator . $right . $postfixCondition;
  							} else {
  								$result[] = $prefixCondition . $left . ' = ' . (($clip[2]) ? '0' : '1') . $postfixCondition;
  							}
  						}
  					}
  				}
  			}

  			$flag = true;
  		}

  		return implode('', $result);
  	}

  	private function splitBasket(string $unparsed)
  	{
  		$result = [];

  		// Search the closing delimiter
  		while (preg_match('/(?:"|(\{)|(\[))(?:[^"{\[\]}\\\\]++|\\\\.)*(?(1)}|(?(2)\]|"))(*SKIP)(*FAIL)|([()])/', $unparsed, $matches, PREG_OFFSET_CAPTURE)) {
  			if ($matches[0][1] > 0) {
  				// Put the previous content to list
  				$result[] = trim(substr($unparsed, 0, $matches[0][1]));
  			}

  			if (')' === $matches[0][0]) {
  				$object = (object) [
  					'text' => $result,
  				];

  				return [$object, trim(substr($unparsed, $matches[0][1] + strlen($matches[0][0])))];
  			}

  			// Find the closing delimiter and parse the string
  			$basketContent = $this->splitBasket(substr($unparsed, $matches[0][1] + 1));
  			$result[]      = $basketContent[0];
  			$unparsed      = $basketContent[1];
  		}
  	}

  	private function parseBracket(string $statement)
  	{
  		$result   = [];
  		$unparsed = $statement;
  		// Search the opening delimiter
  		while (preg_match('/(?:"|(\{)|(\[))(?:[^"{\[\]}\\\\]++|\\\\.)*(?(1)}|(?(2)\]|"))(*SKIP)(*FAIL)|(\()/', $unparsed, $matches, PREG_OFFSET_CAPTURE)) {
  			// Put the string to result that before the opening delimiter
  			if ($matches[3][1] > 0) {
  				$result[] = substr($unparsed, 0, $matches[3][1]);
  			}

  			// Find the closing delimiter and parse the string
  			$parsed   = $this->splitBasket(substr($unparsed, $matches[3][1] + 1));
  			$result[] = $parsed[0];
  			$unparsed = $parsed[1];
  		}

  		// Put the unparsed content to result
  		if ($unparsed) {
  			$result[] = $unparsed;
  		}

  		return $result;
  	}
  }
}
