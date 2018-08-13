<?php
return function() {
  $this->chainable = true;
  if ($this->dataType == 'string') {
    return strtolower($this->value);
  }
  return $this->value;
}
?>
