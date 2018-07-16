<?php
namespace Core
{
  class DatabaseQuery
  {
    private $statement = null;
    private $dba = null;

    public function __construct($dba, $statement)
    {
      $this->statement = $statement;
      $this->dba = $dba;
    }

    public function fetch($object = null)
    {
      if (is_array($object) && count($object)) {
        foreach ($object as $mapping => $column) {
          $object[$mapping] = null;
          $this->statement->bindColumn($column, $object[$mapping]);
        }
        $this->statement->fetch(\PDO::FETCH_BOUND);
        return $object;
      } elseif (is_string($object) && class_exists($object)) {
        $this->statement->fetch(\PDO::CLASS, $object);
      } else {
        return $this->statement->fetch(\PDO::FETCH_ASSOC);
      }
    }

    public function fetchAll()
    {
      return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchGroup()
    {
        return $this->statement->fetchAll(\PDO::FETCH_ASSOC|\PDO::FETCH_GROUP);
    }

    public function fetchPair()
    {
        return $this->statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    public function lastInsertID()
    {
  		return (isset($this->dba)) ? $this->dba->lastInsertId() : 0;
    }

    public function affectedRow()
    {
      return (isset($this->statement)) ? $this->statement->rowCount() : 0;
    }
  }
}
?>
