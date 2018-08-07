<?php
namespace RazyFramework
{
  class TemplateStructure
  {
    private $blockName = '';
    private $blockType = '';
    private $parentStructure = null;
    private $isRoot = false;
    private $structurePath = '';
    private $structureContent = array();
    private $structureMapping = array();
    private $blockQueue = array();
    private $queueList = array();
    private $blockPointer = null;
    private $templateManager = null;

    public function __construct($templateManager, $blockName, &$tplContent, $blockType = 'BLOCK', $parentStructure = null) {
      // Setup the block name, type and parent
      $this->blockName = $blockName;
      $this->blockType = $blockType;
      $this->parentStructure = $parentStructure;
      $this->templateManager = $templateManager;

      if (is_null($parentStructure)) {
        $this->isRoot = true;
      } else {
        $this->structurePath = $parentStructure->getPath() . '/' . $blockName;
      }

      while (count($tplContent)) {
        $content = array_shift($tplContent);
     		// Check if current line is a block tag
        if (preg_match('/<!\-\- (START|END) ([\w-]+): (.+) \-\->/i', $content, $matches)) {
   				if ($matches[1] == 'START') {
            $tplObject = new TemplateStructure($templateManager, $matches[3], $tplContent, $matches[2], $this);

            // Add current block into mapping list
            $this->structureMapping[$matches[3]] = $tplObject;
            $this->structureContent[] = $this->structureMapping[$matches[3]];
          } elseif ($matches[1] == 'END') {
            break;
          } else {
            new ThrowError('TemplateStructure', '1001', 'Invalid End Tag [' . $matches[3] . '] in current block section [' . $blockName . '].');
          }
        } else {
   				// Put current line into content pool
   				$this->structureContent[] = $content;
   			}
      }
 		}

    public function getStructureContent()
    {
      return $this->structureContent;
    }

    public function getPath() {
      return $this->structurePath;
    }

    public function getManager() {
      return $this->templateManager;
    }

    public function getBlockName() {
      return $this->blockName;
    }

    public function hasBlock($blockName) {
      $blockName = trim($blockName);
      return isset($this->structureMapping[$blockName]);
    }

    public function getBlockStructure($blockName) {
      $blockName = trim($blockName);
      if (isset($this->structureMapping[$blockName])) {
        return $this->structureMapping[$blockName];
      }
      return null;
    }
  }
}
?>
