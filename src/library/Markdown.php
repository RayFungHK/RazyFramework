<?php
namespace Core
{
  class Markdown
  {
    static private $pluginLoaded = false;
    static private $modifiers = array();
    static private $modifierPattern = '';
    static private $paragraphs = array();

    private $content = '';
    private $defined = array();

    public function __construct($text)
    {
      $lines = preg_split('/\n|\r\n?/', $text);
      foreach ($lines as $content) {
        if (preg_match('/^\s*\[([^]]+)\]:(.+)$/', $content, $matches)) {
          $this->defined[$matches[1]] = trim($matches[2]);
        } else {
          $this->content .= $content . "\n";
        }
      }
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
                self::$paragraphs[$config['pattern']] = $config;
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
      }
    }

    private function parseVariable($content)
    {
      return preg_replace_callback(
        '/(!?)\[([^\[\]]*+(?:(?R)[^\[\]]*+)*+)\](?:\(([^)]+)\)|\[([^]]+)\])?/',
        function ($matches) {
          // image
          if (isset($matches[1]) && $matches[1]) {
            $altText = '';
            $imageSrc = '';
            $altText = (isset($this->defined[$matches[2]])) ? $this->defined[$matches[2]] : $matches[2];

            if (isset($matches[3]) && $matches[3]) {
              $imageSrc = $matches[3];
            } elseif (isset($matches[4]) && isset($this->defined[$matches[4]])) {
              $imageSrc = $this->defined[$matches[3]];
            }

            if ($imageSrc) {
              return '<img src="' . $imageSrc . '"' . (($altText) ? ' alt="' . $altText . '"' : '') . ' />';
            }
            return $matches[0];
          }

          $text = (isset($this->defined[$matches[2]])) ? $this->defined[$matches[2]] : $this->parseVariable($matches[2]);

          if (!isset($matches[3])) {
            if (preg_match('/<(.+)>/', $text, $lMatches)) {
              return '<a href="' . $lMatches[1] . '">' . $matches[2] . '</a>';
            }
            return $text;
          }

          if (isset($matches[4])) {
            $link = (isset($this->defined[$matches[4]])) ? $this->defined[$matches[4]] : $matches[4];
          } elseif (isset($matches[3]) && $matches[3]) {
            $link = $matches[3];
          }

          if ($link) {
            if (preg_match('/<(.+)>/', $link, $lMatches)) {
              return '<a href="' . $lMatches[1] . '">' . $text . '</a>';
            }
            return '<a href="' . $link . '">' . $text . '</a>';
          }

          return $matches[0];
        },
        $content
      );
    }

    private function parseModifier($content)
    {
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

    public function result()
    {
      self::LoadPlugin();

      $result = '';
      $content = $this->content;

      foreach (self::$paragraphs as $config) {
        $content = preg_replace_callback(
          $config['pattern'],
          function($matches) use ($config) {
            return $config['callback']->bindTo($this)($matches) . "\n\n";
          },
          $content
        );
      }

      $content = $this->parseVariable($content);

      $contents = preg_split('/\n{2,}/', trim($content));
      foreach ($contents as $section) {
        $section = trim($section);
        if (!preg_match('/<pre.*?>.+<\/pre>/s', $section)) {
          $section = '<p>' . nl2br(trim($section)) . '</p>';
        }
        $result .= $section;
      }

      return $result;
    }
  }
}
?>
