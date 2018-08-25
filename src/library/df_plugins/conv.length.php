<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return function () {
	if (is_array($this) || 'array' === $this->dataType || $this->value instanceof \ArrayAccess) {
		return count($this->value);
	}

	if ('string' === $this->dataType) {
		return strlen($this->value);
	}

	return (isset($this->value)) ? 1 : 0;
};
