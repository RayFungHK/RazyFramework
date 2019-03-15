<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

// Skip
// (?:(?:(["`])(?:[^\\"`]++|\\.)*\1)|{\?[^}]+?})(*SKIP)(*FAIL)
// Find AND OR
// (?:(?:(["`])(?:[^\\"`]++|\\.)*\1)|{\?[^}]+?})(*SKIP)(*FAIL)|[,|]

namespace RazyFramework
{
  class WhereSyntaxParser
  {
  	private const REGEX_SKIP            = '(?:(?:(["`\'])(?:[^\\\\"`\']++|\\\\.)*\1)|{\?(?:[^\\\\}]++|\\\\.)*?})(*SKIP)(*FAIL)|';
  	private const REGEX_WHERE_DELIMITER = '/' . self::REGEX_SKIP . '([,)]|\|(?!=))(\()?/';
  	private const REGEX_BRACKET         = '/' . self::REGEX_SKIP . '[()]/';
  	private const REGEX_OPERATOR        = '/' . self::REGEX_SKIP . '([|*!^$]?=|[><]=?)/';
  	private const REGEX_COLUMN          = '/^(\`(?:[\x00-\x5B\x5D-\x5F\x61-x7F]++|\\\\[\\\\\`])+\`|[a-z]\w*)(?:\.((?1)))?$/i';
  	private const REGEX_QUOTE           = '/^([\'"])((?:[^\\\\\'"]++|\\\\.)*)\1$/';
  	private const REGEX_PARAMETER       = '/^:(\w+)$/';
  	private const REGEX_COMMAND         = '/^{\?(.+)}$/';
  	private const REGEX_NUMERIC         = '/^-?\d+(\.\d+)?$/';
  	private const REGEX_PDO_PARAM       = '/(?:[^\'"`:]++|(?:(["`\'])(?:[^\\\\"`\']++|\\\\.)*\1))(*SKIP)(*FAIL)|:(\w+)/';

  	private $databaseStatement;
  	private $extracted         = [];
  	private $parameterRequired = [];

  	public function __construct(DatabaseStatement $databaseStatement, string $sql = '')
  	{
  		$this->databaseStatement = $databaseStatement;
  		if ($sql) {
  			$this->parseSyntax($sql);
  		}
  	}

  	public function parseSyntax(string $sql)
  	{
  		$sql             = trim($sql);
  		$this->extracted = $this->extract($sql);

  		return $this;
  	}

  	public function getStatement()
  	{
  		if (!count($this->extracted)) {
  			return '';
  		}

  		return $this->setParam($this->combine($this->extracted));
  	}

  	private function setParam(string $statement)
  	{
  		$parameters = $this->databaseStatement->getParameters();

  		return preg_replace_callback(self::REGEX_PDO_PARAM, function ($matches) use ($parameters) {
  			if (array_key_exists($matches[2], $this->parameterRequired)) {
  				if (!isset($parameters[$matches[2]])) {
  					return "''";
  				}

  				if (Database::PARAM_SET === $this->parameterRequired[$matches[2]]) {
  					$set = [];
  					if (is_array($parameters[$matches[2]])) {
  						foreach ($parameters[$matches[2]] as $text) {
  							$set[] = addslashes($text);
  						}
  					} else {
  						$set[] = addslashes($parameters[$matches[2]]);
  					}

  					return "'" . implode("', '", $set) . "'";
  				}
  				if (Database::PARAM_STRING === $this->parameterRequired[$matches[2]]) {
  					return "'" . addslashes($parameters[$matches[2]]) . "'";
  				}

  				return 'LIKE ' . $this->likeString($parameters[$matches[2]], $this->parameterRequired[$matches[2]]);
  			}

  			return $matches[0];
  		}, $statement);
  	}

  	private function combine(array $clips)
  	{
  		$statement = '';
  		foreach ($clips as $clip) {
  			if (is_array($clip)) {
  				$statement .= ' (' . $this->combine($clip) . ')';
  			} else {
  				$statement .= ' ' . $clip;
  			}
  		}

  		return substr($statement, 1);
  	}

  	private function parse(string $statement)
  	{
  		if (preg_match(self::REGEX_OPERATOR, $statement, $matches, PREG_OFFSET_CAPTURE)) {
  			$left     = trim(substr($statement, 0, $matches[2][1]));
  			$operator = $matches[2][0];
  			$right    = trim(substr($statement, $matches[2][1] + strlen($matches[2][0])));
  			if ('?' === $left && '?' === $right) {
  				new ThrowError('Syntax Error (Missing comparison)');
  			} else {
  				if ('?' === $left) {
  					$left  = $right;
  					$right = '?';
  				}
  			}

  			$left = $this->convert($left);

  			// Quick parameter
  			if ('?' === $right) {
  				if ('column' === $left['type'] && preg_match('/\w+/', $left['column_name'])) {
  					$right = [
  						'type'      => 'parameter',
  						'statement' => ':' . $left['column_name'],
  						'parameter' => $left['column_name'],
  					];
  				} else {
  					new ThrowError('The column name cannot assign as a parameter.');
  				}
  			} else {
  				$right = $this->convert($right);
  			}

  			return $left['statement'] . ' ' . $this->comparison($operator, $right);
  		}

  		$falseComparison = false;
  		if ($statement && '!' === $statement[0]) {
  			$falseComparison = true;
  			$statement       = substr($statement, 1);
  		}

  		$statement = $this->convert($statement);
  		if ('column' !== $statement['type'] && $falseMark) {
  			new ThrowError('Only allow false comparison on column.');
  		}

  		if ('column' === $statement['type']) {
  			return $statement['statement'] . ' = ' . (($falseComparison) ? '0' : '1');
  		}

  		return $statement['statement'];
  	}

  	private function likeString(string $text, string $flag = '')
  	{
      $text = addslashes($text);
  		if (Database::PARAM_CONTAIN === $flag) {
  			return "'%" . $text . "%'";
  		}
  		if (Database::PARAM_START_WITH === $flag) {
  			return "'%" . $text;
  		}
  		if (Database::PARAM_END_WITH === $flag) {
  			$text = $text . "%'";
  		}

  		return $text;
  	}

  	private function comparison(string $operator, array $comparer, array $parameters = [])
  	{
  		if ('!=' === $operator) {
  			// Not equal with comparison
  			$operator = '<>';
  		} elseif ('*=' === $operator || '^=' === $operator || '$=' === $operator) {
  			// LIKE comparison, * is contain, ^ is start with and $ is end with
  			$text = '';
  			if ('parameter' === $comparer['type']) {
  				$this->parameterRequired[$comparer['parameter']] = $operator[0];
          return $comparer['statement'];
  			} else {
  				$text = $this->likeString($comparer[('string' === $comparer['type']) ? 'text' : 'statament'], $operator[0]);
  			}

  			return 'LIKE ' . $text;
  		} elseif ('|=' === $operator) {
  			// IN Set comparison
  			$text = '';
  			if ('parameter' === $comparer['type']) {
  				$text                                            = $comparer['statement'];
  				$this->parameterRequired[$comparer['parameter']] = Database::PARAM_SET;
  			} else {
  				$text = $comparer[('string' === $comparer['type']) ? 'text' : 'statament'];
  			}

  			return 'IN(' . $text . ')';
  		}

  		if ('parameter' === $comparer['type']) {
  			$this->parameterRequired[$comparer['parameter']] = Database::PARAM_STRING;
  		}

  		// Equal with comparison
  		return $operator . ' ' . $comparer['statement'];
  	}

  	private function convert(string $statement, string $operator = '')
  	{
  		if (preg_match(self::REGEX_COLUMN, $statement, $matches)) {
  			if (isset($matches[2])) {
  				return [
  					'type'        => 'column',
  					'statement'   => $statement,
  					'table_name'  => $matches[1],
  					'column_name' => $matches[2],
  				];
  			}

  			return [
  				'type'        => 'column',
  				'statement'   => $statement,
  				'column_name' => $statement,
  			];
  		}

  		if (preg_match(self::REGEX_QUOTE, $statement, $matches)) {
  			return [
  				'type'      => 'string',
  				'statement' => $statement,
  				'text'      => $matches[2],
  			];
  		}

  		if (preg_match(self::REGEX_PARAMETER, $statement, $matches)) {
  			return [
  				'type'      => 'parameter',
  				'statement' => $statement,
  				'parameter' => $matches[1],
  			];
  		}

  		if (preg_match(self::REGEX_COMMAND, $statement, $matches)) {
  			return [
  				'type'      => 'command',
  				'statement' => $matches[1],
  			];
  		}

  		if (preg_match(self::REGEX_NUMERIC, $statement)) {
  			return [
  				'type'      => 'command',
  				'statement' => (float) $statement,
  			];
  		}

  		new ThrowError('Syntax Error (Statement incorrect)');
  	}

  	private function extractStatement(string $statement, &$extracted = [], bool $startWithOperator = false)
  	{
  		while ($statement) {
  			if (preg_match(self::REGEX_WHERE_DELIMITER, $statement, $matches, PREG_OFFSET_CAPTURE)) {
  				if ($matches[2][1] > 0) {
  					if (!count($extracted) && $startWithOperator) {
  						// If the previous clip is a statement or bracketed syntax and
  						// the first clip is not start with operator, throw error
  						new ThrowError('Syntax Error (Missing operator)');
  					}
  					$extracted[] = $this->parse(substr($statement, 0, $matches[2][1]));
  				}
  				$extracted[] = (',' === $matches[2][0]) ? 'AND' : 'OR';
  				$statement   = substr($statement, $matches[2][1] + 1);
  			} else {
  				$extracted[] = $this->parse($statement);
  				$statement   = '';
  			}
  		}
  	}

  	private function extract(string &$content, bool $bracket = false)
  	{
  		$clips = [];
  		while ($content) {
  			// Extract Bracket Syntax
  			if (preg_match(self::REGEX_BRACKET, $content, $matches, PREG_OFFSET_CAPTURE)) {
  				if ('(' === $matches[0][0]) {
  					// If it is start from opening bracket, do deeper extract
  					if ($matches[0][1] > 0) {
  						$this->extractStatement(trim(substr($content, 0, $matches[0][1])), $clips, (bool) count($clips));
  					}
  					$content = substr($content, $matches[0][1] + 1);
  					// Opening Bracket
  					$clips[] = $this->extract($content, true);
  				} else {
  					// Closing Bracket
  					$extracted = trim(substr($content, 0, $matches[0][1]));
  					if ($extracted) {
  						$this->extractStatement($extracted, $clips);
  					}
  					$content = substr($content, $matches[0][1] + 1);

  					// If no clip in braket, throw error
  					if (!count($clips)) {
  						new ThrowError('Syntax Error (Empty bracket)');
  					}

  					// When the braket is closed, ensure the last clip is not a operator
  					$lastStatement = end($clips);
  					if (!is_array($lastStatement) && preg_match('/^AND|OR$/', $lastStatement)) {
  						new ThrowError('Syntax Error (Incompleted statement)');
  					}

  					return $clips;
  				}
  			} else {
  				if ($bracket) {
  					// If it is in a bracket without closing bracket, throw error.
  					new ThrowError('Syntax Error (Missing closing bracket)');
  				}

  				break;
  			}
  		}

  		$content = trim($content);
  		if ($content) {
  			$this->extractStatement($content, $clips);
  			// After extract the remaining statement, ensure the last clip is not a operator
  			$lastStatement = end($clips);
  			if (!is_array($lastStatement) && preg_match('/^AND|OR$/', $lastStatement)) {
  				new ThrowError('Syntax Error (Incompleted statement)');
  			}
  		}

  		return $clips;
  	}
  }
}
