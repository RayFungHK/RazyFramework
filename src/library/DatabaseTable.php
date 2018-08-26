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
  class DatabaseTable
  {
  	private $tableName    = '';
  	private $columnList   = [];
  	private $aiDeclared   = false;

  	private $defaultCharacterSet = [
  		'big5'     => 'big5_chinese_ci',
  		'dec8'     => 'dec8_swedish_ci',
  		'cp850'    => 'cp850_general_ci',
  		'hp8'      => 'hp8_english_ci',
  		'koi8r'    => 'koi8r_general_ci',
  		'latin1'   => 'latin1_swedish_ci',
  		'latin2'   => 'latin2_general_ci',
  		'swe7'     => 'swe7_swedish_ci',
  		'ascii'    => 'ascii_general_ci',
  		'ujis'     => 'ujis_japanese_ci',
  		'sjis'     => 'sjis_japanese_ci',
  		'hebrew'   => 'hebrew_general_ci',
  		'tis620'   => 'tis620_thai_ci',
  		'euckr'    => 'euckr_korean_ci',
  		'koi8u'    => 'koi8u_general_ci',
  		'gb2312'   => 'gb2312_chinese_ci',
  		'greek'    => 'greek_general_ci',
  		'cp1250'   => 'cp1250_general_ci',
  		'gbk'      => 'gbk_chinese_ci',
  		'latin5'   => 'latin5_turkish_ci',
  		'armscii8' => 'armscii8_general_ci',
  		'utf8'     => 'utf8_general_ci',
  		'ucs2'     => 'ucs2_general_ci',
  		'cp866'    => 'cp866_general_ci',
  		'keybcs2'  => 'keybcs2_general_ci',
  		'macce'    => 'macce_general_ci',
  		'macroman' => 'macroman_general_ci',
  		'cp852'    => 'cp852_general_ci',
  		'latin7'   => 'latin7_general_ci',
  		'utf8mb4'  => 'utf8mb4_general_ci',
  		'cp1251'   => 'cp1251_general_ci',
  		'utf16'    => 'utf16_general_ci',
  		'utf16le'  => 'utf16le_general_ci',
  		'cp1256'   => 'cp1256_general_ci',
  		'cp1257'   => 'cp1257_general_ci',
  		'utf32'    => 'utf32_general_ci',
  		'binary'   => 'binary',
  		'geostd8'  => 'geostd8_general_ci',
  		'cp932'    => 'cp932_japanese_ci',
  		'eucjpms'  => 'eucjpms_japanese_ci',
  		'gb18030'  => 'gb18030_chinese_ci',
  	];

  	public function __construct(string $tableName)
  	{
  		$this->tableName = $tableName;
  	}

  	public function createColumn(string $name, $type = Database::COLUMN_TEXT, $setting = [])
  	{
  		$name = trim($name);

  		if (!is_array($setting)) {
  			$setting = [];
  		}

  		if (!isset($this->columnList[$name])) {
  			switch ($type) {
			  case Database::COLUMN_AUTO_ID:
				  $setting['datatype']       = 'INT';
				  $setting['auto_increment'] = true;
				  $setting['index_type']     = 'primary';
				  $this->presetSetting($setting, [
				  	'length'        => '8',
				  	'default_value' => 0,
				  	'no_null'       => true,
				  ]);
				  if ($setting['auto_increment'] && $this->aiDeclared) {
				  	// Error: Only allow one Auto Increment Column
				  	new ThrowError('Database', '2001', 'One table only allowed to have one Auto Increment Column');
				  }
				  $this->aiDeclared     = true;

				  break;
			  case Database::COLUMN_TEXT:
				  $setting['datatype'] = 'VARCHAR';
				  $this->presetSetting($setting, [
				  	'length'        => '255',
				  	'default_value' => '',
				  	'no_null'       => true,
				  ]);

				  break;
			  case Database::COLUMN_LONG_TEXT:
				  $setting['datatype'] = 'LONGTEXT';
				  $this->presetSetting($setting, [
				  	'default_value' => null,
				  	'no_null'       => false,
				  ]);

				  break;
			  case Database::COLUMN_INT:
				  $setting['datatype'] = 'INT';
				  $this->presetSetting($setting, [
				  	'length'        => '8',
				  	'default_value' => '0',
				  	'no_null'       => true,
				  ]);

				break;
				case Database::COLUMN_BOOLEAN:
				  $setting['datatype'] = 'TINYINT';
				  $this->presetSetting($setting, [
				  	'length'        => '1',
				  	'default_value' => '0',
				  	'no_null'       => true,
				  ]);

			break;
			  case Database::COLUMN_DECIMAL:
				  $setting['datatype'] = 'DECIMAL';
				  $this->presetSetting($setting, [
				  	'length'        => '8,2',
				  	'default_value' => '0',
				  	'no_null'       => true,
				  ]);

				  break;
			  case Database::COLUMN_TIMESTAMP:
					$setting['datatype'] = 'TIMESTAMP';
					$this->presetSetting($setting, [
						'no_null' => true,
					]);

					break;
			  case Database::COLUMN_DATETIME:
						$setting['datatype'] = 'DATETIME';
						$this->presetSetting($setting, [
							'no_null'       => false,
							'default_value' => null,
						]);

					break;
			  case Database::COLUMN_JSON:
						$setting['datatype'] = 'JSON';
						$this->presetSetting($setting, [
							'no_null' => true,
						]);

					break;
			  case Database::COLUMN_DATE:
						$setting['datatype'] = 'DATE';
						$this->presetSetting($setting, [
							'no_null'       => false,
							'default_value' => null,
						]);

					break;
			  case Database::COLUMN_CUSTOM:
			  default:
					if (!array_key_exists('datatype', $setting) || !preg_match('/^(BIT|(TINY|MEDIUM)(TEXT|BLOB|INT)|(SMALL|BIG)?INT|REAL|DOUBLE|FIXED|FLOAT|DEC(IMAL)?|NUMERIC|DATE|TIME(STAMP)?|DATETIME|YEAR|(VAR)?(CHAR|BINARY)|(LONG)?(TEXT|BLOB)|ENUM|SET|JSON|BOOL(EAN)?)$/i', $setting['datatype'])) {
						new ThrowError('Database', '2002', $setting['datatype'] . ' is not a valid data type.');
					}
					$this->presetSetting($setting, [
						'length'        => '255',
						'default_value' => '',
						'no_null'       => true,
					]);

				  break;
			}

  			$setting['datatype']     = strtoupper($setting['datatype']);
  			$this->columnList[$name] = $setting;
  		}

  		return $this;
  	}

  	public function getSyntax()
  	{
  		$indexKey = [
  			'primary'  => [],
  			'index'    => [],
  			'unique'   => [],
  			'fulltext' => [],
  			'spatial'  => [],
  		];

  		$commands = [];
  		foreach ($this->columnList as $columnName => $column) {
  			$syntax = '`' . $columnName . '`';

  			if (array_key_exists('length', $column)) {
  				if (preg_match('/(BIT|BOOL(EAN)?|DATE(TIME)?|(TINY|MEDIUM)?BLOB|TEXT|GEOMETRY|JSON)/i', $column['datatype'])) {
  					unset($column['length']);
  				} else {
  					if (preg_match('/(REAL|DOUBLE|FLOAT|DEC(IMAL)?|NUMERIC|FIXED)/i', $column['datatype'])) {
  						$column['length'] = max((float) ($column['length']), 1);
  					} else {
  						$column['length'] = max((int) ($column['length']), 0);
  						if ('YEAR' === $column['datatype']) {
  							if (2 !== $column['length'] && 4 !== $column['length']) {
  								$column['length'] = 4;
  							}
  						}
  					}
  				}
  			}
  			$syntax .= ' ' . $column['datatype'] . ((isset($column['length'])) ? '(' . $column['length'] . ')' : '');
  			$syntax .= (!$column['no_null']) ? ' NULL' : ' NOT NULL';

  			if (array_key_exists('auto_increment', $column) && $column['auto_increment']) {
  				$syntax .= ' AUTO_INCREMENT';
  				$indexKey['primary'][] = $columnName;
  			} elseif (array_key_exists('index_type', $column)) {
  				$indexKey[$column['index_type']][] = $columnName;
  			}

  			if (isset($column['default_value']) && (!array_key_exists('auto_increment', $column) || !$column['auto_increment'])) {
  				$syntax .= " DEFAULT '" . $column['default_value'] . "'";
  			}

  			$commands[] = $syntax;
  		}

  		$output = 'CREATE TABLE ' . $this->tableName . ' (';

  		// Primary key
  		if (count($indexKey['primary'])) {
  			$commands[] = 'PRIMARY KEY(`' . implode('`, `', $indexKey['primary']) . '`)';
  		}
  		unset($indexKey['primary']);

  		foreach ($indexKey as $index_type => $columns) {
  			foreach ($columns as $column) {
  				$commands[] = strtoupper($index_type) . '(`' . $column . '`)';
  			}
  		}

  		$output .= implode(', ', $commands) . ') ENGINE InnoDB;';

  		return $output;
  	}

  	private function presetSetting(array &$setting, array $preset)
  	{
  		foreach ($preset as $name => $value) {
  			if (!array_key_exists($name, $setting)) {
  				$setting[$name] = $value;
  			}
  		}

  		return $this;
  	}
  }
}
