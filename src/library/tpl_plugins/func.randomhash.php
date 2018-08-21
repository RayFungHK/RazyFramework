<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return function () {
	$length = (isset($this->parameters['length'])) ? (int) ($this->parameters['length']) : 4;
	$hash   = md5(mt_rand(0, 0xffff));

	return ($length > 32) ? $hash : substr($hash, 0, $length);
};
