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
	'pattern'  => '(?:\h{0,3}(?:\|(?:[^\\\\|\n]+|\\\\.)+)*\|\h*\n?)+',
	'callback' => function ($matches) {
		$columnCount = 0;
		$result = '';
		$contents = explode("\n", trim($matches[0]));
		$separater = false;
		$alignment = [];

		foreach ($contents as $content) {
			$content = rtrim(trim($content), '|');
			preg_match_all('/(?:\|((?:[^\\\\|\n]+|\\\\.)*))/', $content, $column);

			if (!$separater) {
				if (!$columnCount) {
					$columnCount = count($column[0]);
					$tableContent[] = $column;
				} else {
					if (preg_match('/(?:\|\h*:?-+:?\h*)+\|?/', $content)) {
						if ($columnCount !== count($column[0])) {
							// Not a valid table format, return the original content
							return $matches[0];
						}
						$separater = true;
						foreach ($column[1] as $align) {
							if (preg_match('/(:)?-+(:)?/', $align, $aMatches)) {
								if (isset($aMatches[1], $aMatches[2])) {
									$alignment[] = 'center';
								} elseif (isset($aMatches[2])) {
									$alignment[] = 'right';
								} else {
									$alignment[] = 'left';
								}
							}
						}
					} else {
						return $matches[0];
					}
				}
			} else {
				$tableContent[] = $column;
			}
		}

		foreach ($tableContent as $rIndex => $row) {
			if (!$rIndex) {
				$result .= '<thead><tr>';
				for ($cIndex = 0; $cIndex < $columnCount; ++$cIndex) {
					$result .= '<th>' . $this->parseModifier($this->parseVariable(stripcslashes($row[1][$cIndex]))) . '</th>';
				}
				$result .= '</tr></thead><tbody>';
			} else {
				$result .= '<tr>';
				for ($cIndex = 0; $cIndex < $columnCount; ++$cIndex) {
					$result .= '<td align="' . $alignment[$cIndex] . '">' . $this->parseModifier($this->parseVariable(stripcslashes($row[1][$cIndex]))) . '</td>';
				}
				$result .= '</tr>';
			}
		}
		$result .= '</tbody>';

		return '<table>' . $result . '</table>';
	},
];
