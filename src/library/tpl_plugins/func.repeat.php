<?php
return function() {
  if ($this->content === null) {
    return false;
  }
  $count = (isset($this->parameters['count'])) ? intval($this->parameters['count']) : 0;
  return ($count > 0) ? str_repeat($this->content, $count) : '';
};
?>
