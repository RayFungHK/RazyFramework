<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework\DOM\Control
{
  use RazyFramework\DOM\Base;

  /**
   * The INPUT control.
   */
	class Password extends Base
	{
		/**
		 * Password constructor.
		 *
		 * @param string $name the name "id" value
		 * @param string $id   the attribute "id" value
		 */
		public function __construct(string $name = '', string $id = '')
		{
			$this->tag = 'input';
			$this->setAttribute('type', 'password');
			parent::__construct($name, $id);
		}
	}
}
