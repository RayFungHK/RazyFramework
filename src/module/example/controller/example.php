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
  		$dba = Database::GetConnection('local');
  		Profiler::AddStatistic('mysql_query', function () use ($dba) {
  			return $dba->getQueried();
  		});

  		$config     = $this->load->config('general');
  		$tplManager = $this->load->view('main')->addToQueue();
  		$tplManager->output();
  	}
  }
}
