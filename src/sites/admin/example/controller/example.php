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
  			'/'       => 'main',
  			'reroute' => 'reroute',
  			'custom'  => 'custom',
  		])->setRoutingPrefix('/');

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

  			$tplmanager = $this->loader->view('main');

  			$im = new Iterator\Manager([
  				'name'   => ' Ray Fung ',
  				'gender' => 'male',
  			]);

  			$im('name')->upper();

  			$tplmanager->getRootBlock()->assign([
  				'showname' => true,
  				'authur'   => $im['name'],
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

  			$elements = new DOM\Element(\file_get_contents('https://www.w3schools.com/css/css_syntax.asp'));

  			foreach (['p', 'h2+p', 'h2~p', '.notranslate', '[class*="sidesection"]', '[name]', 'p:nth-child(2n+1)', 'p:nth-child(odd)', 'p:nth-child(even)'] as $selector) {
  				$selectorBlock = $root->newBlock('selector');
  				$selectorBlock->assign([
  					'selector' => $selector,
  				]);
  				foreach ($elements($selector) as $dom) {
  					$selectorBlock->newBlock('element', $dom->nodeName)->assign([
  						'name'  => $dom->nodeName,
  						'count' => function ($value) {
  							$value = $value ?? 0;

  							return ++$value;
  						},
  					]);
  				}
  			}

  			echo $tplmanager->output();
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
