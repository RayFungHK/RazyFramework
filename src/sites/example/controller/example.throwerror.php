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
    throw new ErrorHandler('Error message will display here. If the parameters `debug` has set to `true` in global configuration, the backtrace will be listed as below.');
  };
}
