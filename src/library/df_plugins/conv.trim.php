<?php
return function() {
  if ($this->dataType != 'string' && $this->dataType != 'integer' && $this->dataType != 'double') {
    $this->value = print_r($this->value, true);
  }
  $this->value = trim($this->value);
}
?>
