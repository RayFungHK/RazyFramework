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
	'pattern'  => '\h{0,3}>(?:[^\n]+\n?)+',
	'callback' => function ($matches) {
		$content = preg_replace('/(?<=\n)|\B\h{0,3}>/', '', $matches[0]);

		return '<blockquote>' . str_replace("\n", '<br />', $this->parseVariable(trim($content))) . '</blockquote>';
	},
];
