<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework
{
  class DOMParser
  {
  	private $domElement;

  	public function __construct(string $html)
  	{
      $this->domElement = new DOMElement(DOMElement::DOMTYPE_DOCUMENT, '', '');
      $this->domElement->html($html);
  	}

  }
}
