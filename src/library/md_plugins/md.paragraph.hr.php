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
	'pattern'  => '\h{0,3}([-*_])\1{2,}\h*(?=\n|\Z)',
	'callback' => function ($matches) {
		return '<hr />';
	},
];
