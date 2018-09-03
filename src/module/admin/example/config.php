<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return [
	'module_code' => 'example',
	'author'      => 'Ray Fung',
	'version'     => '1.0.0',
	'remap'       => '/',
	'route'       => [
		// (:any)					Pass all arguments to 'any' method if there is no route was matched
		'(:any)'  => 'example.main',
		'reroute' => 'example.reroute',
		'custom'  => 'example.custom',
	],
	'callable' => [
		'method'    => 'example.method',
		'onMessage' => 'example.onMessage',
	],
];
