<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework\DOM
{
  use RazyFramework\RegexHelper;

  /**
   * An object contains the DOM element and its structure.
   */
  class Element
  {
  	/**
  	 * DOMDocument object.
  	 *
  	 * @var \DOMNode
  	 */
  	private $document;

  	/**
  	 * Mixed.
  	 *
  	 * @param mixed $content An object of HTML or DOMNode
  	 */
  	public function __construct($content = null)
  	{
  		if (is_string($content)) {
  			$this->document = new \DOMDocument();
  			$this->document->loadHTML($content, \LIBXML_COMPACT | \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD | \LIBXML_NOERROR);
  		} elseif ($content instanceof \DOMNodeList) {
  			$this->document = new \DOMDocument();
  		} elseif ($content instanceof \DOMNode) {
  			if ($content instanceof \DOMDocument) {
  				$this->document = $content;
  			} else {
  				$this->document = new \DOMDocument();
  				$this->document->appendChild($this->document->importNode($content, true));
  			}
  		} else {
  			$this->document = new \DOMDocument();
  		}
  	}

  	/**
  	 * Magic method invoke, you can use a formatted string like CSS selector to find the DOM element.
  	 *
  	 * @param string $selector The string of selector
  	 *
  	 * @return Collecion A collection object contains the element
  	 */
  	public function __invoke(string $selector)
  	{
  		if (!$regex = RegexHelper::GetCache('dom-split')) {
  			$regex = new RegexHelper('/,/', 'dom-split');
  			$regex->exclude(RegexHelper::EXCLUDE_SINGLE_QUOTE | RegexHelper::EXCLUDE_DOUBLE_QUOTE | RegexHelper::EXCLUDE_ALL_BRACKETS);
  		}

  		$chips = $regex->split($selector);

  		if (!$regex = RegexHelper::GetCache('dom-selector')) {
  			$regex = new RegexHelper('/(?|\A|\s*([>~+])\s*)([#.]?[\w-]+)(\[\w+(?:[*^$|]?=(?:([\'"])(?:[^\\\\\\\\"]|\\\\.)*\4|[^\[\]\'"]+))?\])*(:[\w-]+\((?:[^()\'"]+|([\'"])(?:[^\\\\\\\\"]|\\\\.)*\6)\))*/', 'dom-selector');
  		}

  		$element = new self();
  		foreach ($chips as $clip) {
        if (!$regex->combination($clip)) {
          throw new ErrorHandler('Invalid DOM query.');
        }
  			$domQuery = $regex->extract($clip);
  			foreach ($domQuery as $query) {
  				$operator = $query[1];
  				$name     = $query[2];
  				$attr     = $query[3] ?? '';
  				$filter   = $query[5] ?? '';

          if ($operator == '>') {
            
          }
  			}
  		}

  		exit;
  	}

  	/**
  	 * Parse the HTML content and replace the children.
  	 *
  	 * @param string $content The HTML to set as the children
  	 *
  	 * @return self Chainable
  	 */
  	public function html(string $content)
  	{
  		$this->children = [];
  		$result         = self::ParseDOM($content);
  		foreach ($result->getChildren() as $child) {
  			$this->children[] = $child;
  		}

  		return $this;
  	}

  	/**
  	 * Append a DOM element object into children.
  	 *
  	 * @param array|Element|string $element The DOM element to append or an arroy contains the ELement
  	 *
  	 * @return self Chainable
  	 */
  	public function append($element)
  	{
  		if (is_array($element)) {
  			foreach ($element as $ele) {
  				$this->append($ele);
  			}
  		} elseif (is_string($element)) {
  			$this->children[] = new self(self::TYPE_TEXTNODE, $element);
  		} elseif ($element instanceof self) {
  			$this->children[] = $element;
  		}

  		return $this;
  	}

  	/**
  	 * Get the children.
  	 *
  	 * @return Collection A collection object contains the element
  	 */
  	public function getChildren()
  	{
  		return $this->children;
  	}

  	public function getTagName()
  	{
  		return $this->name;
  	}
  }
}
