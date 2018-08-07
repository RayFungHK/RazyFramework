<?php
namespace RazyFramework
{
  class DatabaseTable
  {
    private $dbConn = null;
    private $tableName = '';
    private $columnList = array();
    private $parmaryKey = array();
    private $hasAICol = false;

    private $defaultCharacterSet = array(
      'big5' => 'big5_chinese_ci',
      'dec8' => 'dec8_swedish_ci',
      'cp850' => 'cp850_general_ci',
      'hp8' => 'hp8_english_ci',
      'koi8r' => 'koi8r_general_ci',
      'latin1' => 'latin1_swedish_ci',
      'latin2' => 'latin2_general_ci',
      'swe7' => 'swe7_swedish_ci',
      'ascii' => 'ascii_general_ci',
      'ujis' => 'ujis_japanese_ci',
      'sjis' => 'sjis_japanese_ci',
      'hebrew' => 'hebrew_general_ci',
      'tis620' => 'tis620_thai_ci',
      'euckr' => 'euckr_korean_ci',
      'koi8u' => 'koi8u_general_ci',
      'gb2312' => 'gb2312_chinese_ci',
      'greek' => 'greek_general_ci',
      'cp1250' => 'cp1250_general_ci',
      'gbk' => 'gbk_chinese_ci',
      'latin5' => 'latin5_turkish_ci',
      'armscii8' => 'armscii8_general_ci',
      'utf8' => 'utf8_general_ci',
      'ucs2' => 'ucs2_general_ci',
      'cp866' => 'cp866_general_ci',
      'keybcs2' => 'keybcs2_general_ci',
      'macce' => 'macce_general_ci',
      'macroman' => 'macroman_general_ci',
      'cp852' => 'cp852_general_ci',
      'latin7' => 'latin7_general_ci',
      'utf8mb4' => 'utf8mb4_general_ci',
      'cp1251' => 'cp1251_general_ci',
      'utf16' => 'utf16_general_ci',
      'utf16le' => 'utf16le_general_ci',
      'cp1256' => 'cp1256_general_ci',
      'cp1257' => 'cp1257_general_ci',
      'utf32' => 'utf32_general_ci',
      'binary' => 'binary',
      'geostd8' => 'geostd8_general_ci',
      'cp932' => 'cp932_japanese_ci',
      'eucjpms' => 'eucjpms_japanese_ci',
      'gb18030' => 'gb18030_chinese_ci'
    );

    public function __construct($dbConn, $tableName)
    {
      $this->dbConn = $dbConn;
      $this->tableName = $tableName;
    }

    public function addColumn($columnName, $setting = array())
    {
      $columnName = trim($columnName);
      if (!isset($this->columnList[$columnName])) {
        $this->columnList[$columnName] = array(
          'datatype' => null,
          'length' => null,
          'default_value' => null,
          'enumset' => null,
          'no_null' => true,
          'is_parmary_key' => false,
          'is_ai' => false,
          'character_set' => null,
          'collate' => null
        );
      }

      if (isset($setting['datatype'])) {
        $this->columnList[$columnName]['datatype'] = strtoupper(trim($setting['datatype']));
        if (!preg_match_all('/^(BIT|(?:TINY|SMALL|MEDIUM|BIG)?INT|REAL|DOUBLE|FLOAT|DECIMAL|NUMERIC|DATE|TIME(?:STAMP)?|DATETIME|YEAR|(?:VAR)?CHAR|(?:VAR)?BINARY|(?:TINY|MEDIUM|LONG)?(?:TEXT|BLOB)|ENUM|SET|JSON)/i', $setting['datatype'], $matches, PREG_SET_ORDER)) {
          // Error: Duplicated Column Name
          new ThrowError('Database', '2002', 'Duplicated Column Name');
        }

        if (isset($setting['length']) && $setting['length'] != null) {
          $this->columnList[$columnName]['length'] = trim($setting['length']);
        }
      } else {
        // Error: Missing Column Datatype
        new ThrowError('Database', '2001', 'Missing Column Datatype');
      }

      if (isset($setting['default_value'])) {
        $this->columnList[$columnName]['default_value'] = ($setting['default_value'] != null) ? strval($setting['default_value']) : null;
      }

      if (isset($setting['enumset'])) {
        $this->columnList[$columnName]['enumset'] = (array)$setting['enumset'];
      }

      if (isset($setting['no_null'])) {
        $this->columnList[$columnName]['no_null'] = !!$setting['no_null'];
      }

      if (isset($setting['is_ai'])) {
        $this->columnList[$columnName]['is_ai'] = !!$setting['is_ai'];
        if ($setting['is_ai'] && $this->hasAICol) {
          // Error: Only allow one Auto Increment Column
          new ThrowError('Database', '2003', 'Only allow one Auto Increment Column');
        }
        $this->hasAICol = true;
        $this->parmaryKey[] = $columnName;
      }

      return $this;
    }

    public function getSyntax()
    {
      $createSyntax = 'CREATE TABLE ' . $this->tableName . ' (';
      $columnSyntax = array();
      foreach ($this->columnList as $columnName => $column) {
        $syntax = '`' . $columnName . '`';

        $datatype = $column['datatype'];
        if ($column['length'] != null) {
          if (preg_match('/(BIT|(?:TINY|SMALL|MEDIUM|BIG)?INT|TIME(?:STAMP)?|DATETIME|(?:VAR)?CHAR|(?:VAR)?BINARY)|(?:TINY|MEDIUM|LONG)?(?:TEXT|BLOB)/i', $column['datatype'])) {
            $column['length'] = intval($column['length']);
            if ($column['length'] <= 0) {
              $column['length'] = null;
            }
          } elseif (preg_match('/(REAL|DOUBLE|FLOAT|DECIMAL|NUMERIC)/i', $column['datatype'])) {
            if (preg_match_all('/(\d+)(?:,\s*(\d+))?/', $column['length'], $matches, PREG_SET_ORDER, 0)) {
              $column['length'] = $matches[1] . ',' . $matches[2];
            } else {
              $column['length'] = null;
            }
          } else {
            $column['length'] = null;
          }
        }
        $syntax .= ' ' . $datatype . (($column['length'] != null) ? '(' . $column['length'] . ')' : '');
        $syntax .= (!$column['no_null']) ? ' NULL' : ' NOT NULL';

        if ($column['is_ai']) {
          $syntax .= ' AUTO_INCREMENT';
          $column['is_parmary_key'] = true;
        }

        if (isset($column['default_value'])) {
          $syntax .= " DEFAULT '" . $column['default_value'] . "'";
        }
        $colSyntaxList[] = $syntax;
      }

      $createSyntax .= implode(', ', $colSyntaxList);

      if (count($this->parmaryKey)) {
        $createSyntax .= ', PRIMARY KEY(`' . implode('`, `', $this->parmaryKey) . '`)';
      }
      $createSyntax .= ') ENGINE InnoDB;';
      return $createSyntax;
    }
  }
}
?>
