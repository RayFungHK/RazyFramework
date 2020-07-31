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
	$parameters = $this->extractParams($parameter);
	$parameters['source'] = trim($parameters['source'] ?? '');
	$parameters['key']    = trim($parameters['key'] ?? 'key');
	$parameters['value']  = trim($parameters['value'] ?? 'value');

	if (!$parameters['source']) {
		return '';
	}
	$content = '';
	$data = $this->getValue($parameters['source']);
	foreach ($data as $key => $value) {
		$this->assign($parameters['key'], $key)->assign($parameters['value'], $value);
		$content .= $this->replaceTag($wrapped ?? '');
	}

	return $content;
};
