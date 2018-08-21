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
  class Markdown
  {
  	private static $pluginLoaded    = false;
  	private static $modifiers       = [];
  	private static $modifierPattern = '';
  	private static $paragraphs      = [];

  	private $content        = '';
  	private $defined        = [];
  	private $allowStripTags = false;
  	private $allowableTags  = 'a|b|strong|strike|u|i|table|th|tr|td|thead|tfoot|tbody|pre|code|p|ol|ul|li|br|hr|h1|h2|h3|h4|h5|h6';

  	public function __construct($text = '')
  	{
  		if (!$text) {
  			$this->loadContent($text);
  		}
  	}

  	public function allowStripTags($enable = false)
  	{
  		$this->allowStripTags = (bool) $enable;

  		return $this;
  	}

  	public function setAllowableTags($taglist)
  	{
  		if (is_string($taglist)) {
  			$taglist       = explode(',', $taglist);
  			$allowableTags = [];

  			$this->allowableTags = '';
  			foreach ($taglist as $tag) {
  				if (preg_match('/^\w++$/', $tag)) {
  					$allowableTags[$tag] = $tag;
  				}
  			}
  			$this->allowableTags = implode('|', $allowableTags);
  		}

  		return $this;
  	}

  	public function loadFile($path)
  	{
  		if (!file_exists($path)) {
  			new ThrowError('Markdown', '1001', 'Cannot load content from file, file not found.');
  		} else {
  			$this->loadContent(file_get_contents($path));
  		}

  		return $this;
  	}

  	public function parse()
  	{
  		self::LoadPlugin();

  		$content = $this->content;

  		// Parse Markdown Paragraph
  		foreach (self::$paragraphs as $pattern => $callback) {
  			$content = preg_replace_callback(
		  $pattern,
		  function ($matches) use ($callback) {
		  	return  '</p>' . $callback->bindTo($this)($matches) . '<p>';
		  },
		  $content
		);
  		}

  		// Clear the empty <p> tags
  		$content = preg_replace('/\s*<p>\s*?<\/p>\s*/s', '', '<p>' . $content . '</p>');

  		// Convert \n (newline) into line break <br /> in <p>
  		$content = preg_replace_callback(
		'/<p>(.+?)<\/p>/s',
		function ($matches) {
			return str_replace(
			// Convert the newline to break line
			"\n",
			'<br />',
			// Split the paragraph if it has over two newline
			preg_replace(
			  '/\n{2,}/s',
			  '</p><p>',
			  '<p>' . trim($this->parseModifier($this->parseVariable($matches[1]))) . '</p>'
			)
		  );
		},
		$content
	  );

  		return $content;
  	}

  	private function loadContent($text)
  	{
  		$this->defined = [];
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

  	private static function LoadPlugin()
  	{
  		if (!self::$pluginLoaded) {
  			self::$pluginLoaded = true;
  			$paragraphPattern   = [];
  			$pluginFolder       = __DIR__ . \DIRECTORY_SEPARATOR . 'md_plugins' . \DIRECTORY_SEPARATOR;
  			foreach (scandir($pluginFolder) as $node) {
  				if ('.' === $node || '..' === $node) {
  					continue;
  				}

  				if (preg_match('/md\.(paragraph|modifier)\.(.+)\.php/', $node, $matches)) {
  					$config = (array) require $pluginFolder . $node;

  					if (isset($config['pattern'], $config['callback']) && is_callable($config['callback'])) {
  						if ('modifier' === $matches[1]) {
  							self::$modifiers[$config['pattern']] = $config['callback'];
  						} elseif ('paragraph' === $matches[1]) {
  							$config['pattern']                    = '/(?<=\n|\A)' . $config['pattern'] . '/s';
  							self::$paragraphs[$config['pattern']] = $config['callback'];
  						}
  					}
  				}
  			}

  			uksort(self::$modifiers, function ($a, $b) {
  				if (strlen($a) === strlen($b)) {
  					return 0;
  				}

  				if (strlen($a) < strlen($b)) {
  					return 1;
  				}

  				return -1;
  			});

  			// Group all modifier tag into pattern group
  			$patternGroup = array_keys(self::$modifiers);
  			$patternGroup = implode('|', array_map(function ($value) {
  				return preg_quote($value);
  			}, $patternGroup));

  			self::$modifierPattern = '/(' . $patternGroup . ')([^\n]+?)(?:\1)/';
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
  		$parsedVariable = [];
  		$text           = preg_replace_callback(
		'/(!?)\[((?>[^\[\]\\\\]+|\\\\[\[\]]|(?R))*)\](?:\(((?>[^()\[\]\\\\]+|\\\\[()\[\]])*)\))?(?:\[((?>[^\[\]\\\\]+|\\\\[\[\]])*)\])?/',
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

  		if ($this->allowStripTags) {
  			$text = preg_replace_callback(
		  '/<\h*(?!\/?(' . $this->allowableTags . ')(?=\b))(?>[^<>\\\\]+|\\\\.)*>/s',
		  function ($matches) {
		  	return htmlspecialchars($matches[0]);
		  },
		  $text
		);
  		}

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
		function ($matches) {
			if (isset(self::$modifiers[$matches[1]])) {
				return call_user_func(self::$modifiers[$matches[1]]->bindTo($this), $matches[2]);
			}

			return $matches[0];
		},
		$content
	  );

  		return $content;
  	}
  }
}
