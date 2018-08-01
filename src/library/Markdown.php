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

    public function __construct($text = '')
    {
      if (!$text) {
        $this->loadContent($text);
      }
    }

    public function loadFile($path)
    {
      if (!file_exists($path)) {
        new ThrowError('Markdown', '1001', 'Cannot load content from file, file not found.');
      } else {
        $this->loadContent(file_get_contents($path));
      }
    }

    private function loadContent($text)
    {
      $this->defined = array();
      $this->content = '';

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
                $config['pattern'] = '/(?<=\n|\A)' . $config['pattern'] . '/s';
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

    private function isDefined($variable)
    {
      return isset($this->defined[strtolower($variable)]);
    }

    private function getDefined($variable)
    {
      return trim(($this->isDefined($variable)) ? $this->parseURL($this->defined[strtolower($variable)]) : $variable);
    }

    private function parseURL($text)
    {
      $text = trim($text);
      return (preg_match('/^<(.+)>$/', $text, $matches)) ? $matches[1] : $text;
    }

    private function parseVariable($text, $context = false)
    {
      $parsedVariable = array();
      $text = preg_replace_callback(
        '/(!?)\[((?:(?<=\\\\)[\[\]]|[^\[\]]|(?R))+?)(?<!\\\\)\](?:\((.+?)(?<!\\\\)\))?(?:\[(.+?)(?<!\\\\)\])?/',
        function ($matches) use ($context, &$parsedVariable) {
          $guid = '[{#' . sprintf('%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535)) . '}]';
          $parsedVariable[$guid] = $matches[0];

          // Parse image tag
          if ($matches[1]) {
            $altText = $this->getDefined($matches[2]);
            // If the image src is not empty, create an image tag
            if (isset($matches[3]) || (isset($matches[4]) && !$this->isDefined($matches[4]))) {
              $src = (isset($matches[4])) ? $this->getDefined($matches[4]) : trim($matches[3]);
              $parsedVariable[$guid] = '<img src="' . $src . '"' . (($altText) ? ' alt="' . $altText . '"' : '') . ' />';
            }
          } else {
            // Parse herf tag
            $text = $this->parseVariable($matches[2]);

            if (!isset($matches[3]) && !isset($matches[4])) {
              if (!$this->isDefined($text)) {
                // If no defined variable matched
                // Return the parsed variable with wrapped []
                $parsedVariable[$guid] = '[' . $text . ']';
              } else {
                // Else return as a link
                $parsedVariable[$guid] = '<a href="' . $this->getDefined($text) . '">' . $text . '</a>';
              }
            } else {
              // If the link is a defined value [variable]
              if (isset($matches[4])) {
                // If no defined variable matched, no modify applied.
                if ($this->isDefined($matches[4])) {
                  $parsedVariable[$guid] = '<a href="' . $this->getDefined($matches[4]) . '">' . $text . '</a>';
                }
              } else {
                // Assume (text) is a URL
                $parsedVariable[$guid] = '<a href="' . $this->parseURL($matches[3]) . '">' . $text . '</a>';
              }
            }
          }

          return $guid;
        },
        $text
      );

      $text = htmlspecialchars($text);
      return (count($parsedVariable)) ? str_replace(array_keys($parsedVariable), array_values($parsedVariable), $text) : $text;
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

    public function parse()
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
          return str_replace("\n", '<br />', '<p>' . trim($this->parseModifier($this->parseVariable($matches[1]))) . '</p>');
        },
        $content
      );

      return $content;
    }
  }
}
?>
