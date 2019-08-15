<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework {
	return function ($array = [], $definekey = false) {
		if (is_array($array) || $array instanceof DataFactory) {
			foreach ($array as $index => $value) {
				$key = ($definekey) ? $value : $index;
				if (!$definekey) {
					$this[$key] = $value;
				} elseif (!array_key_exists($key, $this)) {
					$this[$key] = null;
				}
			}
		}

		return $this;
	};
}
