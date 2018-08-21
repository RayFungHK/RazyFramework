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
  $this->chainable = true;
  if ($this->dataType != 'string' && $this->dataType != 'integer' && $this->dataType != 'double') {
    return print_r($this->value, true);
  }
  return htmlspecialchars($this->value);
}
?>
