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
  class example extends IController
  {
  	public function main()
  	{
      $profiler = new Profiler();
      $profiler->addStep('start');

  		$config     = $this->load->config('general');
  		$tplManager = $this->load->view('main')->addToQueue();
  		$tplManager->output();
      $profiler->addStep('a');
      $a = str_repeat(' ', 10240 * 10240);
      $profiler->addStep('b');
      $a = str_repeat(' ', 1024 * 1024);
      $profiler->addStep('c');
      $a = str_repeat(' ', 1024 * 1024);
      $profiler->addStep('end');
      $r = $profiler->report('b', 'c', 'a');
      print_r($r['report']);
  	}
  }
}
