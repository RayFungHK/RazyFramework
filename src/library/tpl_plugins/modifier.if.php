<?php
return function() {
	return ($this->value) ?
		(isset($this->arguments[0]) ? $this->arguments[0] : '') :
		(isset($this->arguments[1]) ? $this->arguments[1] : '');
}
?>
