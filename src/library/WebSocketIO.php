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
  class WebSocketIO
  {
  	public static function Mask($payload, $type = 'text', $masked = true)
  	{
  		$length    = strlen($payload);
  		$frameHead = [];
  		$frame     = '';

  		$type = trim(strtolower($type));
  		switch ($type) {
  			case 'binary':
  			  $frameHead[0] = 130;

  			  break;
  			case 'close':
  			  $frameHead[0] = 136;

  			  break;
  			case 'ping':
  			  $frameHead[0] = 137;

  			  break;
  			case 'pong':
  			  $frameHead[0] = 138;

  			  break;
  			case 'text':
  			default:
  			  $frameHead[0] = 129;

  			  break;
  		}

  		if ($length > 65535) {
  			$binary      = str_split(sprintf('%064b', $length), 8);
  			$frameHead[] = ($masked) ? 255 : 127;
  			for ($i = 0; $i < 8; ++$i) {
  				$frameHead[] = bindec($binary[$i]);
  			}

  			if ($frameHead[2] > 127) {
  				//$this->close(1004);
  				return false;
  			}
  		} elseif ($length > 125) {
  			$binary      = str_split(sprintf('%016b', $length), 8);
  			$frameHead[] = ($masked) ? 254 : 126;
  			$frameHead[] = bindec($binary[0]);
  			$frameHead[] = bindec($binary[1]);
  		} else {
  			$frameHead[] = ($masked) ? $length + 128 : $length;
  		}

  		foreach ($frameHead as $index => $binary) {
  			$frameHead[$index] = chr($frameHead[$index]);
  		}

  		if ($masked) {
  			$mask = [];
  			for ($i = 0; $i < 4; ++$i) {
  				$mask[$i] = chr(rand(0, 255));
  			}
  			$frameHead += $mask;
  		}

  		$frame = implode('', $frameHead);
  		for ($i = 0; $i < $length; ++$i) {
  			$frame .= ($masked) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
  		}

  		return $frame;
  	}

  	public static function Unmask($data, &$decoded = '')
  	{
  		if (!isset($decoded)) {
  			$decoded = [
  				'text'   => '',
  				'mask'   => null,
  				'opcode' => null,
  			];
  		}

  		$firstByte  = sprintf('%08b', ord($data[0]));
  		$maskedByte = sprintf('%08b', ord($data[1]));

  		$header = [
  			'fin'    => ord($data[0]) & 0x77,
  			'rsv1'   => bindec($firstByte[1]),
  			'rsv2'   => bindec($firstByte[2]),
  			'rsv3'   => bindec($firstByte[3]),
  			'opcode' => bindec(substr($firstByte, 4, 4)),
  			'masked' => ('1' === $maskedByte[0]),
  			'length' => 0,
  			'mask'   => '',
  		];

  		if (!$decoded['opcode']) {
  			$decoded['opcode'] = $header['opcode'];
  		}

  		$codedData = '';
  		$length    = ($header['masked']) ? ord($data[1]) & 127 : ord($data[1]);

  		if (1 !== $header['fin'] && $decoded['mask']) {
  			$codedData = substr($data, 0);
  			for ($i = 0; $i < strlen($codedData); ++$i) {
  				$decoded['text'] .= $codedData[$i] ^ $decoded['mask'][$i % 4];
  			}
  		} else {
  			if ($header['masked']) {
  				if (126 === $length) {
  					$decoded['mask'] = substr($data, 4, 4);
  					$codedData       = substr($data, 8);
  				} elseif (127 === $length) {
  					$decoded['mask'] = substr($data, 10, 4);
  					$codedData       = substr($data, 14);
  				} else {
  					$decoded['mask'] = substr($data, 2, 4);
  					$codedData       = substr($data, 6);
  				}

  				for ($i = 0; $i < strlen($codedData); ++$i) {
  					$decoded['text'] .= $codedData[$i] ^ $decoded['mask'][$i % 4];
  				}
  			} else {
  				if (126 === $length) {
  					$decoded['text'] .= substr($data, 4);
  				} elseif (127 === $length) {
  					$decoded['text'] .= substr($data, 10);
  				} else {
  					$decoded['text'] .= substr($data, 2);
  				}
  			}
  		}
  	}
  }
}
