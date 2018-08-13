<?php
return function($callback) {
  if ($this->dataType == 'array' || is_array($this->value)) {
    foreach ($this->value as $key => $value) {
      call_user_func($callback, $key, $value);
    }
  }

  $this->chainable = true;
};
?>
