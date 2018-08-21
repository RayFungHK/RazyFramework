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
  class ThrowError
  {
  	public function __construct($errorModule, $errorCode, $message)
  	{
  		if (CLI_MODE) {
  			die('[' . $errorModule . '] #' . $errorCode . ': ' . $message);
  		}
  		ob_get_clean();

  		$errorPageOutput = file_get_contents(MATERIAL_PATH . \DIRECTORY_SEPARATOR . 'errorthrow.html');

  		echo sprintf($errorPageOutput, $errorModule, $errorCode, $message);

  		ob_end_flush();
  		die();
  	}
  }
}
