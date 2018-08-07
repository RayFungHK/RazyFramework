<?php
namespace RazyFramework
{
  class TemplateBlockSet extends \ArrayObject
  {
    static private $filterMapping = array();

    public function __construct($blockList)
    {
      parent::__construct((is_array($blockList)) ? $blockList : [$blockList]);
    }

    public function find($selector)
    {
      // Trim selector, remove repeatly slash
      $selector = trim(preg_replace('/\/+/', '/', $selector . '/'));
      if (preg_match('/^(?:(?:[\w-]+)(?:\[(?|!\w+|\w+(?:(?|(?:!?=)|(?:=[\^*$|]))(?|\w+|(?:"(?:[^"\\\\]|\\\\.)*"))?))\])*(:[\w-]+(?:\((?|\w+|(?:"(?:[^"\\\\]|\\\\.)*"))\))?)*\/)+$/', $selector)) {
        // Get the current level block list
        $blockList = $this->getArrayCopy();

        // Remove the first & last slash
        $selector = trim($selector, '/');

        // Extract Block Path
        $pathClips = explode('/', $selector);
        if (count($pathClips)) {
          foreach ($pathClips as $path) {
            // Extract condition and filter function tag
            $pathCount = preg_match('/([\w-]+)((?:\[(?|!\w+|\w+(?:(?|(?:!?=)|(?:=[\^*$|]))(?|\w+|(?:"(?:[^"\\\\]|\\\\.)*"))?))\])*)((?::[\w-]+(?:\((?|\w+|(?:"(?:[^"\\\\]|\\\\.)*"))\))?)?)/i', $path, $clip);
            $blockName = $clip[1];
            $assignFilter = array();
            $functionFilter = null;
            $blocks = array();

            // Extract condition tag
            if (isset($clip[2])) {
              preg_match_all('/\[(?|(?:(!)(\w+))|(?:()(\w+)(?:(?|(!?=)|(=[\^*$|]))(?|(\w+)|(?:"((?:[^"\\\\]|\\\\.)*)")))?))\]/i', $clip[2], $matches, PREG_SET_ORDER);
              foreach ($matches as $match) {
                $assignFilter[] = $match;
              }
            }

            // Extract filter function tag
            if (isset($clip[3])) {
              if (preg_match_all('/^:([\w-]+)(?:\((?|([\w+-]*)()|(?:"((?:[^"\\\\]|\\\\.)*)(")))\))?$/i', $clip[3], $matches, PREG_SET_ORDER)) {
                $functionFilter = $matches[0];
              }
            }

            // Get all next level block from block list
            $nextBlockList = array();
            if (count($blockList)) {
              foreach ($blockList as $block) {
                if ($block->hasBlock($blockName)) {
                  $nextBlockList = array_merge($nextBlockList, $block->getBlockList($blockName));
                }
              }
            }

            // If filter function tag was found, start filtering
            // Arguments: $index, $block, $source, $arg
            if (count($nextBlockList) && count($functionFilter)) {
              // Clone the current block list
              $source = $nextBlockList;

              foreach ($nextBlockList as $index => $block) {
                // Define the arguments
                $parameters = array();
                $parameters[] = $index;
                $parameters[] = $block;
                $parameters[] = $source;
    						if (self::FilterExists('filter.' . $functionFilter[1])) {
                  if (isset($functionFilter[2])) {
                    // If the parameter quoted by double quote, the string with backslashes
                    // that recognized by C-like \n, \r ..., octal and hexadecimal representation will be stripped off
                    $parameters[] = ($functionFilter[3] == '"') ? stripcslashes($functionFilter[2]) : stripslashes($functionFilter[2]);
                  }
                }

                // If filter function return false, remove current block from the list
                if (!self::CallFilter('filter.' . $functionFilter[1], $parameters)) {
                  unset($nextBlockList[$index]);
                }
              }
            }

            if (count($nextBlockList) && count($assignFilter)) {
              foreach ($nextBlockList as $index => $block) {
                foreach ($assignFilter as $filter) {
                  $negative = ($filter[1] == '!') ? true : false;
                  $tagName = $filter[2];

                  $valueDefined = false;
                  // Check the value is defined & not empty
                  if ($block->hasAssign($tagName) && $block->getAssign($tagName)) {
                    $valueDefined = true;
                  }

                  if (!$negative) {
                    $valueDefined = !$valueDefined;
                  }

                  if (!$valueDefined) {
                    unset($nextBlockList[$index]);
                    break;
                  }

                  // If operator exists, start condition filter
                  if (isset($filter[3])) {
                    $operator = $filter[3];
                    $comparison = ($filter[5] == '"') ? stripcslashes($filter[4]) : $filter[4];
                    $value = $block->getAssign($tagName);
                    if (
                      (is_string($value) && (
                        // Equal
                        ($operator == '=' && $comparison != $value) ||
                        // Not Equal
                        ($operator == '!=' && $comparison == $value) ||
                        // Contain
                        ($operator == '=*' && strpos($value, $comparison) === FALSE) ||
                        // Start With
                        ($operator == '=^' && substr($value, 0, strlen($comparison)) != $comparison) ||
                        // End With
                        ($operator == '=$' && substr($value, -strlen($comparison)) != $comparison)
                      )) ||
                      (is_array($value) &&
                        // Element in List
                        ($operator == '=|' && !in_array($comparison, $value))
                      )
                    ) {
                      if (!$negative) {
                        unset($nextBlockList[$index]);
                        break;
                      }
                    }
                  }
                }
              }
            }

            $blockList = $nextBlockList;
            if (count($blockList) == 0) {
              break;
            }
          }
          return new TemplateBlockSet($blockList);
        }
      } else {
        new ThrowError('TemplateBlockSet', '1001', 'Invalid selector');
      }
    }

    public function each($callback)
    {
      if (is_callable($callback)) {
        if (count($this)) {
          foreach ($this as $block) {
            call_user_func($callback->bindTo($block));
          }
        }
      } else {
        new ThrowError('TemplateBlockSet', '2001', 'Invalid callback function for each method');
      }
      return $this;
    }

    public function assign($variable, $value = null)
    {
      if (count($this)) {
        foreach ($this as $block) {
          $block->assign($variable, $value);
        }
      }
      return $this;
    }

    public function filter($callback)
    {
      if (is_callable($callback)) {
        if (count($this)) {
          foreach ($this as $index => $block) {
            if (!call_user_func($callback->bindTo($block))) {
              unset($this[$index]);
            }
          }
        }
      } else {
        new ThrowError('TemplateBlockSet', '2001', 'Invalid callback function for each method');
      }
      return $this;
    }

    static private function FilterExists($filter)
    {
      if (!array_key_exists($filter, self::$filterMapping)) {
        self::$filterMapping[$filter] = null;
        $pluginFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl_plugins' . DIRECTORY_SEPARATOR . $filter . '.php';
        if (file_exists($pluginFile)) {
          $callback = require $pluginFile;
          if (is_callable($callback)) {
            self::$filterMapping[$filter] = $callback;
          }
        }
      }

      return isset(self::$filterMapping[$filter]);
    }

    static private function CallFilter($filter, $args)
    {
      if (!self::FilterExists($filter)) {
        new ThrowError('TemplateBlockSet', '3001', 'Cannot load [' . $filter . '] filter function.');
      }
      return call_user_func_array(self::$filterMapping[$filter], $args);
    }
  }
}
?>
