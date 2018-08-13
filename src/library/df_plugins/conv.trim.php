<?php
return function() {
  $this->chainable = true;
  if ($this->dataType != 'string' && $this->dataType != 'integer' && $this->dataType != 'double') {
    $this->value = print_r($this->value, true);
  }
  return trim($this->value);
}
?>
