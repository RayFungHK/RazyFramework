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
  	$tplManager = $this->loader->view('iteratorManager')->queue();
  	$data       = [
  		'Country' => [
  			'HK' => 'Hong Kong',
  			'TW' => 'Taiwan',
  			'CN' => 'China',
  		],
  		'Description' => '  This is a Testing Variable.   ',
  		'Email'       => 'abc@ccc.com',
  	];

  	$im = new Iterator\Manager($data);

  	$root = $tplManager->getRootBlock();
  	foreach ($im as $index => $value) {
  		$root->newBlock('data')->assign([
  			'index' => $index,
  			'value' => $value,
  		]);
  	}

  	$is_email = var_export($im('Email')->is_email(), true);

  	$root->assign([
  		'json'     => $im('Country')->json_encode()->getValue(),
  		'trim'     => $im('Description')->trim()->getValue(),
  		'is_email' => $is_email,
  	]);

  	echo $tplManager->output();

    return true;
  };
}
