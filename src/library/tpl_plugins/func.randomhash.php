<?php
return function() {
  $length = (isset($this->parameters['length'])) ? intval($this->parameters['length']) : 4;
  $hash = md5(mt_rand(0, 0xffff));
  return ($length > 32) ? $hash : substr($hash, 0, $length);
};
?>
