<?php
return function() {
  if ($this->dataType == 'string') {
    $this->value = strtoupper($this->value);
  }
  return $this->value;
}
?>
