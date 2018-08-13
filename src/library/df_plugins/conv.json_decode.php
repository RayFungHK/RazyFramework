<?php
return function($toarray = false) {
  $this->chainable = true;
  if ($this->dataType == 'string') {
    return json_decode($this->value, !!$toarray);
  }
  return $this->value;
}
?>
