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
  $object = $this->getKeyValue();
	if (is_string($object->value)) {
		$object->value = json_decode($object->value, (bool) $toarray);
	}

	return $this;
};
