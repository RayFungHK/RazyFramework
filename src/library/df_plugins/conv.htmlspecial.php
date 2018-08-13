<?php
return function() {
  $this->chainable = true;
  if ($this->dataType != 'string' && $this->dataType != 'integer' && $this->dataType != 'double') {
    return print_r($this->value, true);
  }
  return htmlspecialchars($this->value);
}
?>
