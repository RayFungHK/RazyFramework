<?php
return function() {
	if ($this->dataType != 'string') {
		return false;
	}
	return filter_var($this->value, FILTER_VALIDATE_EMAIL);
}
?>
