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
	'pattern'  => '(?>\h{0,3}```(\w*\h*)\n(.+?)(?>\n```(?!\h{4})|\Z)|((?>    |\t)[^\n]+\n?)+)',
	'callback' => function ($matches) {
		if (isset($matches[3])) {
			$content = preg_replace('/(?<=\n|\A)(?:    |\t)/', '', $matches[0]);

			return '<pre><code>' . htmlspecialchars(trim($content)) . '</code></pre>';
		}
		$content = preg_replace('/^\n+|\n+$/', '', $matches[2]);

		return '<pre' . (($matches[1]) ? ' language="' . $matches[1] . '"' : '') . '><code>' . htmlspecialchars($content) . '</code></pre>';
	},
];
