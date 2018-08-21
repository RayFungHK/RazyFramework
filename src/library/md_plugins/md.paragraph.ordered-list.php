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
	'pattern'  => '(?:\h{0,3}\d+\.\h+(?:[^\n]+\n?)+)+',
	'callback' => function ($matches) {
		$contents = explode("\n", $matches[0]);

		$result = '<ol>';
		$first = false;
		foreach ($contents as $line) {
			if (preg_match('/\h{0,3}(\d+\.)\h*(.+)/', $line, $matches)) {
				$content = $this->parseModifier($this->parseVariable($matches[2]));
				if (!$first) {
					$result .= '<li>' . $content;
				} else {
					$result .= (!$matches[1]) ? '<br />' . $content : '</li><li>' . $content;
				}
			}
		}
		$result .= '</ol>';

		return $result;
	},
];
