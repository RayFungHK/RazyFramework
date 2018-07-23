<?php
namespace Core
{
  class Markdown
  {
    static private $pluginLoaded = false;
    static private $modifiers = array();
    static private $modifierPattern = '';
    static private $paragraphs = array();
    static private $paragraphPattern = '';
    private $markdownContent = '';
    private $vaiables = array();
    private $mdTable = null;

    public function __construct($text)
    {
      $this->markdownContent = $this->convert(trim($text));
    }

    static private function LoadPlugin()
    {
      if (!self::$pluginLoaded) {
        self::$pluginLoaded = true;
        $paragraphPattern = array();
        $pluginFolder = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'md_plugins' . DIRECTORY_SEPARATOR;
    		foreach (scandir($pluginFolder) as $node) {
    			if ($node == '.' || $node == '..') {
    				continue;
    			}

          if (preg_match('/md\.(.+)\.php/', $node, $matches)) {
            $config = (array) require($pluginFolder . $node);
            if (isset($config['type']) && isset($config['callback']) && is_callable($config['callback'])) {
              if ($config['type'] == 'modifier' && isset($config['tag'])) {
                self::$modifiers[$config['tag']] = $config;
              } elseif ($config['type'] == 'paragraph' && isset($config['pattern'])) {
                $paragraphName = preg_replace('/[^\w]/', '_', $matches[1]);
                $paragraphPattern[] = '(?<' . $paragraphName . '>' . $config['pattern'] . ')';
                self::$paragraphs[$paragraphName] = $config;
              }
            }
          }
    		}

        uksort(self::$modifiers, function($a, $b) {
          if (strlen($a) == strlen($b)) {
            return 0;
          }

          if (strlen($a) < strlen($b)) {
            return 1;
          }

          return -1;
        });

        // Group all modifier tag into pattern group
        $patternGroup = array_keys(self::$modifiers);
        $patternGroup = implode('|', array_map(function($value) {
          return preg_quote($value);
        }, $patternGroup));

        self::$modifierPattern = '/(' . $patternGroup . ')(.+?)(?:\1)/';

        // Group all paragraph pattern
        self::$paragraphPattern = '/' . implode('|', $paragraphPattern) . '/';
      }
    }

    private function parseModifier($content)
    {
      self::LoadPlugin();
      // italic, bold, strike and underline
      $content = preg_replace_callback(
        self::$modifierPattern,
        function($matches) {
          if (isset(self::$modifiers[$matches[1]])) {
            return call_user_func(self::$modifiers[$matches[1]]['callback']->bindTo($this), $matches[2]);
          }
        },
        $content
      );

      return $content;
    }

    private function parseParagraph($content)
    {
      if (preg_match('/^ {4,}(.+)/', $content, $matches)) {
        // pre (Start from 4 spaces)
        return '<pre>' . htmlspecialchars($matches[1]) . '</pre>';
      } else {
        $content = trim($content);
        if (preg_match('/(#{0,6})(.+)\1?/', $content, $matches)) {
          // header block (Start from #)
          $level = strlen($matches[1]);
          return '<h' . $level . '>' . $this->parseModifier(trim($matches[2])) . '</h' . $level . '>';
        } elseif (preg_match('/^|$/', $content, $matches)) {
          // Tables
        }
      }

      return $content;
    }

    private function convert($text)
    {
      $result = '';
      $lines = preg_split('/\n|\r\n?/', $text);
      foreach ($lines as $content) {
        if ($content) {
          if (self::$paragraphPattern && preg_match(self::$paragraphPattern, $content, $matches)) {
            print_r($matches);
          } else {
            $result .= '<p>' . $this->parseModifier($content) . '</p>';
          }
        }
      }
      return $result;
    }

    public function result()
    {
      return $this->markdownContent;
    }
  }
}
?>
