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
	function deeprun($data)
	{
		if (!is_array($data) && $data instanceof \ArrayAccess) {
			$data = $data->getArrayCopy();
		}

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				if (is_array($value) || $value instanceof \ArrayAccess) {
					$data[$key] = deeprun($value);
				}
			}
		}

		return $data;
	}

	return json_encode(deeprun($this));
};
