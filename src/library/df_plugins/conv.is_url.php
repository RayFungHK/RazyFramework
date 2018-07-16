<?php
return function() {
	if ($this->dataType != 'string') {
		return false;
	}
	return !!preg_match('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $this->value);
}
?>
