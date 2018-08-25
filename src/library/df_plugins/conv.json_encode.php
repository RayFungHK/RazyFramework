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
	$this->chainable = true;
	if (!function_exists('deeprun')) {
		function deeprun($data) {
			if ($data instanceof \RazyFramework\DataFactory) {
				$data = $data->getArrayCopy();
			}

			if (is_array($data)) {
				foreach ($data as $key => $value) {
					if (is_array($value)) {
						$data[$key] = deeprun($value);
					}
				}
			}

			return $data;
		}
	}

	$this->value = json_encode(deeprun($this->value));
};
