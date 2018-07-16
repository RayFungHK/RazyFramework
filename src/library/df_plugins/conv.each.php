<?php
return function($callback) {
  if ($this->dataType == 'array' || $this->value instanceof \ArrayAccess)) {
    foreach ($this->value as $key => $value) {
      call_user_func($callback, $key, $value);
    }
  }

  return $this;
};
?>
