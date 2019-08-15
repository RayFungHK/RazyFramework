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
	return ($this->value) ? (isset($this->arguments[0]) ? $this->arguments[0] : '') : (isset($this->arguments[1]) ? $this->arguments[1] : '');
};
