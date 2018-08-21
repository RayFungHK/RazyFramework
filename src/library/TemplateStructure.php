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
  class TemplateStructure
  {
  	private $blockName = '';
  	private $blockType = '';
  	private $parentStructure;
  	private $isRoot           = false;
  	private $structurePath    = '';
  	private $structureContent = [];
  	private $structureMapping = [];
  	private $blockQueue       = [];
  	private $queueList        = [];
  	private $blockPointer;
  	private $templateManager;

  	public function __construct($templateManager, $blockName, &$tplContent, $blockType = 'BLOCK', $parentStructure = null)
  	{
  		// Setup the block name, type and parent
  		$this->blockName       = $blockName;
  		$this->blockType       = $blockType;
  		$this->parentStructure = $parentStructure;
  		$this->templateManager = $templateManager;

  		if (null === $parentStructure) {
  			$this->isRoot = true;
  		} else {
  			$this->structurePath = $parentStructure->getPath() . '/' . $blockName;
  		}

  		$blockClosed = false;
  		while (count($tplContent)) {
  			$content = array_shift($tplContent);
  			// Check if current line is a block tag
  			if (preg_match('/^\s*<!\-\- (START|END) ([\w-]+): (.+) \-\->\s*$/', $content, $matches)) {
  				if ('START' === $matches[1]) {
  					$tplObject = new self($templateManager, $matches[3], $tplContent, $matches[2], $this);

  					// Add current block into mapping list
  					$this->structureMapping[$matches[3]] = $tplObject;
  					$this->structureContent[]            = $this->structureMapping[$matches[3]];
  				} elseif ('END' === $matches[1] && $matches[3] === $blockName) {
  					$blockClosed = true;

  					break;
  				} else {
  					new ThrowError('TemplateStructure', '1001', 'Invalid End Tag [' . $matches[3] . '] in current block section [' . $blockName . '].');
  				}
  			} else {
  				// Put current line into content pool
  				$this->structureContent[] = $content;
  			}
  		}

  		if (!$blockClosed && !$this->isRoot) {
  			new ThrowError('TemplateStructure', '1002', 'Block [' . $blockName . '] has not closed.');
  		}
  	}

  	public function getStructureContent()
  	{
  		return $this->structureContent;
  	}

  	public function getPath()
  	{
  		return $this->structurePath;
  	}

  	public function getManager()
  	{
  		return $this->templateManager;
  	}

  	public function getBlockName()
  	{
  		return $this->blockName;
  	}

  	public function hasBlock($blockName)
  	{
  		$blockName = trim($blockName);

  		return isset($this->structureMapping[$blockName]);
  	}

  	public function getBlockStructure($blockName)
  	{
  		$blockName = trim($blockName);
  		if (isset($this->structureMapping[$blockName])) {
  			return $this->structureMapping[$blockName];
  		}

  		return null;
  	}
  }
}
