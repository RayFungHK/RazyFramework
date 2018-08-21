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
	'pattern'  => '(?:\h{0,3}[-*+]\h+(?:[^\n]+\n?)+)+',
	'callback' => function ($matches) {
		$contents = explode("\n", $matches[0]);

		$lastPointer = '';

		$result = '<ul>';
		foreach ($contents as $line) {
			if (preg_match('/\h{0,3}([-*+])\h*(.+)/', $line, $matches)) {
				$content = $this->parseModifier($this->parseVariable($matches[2]));
				if (!$lastPointer) {
					$lastPointer = $matches[1];
					$result .= '<li>' . $content;
				} else {
					if (!$matches[1]) {
						$result .= '<br />' . $content;
					} else {
						$result .= ($lastPointer === $matches[1]) ? '</li><li>' . $content : '</li></ul><li>' . $content;
					}
				}
			}
		}
		$result .= '</ul>';

		return $result;
	},
];
