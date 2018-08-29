<?php

/*
 * This file is part of RazyFramwork.
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
  	protected function __onModuleLoaded()
  	{
  		return true;
  	}

  	public function __onReady()
  	{
  		DataConvertor::CreateConvertor('appendBold', function () {
  			$this->chainable = true;

  			return '<b>' . $this->value . '</b>';
  		});

  		TemplateBlockSet::CreateFilter('odd-filter', function () {
  			return 0 === $this->index % 2;
  		});

  		return true;
  	}

  	public function main()
  	{
  		if (CLI_MODE) {
  			echo 'Welcome to CLI mode';
  			foreach ($this->manager->getScriptParameters() as $param => $value) {
  				echo "\n${param}:" . str_repeat(' ', 12 - strlen($param)) . $value;
  			}
  		} else {
        // Autoloader, load the class file from module folder
  			$sampleClass   = new \sampleClass();
  			$sampleClassNS = new \Custom\objectClass();

        // loader, a bundle of preset function
  			$config     = $this->load->config('general');
        $config['parameter'] = '123';
        $config->commit();
   			$tplmanager = $this->load->view('main');

        // Markdown library
  			$md = new Markdown();
  			$md->loadFile($this->module->getViewPath() . \DIRECTORY_SEPARATOR . 'markdown-sample.txt');
        print_r(new DOMParser($md->parse()));

  			$df = new DataFactory([
  				'name'   => ' Ray Fung ',
  				'gender' => 'male',
  			]);

  			$df('name')->upper()->appendBold();

  			$tplmanager->getRootBlock()->assign([
  				'markdown' => $md->parse(),
  				'showname' => true,
  				'authur'   => $df['name'],
  			]);

  			// Block selector
  			$root  = $tplmanager->getRootBlock();
  			$index = 0;
  			foreach (['Peter', 'May', 'John', 'Sally', 'Karn'] as $name) {
  				$root->newBlock('levelA')->assign([
  					'index' => ++$index,
  					'name'  => $name,
  				]);
  			}

  			$tplmanager('levelA:odd-filter')->assign('name', function ($value) {
  				return $value . ' (Found)';
  			});
        $tplmanager->output();
  		}
  	}

  	public function reroute()
  	{
  		echo 'Re-Route';
  	}

  	public function onMessage()
  	{
  		echo 'onMessage';
  	}

  	public function method()
  	{
  		return 'Method';
  	}

  	public function cli($argA = null, $argB = null)
  	{
  		echo str_repeat('=', 24) . "\n";
  		echo "Here is CLI Mode\n";
  		echo str_repeat('=', 24) . "\n";
  	}
  }
}
