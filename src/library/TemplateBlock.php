<?php
namespace RazyFramework
{
  class TemplateBlock
  {
    private $tagName = '';
    private $variableList = array();
    private $structure = null;
    private $blockList = array();
    static private $modifiers = array();
    static private $dynamicModifiers = array();

    public function __construct($structure, $tagName)
    {
      $this->tagName = $tagName;
      if (get_class($structure) != 'RazyFramework\\TemplateStructure') {
        new ThrowError('TemplateBlock', '1001', 'Invalid TemplateStructure object.');
      }
      $this->structure = $structure;
    }

    public function getBlockList($blockName)
    {
      return (isset($this->blockList[$blockName])) ? $this->blockList[$blockName] : array();
    }

    public function blockCount($blockName)
    {
      return (isset($this->blockList[$blockName])) ? count($this->blockList[$blockName]) : 0;
    }

    public function hasBlock($blockName, $tagName = '')
    {
      $blockName = trim($blockName);
      if ($tagName) {
        if (isset($this->blockList[$blockName][$tagName])) {
          return true;
        }
      } else {
        return $this->structure->getBlockStructure($blockName);
      }
    }

    public function newBlock($blockName, $tagName = '')
    {
      $blockName = trim($blockName);
      if ($this->structure->hasBlock($blockName)) {
        $tagName = trim($tagName);
        if (isset($this->blockList[$blockName][$tagName])) {
          return $this->blockList[$blockName][$tagName];
        } else {
          $templateBlock = new TemplateBlock($this->structure->getBlockStructure($blockName), $tagName);
          if ($tagName) {
            $this->blockList[$blockName][$tagName] = $templateBlock;
          } else {
            $this->blockList[$blockName][] = $templateBlock;
          }
        }
      } else {
        new ThrowError('TemplateBlock', '2001', 'Block [' . $blockName . '] not found.');
      }
      return $templateBlock;
    }

    public function assign($variable, $value = null)
    {
  		if (is_array($variable)) {
  			foreach ($variable as $tagName => $value) {
  				$this->assign($tagName, $value);
  			}
  		} else {
  			$this->variableList[$variable] = $value;
  		}
  		return $this;
    }

    public function output()
    {
      $seperatedBlock = array();
      $outputContent = '';

      // Get the structure content
      $structureContent = $this->structure->getStructureContent();

      foreach ($structureContent as $content) {
        // If current line isn't a string, TemplateStructure found
        if (!is_string($content)) {
          // Get TemplateStructure Block Name
          $blockName = $content->getBlockName();

          // Define a temporary name for post process
          $tempName = '{__POSTPARSE#' . sprintf('%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff)) . '}';

          // Put nest structure content into seperated list
          $seperatedBlock[$tempName] = '';
          if (isset($this->blockList[$blockName]) && count($this->blockList[$blockName])) {
            foreach ($this->blockList[$blockName] as $block) {
              $seperatedBlock[$tempName] .= $block->output();
            }
          }
          $outputContent .= $tempName;
        } else {
          $outputContent .= $content;
        }
      }

			// Search variable tag, pettern: {$variable_tag(|modifier(:parameter)*)*}
			$outputContent = $this->parseTag($outputContent);

      // Put back sub structure into output content
      if (count($seperatedBlock)) {
        $outputContent = str_replace(array_keys($seperatedBlock), array_values($seperatedBlock), $outputContent);
      }

      return $outputContent;
    }

    public function getValue($tagname)
    {
      if (array_key_exists($tagname, $this->variableList)) {
        return $this->variableList[$tagname];
      }
      return '';
    }

  	private function parseTag($outputContent)
    {
      // Search variable tag
      // Function Tag pettern: {func_name( parameter="value")*}
      // Variable Tag pettern: {$variable(|modifier(:"value")*)*}
			return preg_replace_callback(
        '/\{(?|(?:(\$?)(\w+)((?:\|\w+(?::(?>\w+|"(?:[^"\\\\]|\\\\.)*")?)*)*))|(?:()(\w+)((?:\h+\w+(?:=(?>\w+|"(?>[^"\\\\]|\\\\.)*"))*)*)))\}(?>((?>.|(?R))+){\/\1\2})?/si',
        function($matches) {
          if ($matches[1] == '$') {
            $tagname = $matches[2];
            $value = '';

            // Search Modifier
            $clipsCount = preg_match_all('/\|(\w+)((?::(?|(?:\w+)|"(?:[^"\\\\]|\\\\.)*")?)*)/i', $matches[3], $clips, PREG_SET_ORDER);

            // Find assigned value
    				if (array_key_exists($tagname, $this->variableList)) {
    					// Block level variable tag
    					$value = $this->variableList[$tagname];
    				} elseif ($this->structure->getManager()->hasGlobalVariable($tagname)) {
    					// Global level variable tag
    					$value = $this->structure->getManager()->getGlobalVariable($tagname);
    				} elseif (TemplateManager::HasEnvironmentVariable($tagname)) {
    					// Global level variable tag
    					$value = TemplateManager::GetEnvironmentVariable($tagname);
    				} elseif ($clipsCount == 0) {
    					return $matches[0];
    				}

            $bindObject = new \stdClass();

    				// If variable tag includes modifier clips, start extract the modifier
    				if ($clipsCount) {
    					foreach ($clips as $clip) {
    						// Get the function name and parameters string
    						$funcname = $clip[1];

    						// Check the plugin is exists or not
    						if (self::GetModifier('modifier.' . $funcname)) {
    							$bindObject->arguments = array();
    							// Extract the parameters
    							if (isset($clip[2])) {
    								$clipsCount = preg_match_all('/:(?|(\w+)()|(?:"((?:[^"\\\\]|\\\\.)*)(")))?/', $clip[2], $params, PREG_SET_ORDER);
    								foreach ($params as $match) {
                      if (isset($match[1])) {
    										if ($match[1] == 'true') {
    											$bindObject->arguments[] = true;
    										} elseif ($match[1] == 'false') {
    											$bindObject->arguments[] = false;
    										} else {
    											// If the parameter quoted by double quote, the string with backslashes
    											// that recognized by C-like \n, \r ..., octal and hexadecimal representation will be stripped off
    											$bindObject->arguments[] = ($match[2] == '"') ? stripcslashes($match[1]) : stripslashes($match[1]);
    										}
                      } else {
                        $bindObject->arguments[] = '';
                      }
    								}
    							}

    							array_unshift($parameters, $value);
    							// Execute the variable tag function
    							$value = $this->parseTag(self::CallModifier('modifier', $funcname, $bindObject));
    						}
    					}
            }

            // Balanced variable tag found, if return value is not false or null
            // Return the wrapped content
            if (isset($matches[4])) {
              return ($value) ? $this->parseTag($matches[4]) : '';
            }

            return $value;
          } else {
            $funcname = $matches[2];
  					$clipsCount = preg_match_all('/\s+(\w+)(?:=(?|(\w+)()|"((?:[^"\\\\]|\\\\.)*)(")))?/', $matches[3], $clips, PREG_SET_ORDER);

            $bindObject = new \stdClass();
            $bindObject->parameters = array();
            $bindObject->content = null;

  					if (self::GetModifier('func.' . $funcname)) {
  						$parameters = array();
  						if (count($clips)) {
  							foreach ($clips as $clip) {
                  $value = true;
  								if (array_key_exists(2, $clip)) {
  									$value = ($clip[3] == '"') ? stripcslashes($clip[2]) : $clip[2];
  								}
  								$bindObject->parameters[$clip[1]] = $value;
  							}
  						}

              if (isset($matches[4])) {
                $bindObject->content = $this->parseTag($matches[4]);
              }

              // Execute the variable tag function
              $result = self::CallModifier('func', $funcname, $bindObject);
              return ($result === false) ? $matches[0] : $result;
            }
            return '';
          }
          return $matches[0];
        },
        $outputContent
      );
  	}

    public function hasVariable($variable)
    {
      $variable = trim($variable);
      return array_key_exists($variable, $this->variableList);
    }

    public function getVariable($variable)
    {
      $variable = trim($variable);
      return (array_key_exists($variable, $this->variableList)) ? $this->variableList[$variable] : null;
    }

    static public function CreateModifier(string $name, callable $callback)
    {
      $name = trim($name);
      if (preg_match('/^[\w-]+$/', $name)) {
        $modifier = 'modifier.' . $name;
        if (!isset(self::$modifiers[$modifier])) {
          self::$dynamicModifiers[$modifier] = null;
        }

        if (is_callable($callback)) {
          self::$dynamicModifiers[$modifier] = $callback;
        }
      }
    }

    static public function CreateFunctionTag(string $name, callable $callback)
    {
      $name = trim($name);
      if (preg_match('/^[\w-]+$/', $name)) {
        $modifier = 'func.' . $name;
        if (!isset(self::$modifiers[$modifier])) {
          self::$dynamicModifiers[$modifier] = null;
        }

        if (is_callable($callback)) {
          self::$dynamicModifiers[$modifier] = $callback;
        }
      }
    }

    static private function GetModifier($modifier)
    {
      if (!array_key_exists($modifier, self::$modifiers)) {
        self::$modifiers[$modifier] = null;
        $pluginFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl_plugins' . DIRECTORY_SEPARATOR . $modifier . '.php';
        if (file_exists($pluginFile)) {
          $callback = require $pluginFile;
          if (is_callable($callback)) {
            self::$modifiers[$modifier] = $callback;
            return $callback;
          }
        }
      }

      if (array_key_exists($modifier, self::$dynamicModifiers)) {
        return self::$dynamicModifiers[$modifier];
      }
      return self::$modifiers[$modifier];
    }

    static private function CallModifier($type, $modifier, object $bindObject)
    {
      $modifierName = $type . '.' . $modifier;
      if (!($modifier = self::GetModifier($modifierName))) {
        new ThrowError('TemplateBlock', '3001', 'Cannot load [' . $modifierName . '] modifier function.');
      }

      return call_user_func_array(
        \Closure::bind($modifier, $bindObject),
        (property_exists($bindObject, 'arguments')) ? $bindObject->arguments : array()
      );
    }
  }
}
?>
