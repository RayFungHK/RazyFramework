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
	if (null === $this->content) {
		return false;
	}
	$count = (isset($this->parameters['count'])) ? (int) ($this->parameters['count']) : 0;

	return ($count > 0) ? str_repeat($this->content, $count) : '';
};
