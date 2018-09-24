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
	'remap'       => '/',
	'version'     => '1.0.0',
	'route'       => [
		'/'                   => 'example.main',
		'dataFactory'         => 'example.dataFactory',
		'throwerror'          => 'example.throwerror',
		'throwerror-thowable' => 'example.throwerror_thowable',
		'profiler'            => 'example.profiler',
	],
	'console' => [
	],
];
