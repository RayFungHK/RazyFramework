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
	'domains' => [
		/**
		 * The key is the domain and the value is the string of distribution path.
		 * You can set the value as an array for advanced distribution setup.
		 *
		 * The distribution folder must conatins a dist.php
		 *
		 * Basic usage:
		 * 'domain.name' => (string) The module distribution path
		 *
		 * Advanced usage:
		 * (The module folder will not be loaded if it is a distribution folder)
		 * 'domain.name' => (array) [
		 *   'path' => (string) The module distribution path
		 * ]
		 */
		'localhost' => [
			'/'      => append(SYSTEM_ROOT, 'sites'),
			'/admin' => append(SYSTEM_ROOT, 'sites', 'admin'),
		],
	],

  /**
   * The domain alias, it will be used if the domain is not exists
   */
	'alias' => [
		'127.0.0.1' => 'localhost',
	],
];
