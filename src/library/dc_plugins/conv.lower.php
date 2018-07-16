<?php
return function() {
  if ($this->dataType == 'string') {
    $this->value = strtolower($this->value);
  }
  return $this->value;
}
?>
