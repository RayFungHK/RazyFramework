<?php
namespace Core
{
  class Markdown
  {
    static private $pluginLoaded = false;
    static private $modifiers = array();
    static private $modifierPattern = '';
    static private $paragraphs = array();

    private $content = "\n";
    private $defined = array();

    public function __construct($text)
    {
      $lines = preg_split('/\r\n?|\n/', $text);
      foreach ($lines as $content) {
        if (preg_match('/^\h{0,3}\[([^]]+)\]:\h*(.+)$/', $content, $matches)) {
          $value = trim($matches[2]);
          if (preg_match('/^[^\s]+$/', $value)) {
            $this->defined[strtolower($matches[1])] = trim($matches[2]);
          }
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

          if (preg_match('/md\.(paragraph|modifier)\.(.+)\.php/', $node, $matches)) {
            $config = (array) require($pluginFolder . $node);

            if (isset($config['pattern']) && isset($config['callback']) && is_callable($config['callback'])) {
              if ($matches[1] == 'modifier') {
                self::$modifiers[$config['pattern']] = $config['callback'];
              } elseif ($matches[1] == 'paragraph') {
                self::$paragraphs[$config['pattern']] = $config['callback'];
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

        self::$modifierPattern = '/(' . $patternGroup . ')([^\n]+)(?:\1)/';
      }
    }

    private function getVarible($variable, $returnEmpty = false)
    {
      if (isset($this->defined[strtolower($variable)])) {
        return $this->defined[strtolower($variable)];
      }
      return ($returnEmpty) ? '' : $variable;
    }

    private function parseVariable($text, $context = false)
    {
      return preg_replace_callback(
        '/(!?)\[((?:[^\[\]]|(?R))+)(?<!\\\\)\](?:\((.+?)(?<!\\\\)\))?(?:\[(.+?)(?<!\\\\)\])?/',
        function ($matches) use ($context) {
          // Parse image tag
          if (isset($matches[1]) && $matches[1]) {
            $altText = $this->getVarible($matches[2]);
            // If the image src is not empty, create an image tag
            if ($src = (isset($matches[3]) && $matches[3]) ? $matches[3] : $this->getVarible($matches[4])) {
              return '<img src="' . $src . '"' . (($altText) ? ' alt="' . $altText . '"' : '') . ' />';
            }

            return $matches[0];
          }

          // Parse herf tag
          $text = $this->getVarible($matches[2]);
          if (!$context) {
            $text = $this->parseVariable($text, true);
          }

          // If no ('link') after the variable tag
          if (!isset($matches[3])) {
            if (preg_match('/<(.+)>/', $text, $lMatches)) {
              return '<a href="' . $lMatches[1] . '">' . $matches[2] . '</a>';
            }
            return $text;
          }

          if (isset($matches[4])) {
            $link = (isset($this->defined[strtolower($matches[4])])) ? $this->defined[strtolower($matches[4])] : $matches[4];
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
        $text
      );
    }

    private function parseModifier($content)
    {
      if (!count(self::$modifiers)) {
        return $content;
      }

      // Parse Markdown Modifier
      $content = preg_replace_callback(
        self::$modifierPattern,
        function($matches) {
          if (isset(self::$modifiers[$matches[1]])) {
            return call_user_func(self::$modifiers[$matches[1]]->bindTo($this), $matches[2]);
          }
        },
        $content
      );

      return $content;
    }

    public function result()
    {
      self::LoadPlugin();

      $content = $this->content;

      // Parse Markdown Paragraph
      foreach (self::$paragraphs as $pattern => $callback) {
        $content = preg_replace_callback(
          $pattern,
          $callback->bindTo($this),
          $content
        );
      }

      // Wrap the text with the <p> if it has not wrapped by any tags
      $content = preg_replace('/(<([\w]+)[\w ="-]*>.*?<\/\2>)/s', '</p>\1<p>', $content);

      // Clear the empty <p> tags
      $content = preg_replace('/\s*<p>\s*?<\/p>\s*/s', '', '<p>' . $content . '</p>');

      // Convert \n (newline) into line break <br /> in <p>
      $content = preg_replace_callback(
        '/<p>(.+?)<\/p>/s',
        function($matches) {
          return str_replace("\n", '<br />', '<p>' . trim($this->parseModifier(htmlspecialchars($matches[1]))) . '</p>');
        },
        $content
      );
      $content = $this->parseVariable($content);

      return $content;
    }
  }
}
?>
