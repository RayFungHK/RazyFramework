<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return function (string $parameter, ?string $wrapped) {
	$parameters          = $this->extractParams($parameter);
	$parameters['name']  = $parameters['name'] ?? '';
	$parameters['type']  = $parameters['type'] ?? '';
	$parameters['value'] = $parameters['value'] ?? '';

	if ($parameters['name']) {
		$value = (null !== $wrapped) ? $wrapped : $parameters['value'];
		if ('numberic' === $parameters['type']) {
			$value = (float) $value;
		} elseif ('json' === $parameters['type']) {
			$value = json_decode($value, true);
		} elseif ('object' === $parameters['type']) {
			$value = json_decode($value);
		} elseif ('bool' === $parameters['type']) {
			$value = !!$value;
		}
		$this->assign($parameters['name'], $value);
	}

	return '';
};
