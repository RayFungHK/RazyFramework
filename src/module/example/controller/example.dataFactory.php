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
  	$tplManager = $this->load->view('dataFactory')->addToQueue();
  	$data       = [
  		'Country' => [
  			'HK' => 'Hong Kong',
  			'TW' => 'Taiwan',
  			'CN' => 'China',
  		],
  		'Description' => '  This is a Testing Variable.   ',
  		'Email'       => 'abc@ccc.com',
  	];

  	$datafactory = new DataFactory($data);

  	$root = $tplManager->getRootBlock();
  	foreach ($datafactory as $index => $value) {
  		$root->newBlock('data')->assign([
  			'index' => $index,
  			'value' => $value,
  		]);
  	}

  	$is_email = var_export($datafactory('Email')->is_email(), true);

  	$root->assign([
  		'json'     => $datafactory('Country')->json_encode()->getValue(),
  		'trim'     => $datafactory('Description')->trim()->getValue(),
  		'is_email' => $is_email,
  	]);

  	$tplManager->output();
  };
}
