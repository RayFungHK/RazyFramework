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
  return function () {
    $a = 0;
    $b = 0;
    // Here has a syntax error, missing semicolon
    $c = ($a + $b)
  };
}
