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
  	// Create a profiler
  	$profiler = new Profiler();

  	$exampleCode = [
  		'Sample A' => '$a = str_repeat(\' \', 1024 * 1024);',
  		'Sample B' => '$b = str_repeat(\' \', 1024 * 1024 * 32);',
  		'Sample C' => '$getContent = file_get_contents(\'http://youtube.com\');',
  	];

  	$a = str_repeat(' ', 1024 * 1024);
  	$profiler->addSample('Sample A');
  	$b = str_repeat(' ', 1024 * 1024 * 32);
  	$profiler->addSample('Sample B');
  	$getContent = file_get_contents('http://youtube.com');
  	$profiler->addSample('Sample C');

  	$resultEach      = $profiler->report(true, 'Sample A', 'Sample B', 'Sample C', 'Sample D');
  	$resultInit      = $profiler->report(true);
  	$resultFinally   = $profiler->report();
  	$resultToSampleB = $profiler->reportTo('Sample B');
  	$tplManager      = $this->load->view('profiler')->addToQueue();
  	$root            = $tplManager->getRootBlock();

  	foreach ($resultEach as $label => $report) {
  		$sampleBlock = $root->newBlock('sample');
  		$sampleBlock->assign([
  			'label'   => $label,
  			'example' => $exampleCode[$label],
  		]);
  		foreach ($report as $parameter => $value) {
  			if (is_scalar($value)) {
  				if (preg_match('/^output_buffer|memory_/', $parameter)) {
  					$value = Utility::ConvertSizeUnit((float) $value, 2, false, ' ');
  				} else {
  					$value .= ' second';
  				}

  				$sampleBlock->newBlock('statistic')->assign([
  					'parameter' => $parameter,
  					'value'     => $value,
  				]);
  			}
  		}
  	}

  	foreach ($resultInit as $parameter => $value) {
  		if (is_scalar($value)) {
  			if (preg_match('/^output_buffer|memory_/', $parameter)) {
  				$value = Utility::ConvertSizeUnit((float) $value, 2, false, ' ');
  			} else {
  				$value .= ' second';
  			}

  			$root->newBlock('statistic-init-to-c')->assign([
  				'parameter' => $parameter,
  				'value'     => $value,
  			]);
  		}
  	}

  	foreach ($resultFinally as $parameter => $value) {
  		if (is_scalar($value)) {
  			if (preg_match('/^output_buffer|memory_/', $parameter)) {
  				$value = Utility::ConvertSizeUnit((float) $value, 2, false, ' ');
  			} else {
  				$value .= ' second';
  			}

  			$root->newBlock('statistic-a-to-c')->assign([
  				'parameter' => $parameter,
  				'value'     => $value,
  			]);
  		}
  	}

  	foreach ($resultToSampleB as $parameter => $value) {
  		if (is_scalar($value)) {
  			if (preg_match('/^output_buffer|memory_/', $parameter)) {
  				$value = Utility::ConvertSizeUnit((float) $value, 2, false, ' ');
  			} else {
  				$value .= ' second';
  			}

  			$root->newBlock('statistic-init-to-b')->assign([
  				'parameter' => $parameter,
  				'value'     => $value,
  			]);
  		}
  	}

  	$tplManager->output();
  };
}
