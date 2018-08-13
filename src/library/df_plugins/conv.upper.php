<?php
return function() {
  $this->chainable = true;
  if ($this->dataType == 'string') {
    return strtoupper($this->value);
  }
  return $this->value;
}
?>
