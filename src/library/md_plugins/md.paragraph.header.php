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
	'pattern'  => '\h{0,3}(#{1,6})\h+([^#\n]+)',
	'callback' => function ($matches) {
		$level = strlen($matches[1]);

		return '<h' . $level . '>' . $this->parseModifier($this->parseVariable($matches[2])) . '</h' . $level . '>';
	},
];
