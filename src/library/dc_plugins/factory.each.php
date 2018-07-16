<?php
return function($callback) {
  foreach ($this as $key => $value) {
    call_user_func($callback, $key, $value);
  }

  return $this;
};
?>
