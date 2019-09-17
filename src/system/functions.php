<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use RazyFramework\RegexHelper;

/**
 * Tidy the path, remove duplicated slash or backslash.
 *
 * @param string $path   The original path
 * @param bool   $ending Add a directory separator at the end of the path
 *
 * @return string The tidied path
 */
function tidy(string $path, bool $ending = false, string $separator = \DIRECTORY_SEPARATOR)
{
	return preg_replace('/[\/\\\\]+/', $separator, $path . ($ending ? $separator : ''));
}

/**
 * Append additional path.
 *
 * @param string       $path      The original path
 * @param array|string $extra,... Extra path to append
 *
 * @return string The path appended extra path
 */
function append(string $path, ...$extra)
{
	$separator = \DIRECTORY_SEPARATOR;
	$protocal  = '';
	if (preg_match('/^(https?:\/\/)(.*)/', $path, $matches)) {
		$protocal  = $matches[1];
		$path      = $matches[2];
		$separator = '/';
	}

	foreach ($extra as $pathToAppend) {
		if (is_array($pathToAppend) && count($pathToAppend)) {
			$path .= $separator . implode($separator, $pathToAppend);
		} elseif (is_scalar($pathToAppend) && strlen($pathToAppend)) {
			$path .= $separator . $pathToAppend;
		}
	}

	return $protocal . tidy($path, false, $separator);
}

/**
 * Append additional path by using backslash.
 *
 * @param string       $path      The original path
 * @param array|string $extra,... Extra path to append
 *
 * @return string The path appended extra path
 */
function bs_append(string $path, ...$extra)
{
	$separator = '/';
	$protocal  = '';
	if (preg_match('/^(https?:\/\/)(.*)/', $path, $matches)) {
		$protocal  = $matches[1];
		$path      = $matches[2];
		$separator = '/';
	}

	foreach ($extra as $pathToAppend) {
		if (is_array($pathToAppend) && count($pathToAppend)) {
			$path .= $separator . implode($separator, $pathToAppend);
		} elseif (is_scalar($pathToAppend) && strlen($pathToAppend)) {
			$path .= $separator . $pathToAppend;
		}
	}

	return $protocal . tidy($path, false, $separator);
}

/**
 * Sort the route by its folder level, deepest is priority.
 *
 * @param array &$routes An array contains the routing path
 */
function sort_path_level(array &$routes)
{
	uksort($routes, function ($path_a, $path_b) {
		$count_a = substr_count(tidy($path_a, true, '/'), '/');
		$count_b = substr_count(tidy($path_b, true, '/'), '/');
		if ($count_a === $count_b) {
			return 0;
		}

		return ($count_a < $count_b) ? 1 : -1;
	});
}

/**
 * Version compare.
 *
 * @param string $requirement A string of required version
 * @param string $version     The version number
 *
 * @return bool Return true if the version is meet requirement
 */
function vc(string $requirement, string $version)
{
	$version = trim($version);
	if (!$version) {
		return true;
	}

	if (!$regex = RegexHelper::GetCache('version-compare')) {
		$regex = new RegexHelper('/^([<>]=?)?\s*(\d+(?:\.\d+){1,2}(?:\-(?<prv>(?:alpha|beta|a|b|RC)\d*)(?:\.(?&prv))*)?)$/i');
	}

	if (!$matches = $regex->match(trim($version))) {
		return false;
	}
	$version = $matches[2] . (!isset($matches['prv']) ? '-z' : '');

	$requirement = explode(',', $requirement);
	foreach ($requirement as $req) {
		if ($matches = $regex->match(trim($req))) {
			$comparison = $matches[1];

			// If no prv in version number that means it is a final version, add -z after the version number to have a correct sorting.
			$req = $matches[2] . (!isset($matches['prv']) ? '-z' : '');

			if ($req === $version && (!$comparison || strpos($comparison, '=') > 0)) {
				if (!$comparison) {
					return true;
				}
			} else {
				$list = [$req, $version];
				natsort($list);

				if (('>' === $comparison[0] && $list[0] === $version) || ('<' === $comparison[0] && $list[0] !== $version)) {
					return false;
				}
			}
		}
	}

	return true;
}

/**
 * Check If the SSL is used.
 *
 * @return bool [description]
 */
function is_ssl()
{
	if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO']) {
		return true;
	}
	if (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS'] || 443 === $_SERVER['SERVER_PORT']) {
		return true;
	}

	return false;
}

/**
 * Generate the file size with the unit.
 *
 * @param float  $size      The file size
 * @param int    $decPoint  sets the number of decimal points
 * @param bool   $upperCase Convert the unit into uppercase
 * @param string $separator The separator between the size and unit
 *
 * @return string The formatted file size
 */
function getFilesizeString(float $size, int $decPoint = 2, bool $upperCase = false, string $separator = '')
{
	$unitScale  = ['byte', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb'];
	$unit       = 'byte';
	$scale      = 0;
	$decPoint   = ($decPoint < 1) ? 0 : $decPoint;

	while ($size >= 1024 && isset($unitScale[$scale + 1])) {
		$size /= 1024;
		$unit = $unitScale[++$scale];
	}

	$size = ($decPoint) ? number_format($size, $decPoint) : (int) $size;

	if ($upperCase) {
		$unit = strtoupper($unit);
	}

	return $size . $separator . $unit;
}

/**
 * Get the visitor IP.
 *
 * @return [type] [description]
 */
function getIP()
{
	$ipaddress = '';
	if (isset($_SERVER['HTTP_CLIENT_IP'])) {
		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
		$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	} elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
		$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	} elseif (isset($_SERVER['HTTP_FORWARDED'])) {
		$ipaddress = $_SERVER['HTTP_FORWARDED'];
	} elseif (isset($_SERVER['REMOTE_ADDR'])) {
		$ipaddress = $_SERVER['REMOTE_ADDR'];
	} else {
		$ipaddress = 'UNKNOWN';
	}

	return $ipaddress;
}
