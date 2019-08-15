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
  $object = $this->getKeyValue();
	if (is_iterable($object->value)) {
		return count($object->value);
	}

	if (is_scalar($object->value)) {
		return strlen($object->value);
	}

	return ($object->value) ? 1 : 0;
};
