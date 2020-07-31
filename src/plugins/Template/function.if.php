<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework {
	return function (string $parameter, ?string $wrapped) {
		$clips  = RegexHelper::ParensParser($parameter);
		$entity = $this;

		$recursive = function (array $clips) use (&$recursive, $entity) {
			if (!$regex = RegexHelper::GetCache('if-comparison')) {
				$regex = new RegexHelper('/^(!?)(?<parametertag>(?<content>\w+|(?<quote>[\'"])(?>(?!\k<quote>)[^\\\\\\\\]|\\\\.)*\k<quote>)|(?:(?<tag>\$\w+(?:\[\d+\]|\.(?P>content))*)(?:\|\w+(?::(?P>content))*)*))(?:\s?([<>]|(?:\s?[|!*$^><])?=)\s?((?P>parametertag)))?$/', 'if-comparison');
			}

			$value = false;
			while ($clip = array_shift($clips)) {
				if (\is_array($clip)) {
					$value = $recursive($clip);
				} else {
					$statements = (new RegexHelper('/\s*[|,]!?\s*/'))->exclude(RegexHelper::EXCLUDE_ALL_QUOTES)->split($clip, RegexHelper::SPLIT_DELIMITER);
					while ($statement = array_shift($statements)) {
						$statement = trim($statement);
						// If the statement include negative operator, reverse the bool of the returned result
						if (preg_match('/^[,|]!$/', $statement)) {
							$statement = $statement[0];
							$value     = !$value;
						}

						if (',' === $statement) {
							if (!$value) {
								return false;
							}
						} elseif ('|' === $statement) {
							if ($value) {
								return true;
							}
						} elseif ($matches = $regex->match($statement)) {
							$leftOprand = $entity->getValueByParameter($matches[2]);
							if (isset($matches[7]) && $matches[7]) {
								$rightOprand = $entity->getValueByParameter($matches[7]);
								$value       = comparison($leftOprand, $rightOprand, $matches[6]);
							} else {
								$value = (bool) $leftOprand;
							}

							// If the negative operator is given, reverse the bool of the result
							if ($matches[1]) {
								$value = !$value;
							}
						} else {
							// If the statement format is invalid, return false
							return false;
						}
					}
				}
			}

			return $value;
		};

		return ($recursive($clips)) ? $this->replaceTag($wrapped) : '';
	};
}
