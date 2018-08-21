<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return function ($callback) {
	if ('array' === $this->dataType || is_array($this->value)) {
		foreach ($this->value as $key => $value) {
			call_user_func($callback, $key, $value);
		}
	}

	$this->chainable = true;
};
