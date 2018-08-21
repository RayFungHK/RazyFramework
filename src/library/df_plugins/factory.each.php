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
	foreach ($this as $key => $value) {
		call_user_func($callback, $key, $value);
	}

	return $this;
};
