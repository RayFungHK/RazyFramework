<?php
return function() {
	if ($this->dataType != 'string') {
		return false;
	}
	return !!preg_match('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/', $this->value);
}
?>
