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
  class example extends Modular\Controller
  {
  	public function __onInit()
  	{
  		$this->package->addRoute([
  			'/'                   => 'main',
  			'iteratorManager'     => 'iteratorManager',
  			'throwerror'          => 'throwerror',
  			'throwerror-thowable' => 'throwerror_thowable',
  			'profiler'            => 'profiler',
  			'template'            => 'template',
  		])->setRoutingPrefix('/');

  		return true;
  	}

  	public function main()
  	{
  		$tplManager = $this->loader->view('main')->queue();
  		echo $tplManager->output();
      return true;
  	}
  }
}
