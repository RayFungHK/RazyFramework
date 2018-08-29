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
  class DOMParser
  {
  	const DOMTYPE_TEXTNODE = 0;
  	const DOMTYPE_ELEMENT  = 1;
  	const DOMTYPE_COMMENT  = 2;

  	private $domElement;

  	public function __construct(string $html)
  	{
  		$this->domElement = $this->parse($html);
  	}

  	private function parse(string $html)
  	{
  		$domElement = [];

  		$unparsed = $html;
  		// Search the opening delimiter
  		while (preg_match('/(<!--(.*?)-->)|<\s*(!)?\s*([\w-]+)(\s+(?:[^<>\\\\]++|\\\\[<>])+)?>/', $unparsed, $matches, PREG_OFFSET_CAPTURE)) {
  			// Obtain previous text node
  			if ($matches[0][1] > 0) {
  				$textNode = new DOMElement(self::DOMTYPE_TEXTNODE);
  				$textNode->setText(substr($unparsed, 0, $matches[0][1]));
  				$domElement[] = $textNode;
  			}

        $unparsed = substr($unparsed, $matches[0][1] + strlen($matches[0][0]));

  			if ($matches[2][0]) {
  				continue;
  			}

  			if ($matches[1][0]) {
  				// Comment
  				$commentNode = new DOMElement(self::DOMTYPE_COMMENT);
  				$commentNode->setText($matches[1][0]);
  				$domElement[] = $commentNode;
  			} else {
  				$nodeName    = $matches[4][0];
  				$elementNode = new DOMElement(self::DOMTYPE_ELEMENT, $nodeName, (isset($matches[5][0])) ? $matches[5][0] : '');
  				if (preg_match('/area|base|br|hr|embed|iframe|img|input|link|meta|param|source|track/i', $nodeName)) {
  					// Self-Closing Element
  				} else {
  					$parsed = $this->parseClosingTag($unparsed);
  				}
          if ($nodeName == 'ul') {
            print_r($elementNode);
          }
          $domElement[] = $elementNode;
  			}
  		}

  		if ($unparsed) {
  			$textNode = new DOMElement(self::DOMTYPE_TEXTNODE);
  			$textNode->setText($unparsed);
  			$domElement[] = $textNode;
  		}

  		return $domElement;
  	}

  	private function parseClosingTag(string $content, DOMElement &$elementNode)
  	{
  		$nodeName = $elementNode->getNodeName();
  		$unparsed = $content;
  		if (preg_match('/<\s*\/\s*' . $nodeName . '\s*>/', $unparsed, $matches, PREG_OFFSET_CAPTURE)) {
  			if ($matches[0][1] > 0) {
  				$this->parse(substr($unparsed, 0, $matches[0][1]), $elementNode);
  			}
  			$unparsed = substr($unparsed, $matches[0][1] + strlen($matches[0][0]));
  		}

  		return $unparsed;
  	}
  }
}
