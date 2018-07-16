<?php
return function($toarray = false) {
  if ($this->dataType == 'string') {
    $this->value = json_decode($this->value, !!$toarray);
  }
  return ($toarray) ? [] : (object) [];
}
?>
