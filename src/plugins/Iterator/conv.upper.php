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
	if (is_string($object->value)) {
		$object->value= strtoupper($object->value);
	}

	return $this;
};
