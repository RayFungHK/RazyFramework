<?php
namespace RazyFramework
{
  class TemplateManager
  {
    private $baseStructure = null;
    private $currentStructure = null;
    private $blockTree = array();
    private $blockPointer = null;
    private $currentBlock = null;
    private $variableList = array();

    static private $environmentVariableList = array();
    static private $outputQueue = array();

    public function __construct($tplPath, $tplName = '')
    {
      // Read file content
      if (!file_exists($tplPath)) {
        new ThrowError('TemplateManager', '1001', 'Template file not exists');
      }

      $tplContent = file($tplPath);
      if ($tplName) {
        $this->tplName = $tplName;
      } else {
        if (($pos = strrpos('/', $tplPath)) !== FALSE) {
          $this->tplName = substr($tplPath, $pos + 1);
        } else {
          $this->tplName = $tplPath;
        }
      }

  		// Convert File Content to TemplateStructure
  		$this->templateStructure = new TemplateStructure($this, '_ROOT', $tplContent);

  		// Create a new queue in Root block
      $rootBlock = new TemplateBlock($this->templateStructure, '_ROOT');
  		$this->blockTree = $rootBlock;

  		// Setup the template structure pointer
  		$this->currentBlock = $this->blockPointer = $rootBlock;

  		// Add current template to loaded template pool
  		//self::$LoadedExTemplate[$this->tplName] = $this;
    }

    public function gotoBlock($blockName)
    {
      $blockName = preg_replace('/\/+/', '/', trim($blockName));
      if ($blockName) {
        if ($blockName[0] == '/') {
          $this->currentBlock = $this->blockPointer = $this->blockTree;
          $blockName = substr($blockName, 1);
        }

        if ($this->blockPointer->hasBlock($blockName)) {
          $this->blockPointer->setPointer($blockName);
          $this->currentBlock = $this->blockPointer;
          return $this;
        }
      } else {
        new ThrowError('TemplateManager', '2001', 'Block Name cannot be empty.');
      }

      new ThrowError('TemplateManager', '2002', 'Block [' . $blockName . '] not found.');
    }

    public function getRootBlock() {
      return $this->blockTree;
    }

    public function globalAssign($variable, $value = null)
    {
  		if (is_array($variable)) {
  			foreach ($variable as $tagName => $value) {
  				$this->globalAssign($tagName, $value);
  			}
  		} else {
  			$this->variableList[$variable] = $value;
  		}
  		return $this;
    }

    public function hasGlobalVariable($variable)
    {
      $variable = trim($variable);
      return array_key_exists($variable, $this->variableList);
    }

    public function getGlobalVariable($variable)
    {
      $variable = trim($variable);
      return (array_key_exists($variable, $this->variableList)) ? $this->variableList[$variable] : null;
    }

    public function output($returnAsValue = false)
    {
      if ($returnAsValue) {
        return $this->blockTree->output();
      }
      echo $this->blockTree->output();
    }

    public function addToQueue($templateName = '')
    {
      $templateName = trim($templateName);
      if (!$templateName) {
        $templateName = sprintf('%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff));
      }
      self::$outputQueue[$templateName] = $this;
      return $this;
    }

    public function __invoke($selector)
    {
      // Remove repeatly slash
      $selector = trim(preg_replace('/\/+/', '/', $selector));
      if (!$selector) {
        new ThrowError('TemplateManager', '3001', 'Selector cannot not be empty');
      }

      $blockSet = new TemplateBlockSet($this->blockTree);
      // If selector only have a slash, return the root block
      if ($selector == '/') {
        return $blockSet;
      } elseif ($selector[0] == '/') {
        $selector = substr($selector, 1);
      }
      return $blockSet->find($selector);
    }

    static public function EnvironmentAssign($variable, $value = null) {
  		if (is_array($variable)) {
  			foreach ($variable as $tagName => $value) {
  				self::EnvironmentAssign($tagName, $value);
  			}
  		} else {
  			self::$environmentVariableList[$variable] = $value;
  		}
    }

    static public function HasEnvironmentAssign($variable)
    {
      $variable = trim($variable);
      return array_key_exists($variable, self::$environmentVariableList);
    }

    static public function GetEnvironmentAssign($variable)
    {
      $variable = trim($variable);
      return (array_key_exists($variable, self::$environmentVariableList)) ? self::$environmentVariableList[$variable] : null;
    }

    static public function OutputQueued()
    {
      if (count(self::$outputQueue)) {
        foreach (self::$outputQueue as $block) {
          $block->output();
        }
      }
    }
  }
}
?>
