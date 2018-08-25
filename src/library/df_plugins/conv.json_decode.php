<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return function ($toarray = false) {
	$this->chainable = true;
	if ('string' === $this->dataType) {
		$this->value = json_decode($this->value, (bool) $toarray);
	}
};
