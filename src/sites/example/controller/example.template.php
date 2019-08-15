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
  	$tplManager = $this->loader->view('template')->queue();
  	$root       = $tplManager->getRootBlock();

  	for ($i = 0; $i < 2; ++$i) {
  		$level1    = $root->newBlock('level1');
  		$level2    = $level1->newBlock('level2');
  		$recursion = $level2->newBlock('level1')->assign([
  			'recursion' => true,
  		]);
      for ($j = 0; $j < 4; ++$j) {
        $jlevel2 = $recursion->newBlock('level2');
        $recursion = $jlevel2->newBlock('level1')->assign([
    			'recursion' => true,
    		]);
      }
  	}

  	echo $tplManager->output();

  	return true;
  };
}
