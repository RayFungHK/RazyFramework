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
  class SelectSyntaxParser
  {
  	const REGEX_SKIP             = '(?:(?:(["`\'])(?:[^\\\\"`\']++|\\\\.)*\1)|\[\?(?:[^\\\\\]]++|\\\\.)*?\]|\{\?(?:[^\\\\}]++|\\\\.)*?})(*SKIP)(*FAIL)|';
  	const REGEX_SELECT_DELIMITER = '/' . self::REGEX_SKIP . '([><+\-\*](?!=))(\()?/';
  	const REGEX_BRACKET          = '/' . self::REGEX_SKIP . '[()]/';
  	const REGEX_SQUARE_BRACKET   = '/\[(.+)\]$/';
  	const REGEX_ESCAPE           = '/(?<!\\\\)(?:\\\\\\\\)*\'/';
  	const REGEX_COLUMN_DELIMITER = '/\s*,\s*/';
  	const REGEX_WHERE_SYNTAX     = '/^\?(.+)$/';
  	const REGEX_COLUMN           = '/^(?:\`(?:[\x00-\x5B\x5D-\x5F\x61-x7F]++|\\[\\\`])+\`|[a-z]\w*)$/i';
  	const REGEX_TABLE_NAME       = '/^(\`(?:[\x00-\x5B\x5D-\x5F\x61-x7F]++|\\\\[\\\\\`])+\`|[a-z]\w*)(?:\.(?:((?1))|{\$(\w+)}))?$/i';

  	const JOIN_TYPE              = [
  		'<' => 'LEFT JOIN',
  		'>' => 'RIGHT JOIN',
  		'-' => 'JOIN',
  		'*' => 'CROSS JOIN',
  	];

  	private $databaseStatement;
  	private $extracted         = [];
  	private $parameterRequired = [];
  	private $columns           = [];

  	public function __construct(DatabaseStatement $databaseStatement, string $sql = '')
  	{
  		$this->databaseStatement = $databaseStatement;
  		if ($sql) {
  			$this->parseSyntax($sql);
  		}
  	}

  	public function parseColumn(string $selectColumnStatement)
  	{
  		$selectColumnStatement = trim($selectColumnStatement);
  		$this->columns         = preg_split(self::REGEX_COLUMN_DELIMITER, $selectColumnStatement);

  		return $this;
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
  		$sql = 'SELECT ' . implode(', ', $this->columns) . ' FROM ';

  		return $sql . $this->combine($this->extracted);
  	}

  	private function getTableStatement(array $tableObject)
  	{
  		if ($tableObject['sub_query']) {
  			$dbs = $this->databaseStatement->getSubQuery($tableObject['sub_query']);
  			if (!$dbs) {
  				new ThrowError('Missing subquery ' . $tableObject['sub_query'] . '.');
  			}
  			$tableName = '(' . $dbs->getStatement()->queryString . ')';
  		} else {
  			$tableName = $tableObject['table_name'];
  		}

  		return $tableName . (($tableObject['alias']) ? ' AS ' . $tableObject['alias'] : '');
  	}

  	private function combine(array $clips)
  	{
  		$firstTable = array_shift($clips);

  		$statement = $this->getTableStatement($firstTable);
  		foreach (array_chunk($clips, 2) as $clip) {
  			$joinType = self::JOIN_TYPE[$clip[0]];
  			if (isset($clip[1]['table_name'])) {
  				$tableName = $this->getTableStatement($clip[1]);

  				if ('where-syntax' === $clip[1]['type']) {
  					$statement .= ' ' . $joinType . ' ' . $tableName . ' ON ' . $clip[1]['where_syntax']->getStatement();
  				} elseif ('column' === $clip[1]['type']) {
  					if (!isset($firstTable['column_name']) && !isset($clip[1]['column_name'])) {
  						$joinType = 'NATURAL ' . $joinType;
  						$statement .= ' ' . $joinType . ' ' . $tableName;
  					} else {
  						if ('*' === $clip[0]) {
  							new ThrowError('Cross join is not used with an ON clause.');
  						}
  						$columnName = (isset($clip[1]['column_name'])) ? $clip[1]['column_name'] : $firstTable['column_name'];
  						$statement .= ' ' . $joinType . ' ' . $tableName . ' ON ' . $firstTable['variable'] . '.' . $columnName . ' = ' . $clip[1]['variable'] . '.' . $columnName;
  					}
  				}
  			} else {
  				$statement .= ' ' . $joinType . ' (' . $this->combine($clip[1]) . ')';
  			}
  		}

  		return $statement;
  	}

  	private function parse(string $statement, array $extracted = [])
  	{
  		$alias = '';
  		if (preg_match(self::REGEX_SQUARE_BRACKET, $statement, $matches, PREG_OFFSET_CAPTURE)) {
  			$tableName = substr($statement, 0, $matches[0][1]);
  			$statement = $matches[1][0];
  		} else {
  			$tableName = $statement;
  			$statement = '';
  		}

  		$subquery = '';
  		if (preg_match(self::REGEX_TABLE_NAME, $tableName, $matches)) {
  			if (isset($matches[3])) {
          $alias    = $matches[1];
  				$subquery = $matches[3];
  			} elseif (isset($matches[2])) {
  				$alias     = $matches[1];
  				$tableName = $matches[2];
  			} else {
  				$tableName = $matches[1];
  			}
  		}

  		$parsed = [
  			'table_name' => $tableName,
  			'sub_query'  => $subquery,
  			'alias'      => $alias,
  			'variable'   => ($alias) ? $alias : $tableName,
  		];

  		if (!$statement) {
  			$parsed['type'] = 'column';
  			if (count($extracted)) {
  				if (isset($extracted[0]['column_name'])) {
  					$parsed['column_name'] = $extracted[0]['column_name'];
  				}
  			}

  			return $parsed;
  		}

  		return $parsed + $this->convert($statement);
  	}

  	private function convert(string $statement)
  	{
  		if (preg_match(self::REGEX_COLUMN, $statement, $matches)) {
  			return [
  				'type'        => 'column',
  				'column_name' => $statement,
  			];
  		}

  		if (preg_match(self::REGEX_WHERE_SYNTAX, $statement, $matches)) {
  			return [
  				'type'         => 'where-syntax',
  				'where_syntax' => new WhereSyntaxParser($this->databaseStatement, $matches[1]),
  			];
  		}

  		new ThrowError('Syntax Error');
  	}

  	private function extractStatement(string $statement, &$extracted = [], bool $startWithOperator = false)
  	{
  		while ($statement) {
  			if (preg_match(self::REGEX_SELECT_DELIMITER, $statement, $matches, PREG_OFFSET_CAPTURE)) {
  				if ($matches[2][1] > 0) {
  					if (!count($extracted) && $startWithOperator) {
  						// If the previous clip is a statement or bracketed syntax and
  						// the first clip is not start with operator, throw error
  						new ThrowError('Syntax Error (Missing operator)');
  					}
  					$extracted[] = $this->parse(substr($statement, 0, $matches[2][1]), $extracted);
  				}
  				$extracted[] = $matches[2][0];
  				$statement   = substr($statement, $matches[2][1] + 1);
  			} else {
  				$extracted[] = $this->parse($statement, $extracted);
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
  					if (!is_array($lastStatement) && preg_match('/^[><+\-\*]$/', $lastStatement)) {
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
  			if (!is_array($lastStatement) && preg_match('/^[><+\-\*]$/', $lastStatement)) {
  				new ThrowError('Syntax Error (Incompleted statement)');
  			}
  		}

  		return $clips;
  	}
  }
}
