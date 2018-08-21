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
	'pattern'  => '([^\n]+)\n([\-=])\2+(?=\Z)\n?',
	'callback' => function ($matches) {
		$level = ('=' === $matches[2]) ? 1 : 2;

		return '<h' . $level . '>' . $this->parseModifier($this->parseVariable(trim($matches[1]))) . '</h' . $level . '>';
	},
];
