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
  	public static function Parse(string $html)
  	{
  		$domElement = new DOMElement(DOMElement::DOMTYPE_DOCUMENT, '', '');

  		return $domElement->html($html);
  	}
  }
}
