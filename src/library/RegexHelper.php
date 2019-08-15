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
	/**
	 * RegexHelper class.
	 */
	class RegexHelper
	{
		/**
		 * Exclude the text wrapped by any qoutes, include single quote, double quote and grace accent.
		 *
		 * @var int
		 */
		const EXCLUDE_ALL_QUOTES = 0b000000111;

		/**
		 * Exclude the text wrapped by single quote.
		 *
		 * @var int
		 */
		const EXCLUDE_SINGLE_QUOTE = 0b000000001;

		/**
		 * Exclude the text wrapped by double quote.
		 *
		 * @var int
		 */
		const EXCLUDE_DOUBLE_QUOTE = 0b000000010;

		/**
		 * Exclude the text wrapped by grace accent.
		 *
		 * @var int
		 */
		const EXCLUDE_GRACE_ACCENT = 0b000000100;

		/**
		 * Exclude the text wrapped by any brackets, include round bracket, square bracket, curly bracket and arrow bracket.
		 *
		 * @var int
		 */
		const EXCLUDE_ALL_BRACKETS = 0b001111000;

		/**
		 * Exclude the text wrapped by round bracket.
		 *
		 * @var int
		 */
		const EXCLUDE_ROUND_BRACKET = 0b000001000;

		/**
		 * Exclude the text wrapped by square bracket.
		 *
		 * @var int
		 */
		const EXCLUDE_SQUARE_BRACKET = 0b000010000;

		/**
		 * Exclude the text wrapped by curly bracket.
		 *
		 * @var int
		 */
		const EXCLUDE_CURLY_BRACKET = 0b000100000;

		/**
		 * Exclude the text wrapped by arrow bracket.
		 *
		 * @var int
		 */
		const EXCLUDE_ARROW_BRACKET = 0b001000000;

		/**
		 * Exclude the text matched by custom options.
		 *
		 * @var int
		 */
		const EXCLUDE_CUSTOM = 0b010000000;

		/**
		 * Exclude the escaped character.
		 *
		 * @var int
		 */
		const EXCLUDE_ESCAPED_CHARACTER = 0b100000000;

		/**
		 * Exclude the text matched by all exclude options.
		 *
		 * @var int
		 */
		const EXCLUDE_ALL = 0b111111111;

		/**
		 * No exclusion.
		 *
		 * @var int
		 */
		const EXCLUDE_NONE = 0b00000000;

		/**
		 * Split the text by all options.
		 *
		 * @var int
		 */
		const SPLIT_ALL = 0b111;

		/**
		 * Contain the delimiter in splitted list.
		 *
		 * @var int
		 */
		const SPLIT_DELIMITER = 0b001;

		/**
		 * Skip the empty splitted content.
		 *
		 * @var int
		 */
		const SPLIT_SKIP_EMPTY = 0b010;

		/**
		 * Ignore the matched delimiter at the start of string and the end of string.
		 *
		 * @var int
		 */
		const SPLIT_BODY_ONLY = 0b100;

		/**
		 * Only match the start of string.
		 *
		 * @var int
		 */
		const REGEX_BEGIN = 0b01;

		/**
		 * Only match the end of string.
		 *
		 * @var int
		 */
		const REGEX_END = 0b10;

		/**
		 * Match the text exactly.
		 *
		 * @var int
		 */
		const REGEX_EXACT = 0b11;

		/**
		 * Round bracket.
		 *
		 * @var int
		 */
		const ROUND_BRACKET = 0;

		/**
		 * Square bracket.
		 *
		 * @var int
		 */
		const SQUARE_BRACKET = 1;

		/**
		 * Curly bracket.
		 *
		 * @var int
		 */
		const CURLY_BRACKET = 2;

		/**
		 * Arrow bracket.
		 *
		 * @var int
		 */
		const ARROW_BRACKET = 3;

		/**
		 * An array contains the cached {@see RegexHelper} object.
		 *
		 * @var array
		 */
		private static $cached = [];

		/**
		 * The captured group index.
		 *
		 * @var int
		 */
		private $index         = 0;

		/**
		 * An array contains the flag of modifier.
		 *
		 * @var array
		 */
		private $modifiers     = [];

		/**
		 * The regular expression pattern.
		 *
		 * @var string
		 */
		private $regex         = '';

		/**
		 * The pattern will be ignored by (*SKIP)(*FAIL).
		 *
		 * @var string
		 */
		private $excludeRegex  = '';

		/**
		 * The option set to match the text by start of or end of string.
		 *
		 * @var int
		 */
		private $regexBeginEnd = 0b00;

		/**
		 * RegexHelper constructor.
		 *
		 * @param string $regex     The full regular expression pattern
		 * @param string $cacheName The cache name, it will save to cache list if it is given
		 */
		public function __construct(string $regex = '', string $cacheName = '')
		{
			if ($regex && preg_match('/^(?<delimter>[\/-@;%`])(?<begin>\\^)?(?<regex>.+?)(?<end>(?<!\\\\)(?:\\\\\\\\)*\\$)?\\1(?<modifier>[mixXsUuAJD]*)$/', $regex, $matches)) {
				$this->regex = $matches['regex'];
				if (isset($matches[3])) {
					for ($i = 0; $i < strlen($matches['modifier']); ++$i) {
						$this->modifiers[$matches['modifier'][$i]] = true;
					}
				}
				if (isset($matches['begin']) && $matches['begin']) {
					$this->regexBeginEnd += self::REGEX_BEGIN;
				}
				if (isset($matches['end']) && $matches['end']) {
					$this->regexBeginEnd += self::REGEX_END;
				}
			} else {
				$this->regex = $regex;
			}

			// If cache name is given, save to cache list
			$cacheName = trim($cacheName);
			if ($cacheName) {
				self::$cached[$cacheName] = $this;
			}
		}

		/**
		 * Call the method in one line, the first argument is the string of regex.
		 *
		 * @param string $name      The name of method
		 * @param array  $arguments An array contains the arguments
		 *
		 * @return mixed The object return from called method
		 */
		public static function __callStatic(string $name, array $arguments)
		{
			if (preg_match('/Quick(Test|Match|Extract|Replace|Split|Combination|Divide)/', $name, $matches)) {
				$regex = array_shift($arguments);
				if (!is_string($regex)) {
					throw new ErrorHandler('The parameter `regex` only allowed in string.');
				}
				$regex = new self($regex);

				return call_user_func_array([$regex, lcfirst($matches[1])], $arguments);
			}

			throw new \BadMethodCallException('Static method ' . $name . ' doesn\'t exist');
		}

		/**
		 * Extract the nest paracentesis structure into an array.
		 *
		 * @param string $text             The subject to be extracted
		 * @param int    $flag             The flag of exclude options
		 * @param array  $customConditions An array contains a list custom options
		 */
		public static function ParensParser(string $text, int $flag = self::EXCLUDE_NONE, array $customConditions = [])
		{
			$nestedContent = [];
			if (!$text) {
				return $nestedContent;
			}

			$regex = new self();
			$regex->recursionWrap(self::ROUND_BRACKET)->exclude($flag, $customConditions);

			while ($matches = $regex->match($text, $offset)) {
				if ($offset[0] > 0) {
					$nestedContent[] = substr($text, 0, $offset[0]);
				}
				$text            = substr($text, $offset[0] + strlen($matches[0]));
				$nestedContent[] = self::ParensParser(substr($matches[0], 1, -1), $flag, $customConditions);
			}

			if ($text) {
				$nestedContent[] = $text;
			}

			return $nestedContent;
		}

		/**
		 * Split the string into an array.
		 *
		 * @param string        $subject  The subject to be splitted
		 * @param int           $flag     The split option flag
		 * @param int           $max      The maximum split count, set 0 for no limited
		 * @param null|callable $callback When the delimiter is found, it will pass the matched content to the callback and return the custom result
		 *
		 * @return array An array contains the splitted string
		 */
		public function split(string $subject, int $flag = 0b00, int $max = 0, callable $callback = null)
		{
			$count    = 0;
			$splitted = [];
			$reserved = '';
			while ($matches = $this->match($subject, $offset)) {
				if ($offset[0] > 0) {
					$divided = substr($subject, 0, $offset[0]);
				} else {
					$divided = '';
				}

				$remaining = substr($subject, $offset[0] + strlen($matches[0]));

				if (!count($splitted) && 0 === strlen($divided) && 0 === strlen($reserved) && self::SPLIT_BODY_ONLY === ($flag & self::SPLIT_BODY_ONLY)) {
					$reserved = $matches[0];

					continue;
				}

				if ($reserved . $divided && self::SPLIT_SKIP_EMPTY !== ($flag & self::SPLIT_SKIP_EMPTY)) {
					$splitted[] = $reserved . $divided;
				}
				$subject    = $remaining;
				$reserved   = '';

				if (self::SPLIT_DELIMITER === ($flag & self::SPLIT_DELIMITER)) {
					$splitted[] = (is_callable($callback)) ? $callback($matches) : $matches[0];
				}

				if ($max > 0 && count($splitted) >= $max) {
					break;
				}
			}

			if (strlen($subject) || strlen($reserved)) {
				$splitted[] = $reserved . $subject;
			}

			return $splitted;
		}

		/**
		 * Determine the string is match with the regex repeatedly.
		 *
		 * @param string $subject The subject to check it is match with the regex
		 *
		 * @return bool Return true if it is matched
		 */
		public function combination(string $subject)
		{
			return preg_match($this->getRegex(function ($regex) {
				return '^(?:' . $regex . ')+$';
			}), $subject);
		}

		/**
		 * Test the subject is match with the regex.
		 *
		 * @param string $subject The subject to check it is match with the regex
		 *
		 * @return bool Return true if it is matched
		 */
		public function test(string $subject)
		{
			return preg_match($this->getRegex(), $subject);
		}

		/**
		 * Replace the matched string with given replacement.
		 *
		 * @param mixed  $replacement if the replacement is a closure, pass the matched
		 *                            result to the closure and replaced by its returned result
		 * @param string $subject     The subject to be replaced
		 *
		 * @return string The replaced string
		 */
		public function replace($replacement, string $subject)
		{
			if (is_callable($replacement)) {
				return preg_replace_callback($this->getRegex(), $replacement, $subject);
			}

			if (is_string($replacement)) {
				return preg_replace($this->getRegex(), $replacement, $subject);
			}

			return $subject;
		}

		/**
		 * Extract the matched string.
		 *
		 * @param string        $subject  The subject to be extracted
		 * @param null|callable $callback A closure will be executed, it will pass the matched result
		 *                                to the closure and add element into result from its returned value
		 *
		 * @return array An array contains the matched string
		 */
		public function extract(string $subject, callable $callback = null)
		{
			// If the `m` multi line modifier is not set and it has the begin '^' or the end '$' token, use preg_match instead
			if ((self::REGEX_BEGIN === ($this->regexBeginEnd & self::REGEX_BEGIN) || self::REGEX_END === ($this->regexBeginEnd & self::REGEX_END)) && !isset($this->modifiers['m'])) {
				$matches = $this->match($subject);
				if (is_callable($callback) && $matches) {
					return $callback($matches);
				}

				return $matches;
			}

			$result = [];
			if (is_callable($callback)) {
				preg_replace_callback($this->getRegex(), function ($matches) use (&$result, $callback) {
					$result[] = $callback($matches);
				}, $subject);
			} else {
				preg_match_all($this->getRegex(), $subject, $result, PREG_SET_ORDER);
			}

			return $result;
		}

		/**
		 * Return the matche string.
		 *
		 * @param string        $subject  The subject to be matched
		 * @param null|array    &$offset  An array will store the matched string offset
		 * @param null|callable $callback a callback will be executed after pattern matched, and return a new result to replace the matched element
		 *
		 * @return string The matched string
		 */
		public function match(string $subject, array &$offset = null, callable $callback = null)
		{
			if (is_string($subject) && $subject) {
				$result = [];
				$offset = [];
				if (preg_match($this->getRegex(), $subject, $matches, PREG_OFFSET_CAPTURE)) {
					foreach ($matches as $captureGroup => $match) {
						$result[$captureGroup] = $match[0];
						$offset[$captureGroup] = $match[1];
					}

					if ($callback) {
						return $callback($result, $offset);
					}

					return $result;
				}
			}

			return null;
		}

		/**
		 * Divide the subject into two string by the regex.
		 *
		 * @param string     $subject  The subject to be divided
		 * @param null|array &$matches An array will store the matched result
		 *
		 * @return array An array contains the divided string
		 */
		public function divide(string $subject, array &$matches = null)
		{
			if ($matches = $this->match($subject, $offset)) {
				return [
					substr($subject, 0, $offset[0]),
					substr($subject, $offset[0] + strlen($matches[0])),
				];
			}

			return [$subject];
		}

		/**
		 * Setup the exculde option, such as ignore the text wrapped by quotes or bracket.
		 *
		 * @param int   $flag             The exclude options
		 * @param array $customConditions An array contains a list custom options
		 *
		 * @return RegexHelper Chainable
		 */
		public function exclude(int $flag, array $customConditions = [])
		{
			$this->excludeRegex = '';
			$excludeInQuotes    = '';
			$excludeInBrackets  = '';
			$excludeInCustoms   = '';

			// Escaped Character
			if (self::EXCLUDE_ESCAPED_CHARACTER === ($flag & self::EXCLUDE_ESCAPED_CHARACTER)) {
				$this->excludeRegex .= '(?:\\\\\\\\)*\\\\.(*SKIP)(*FAIL)|';
			}

			// Quotes
			if (self::EXCLUDE_SINGLE_QUOTE === ($flag & self::EXCLUDE_SINGLE_QUOTE)) {
				$excludeInQuotes .= '\'';
			}

			if (self::EXCLUDE_DOUBLE_QUOTE === ($flag & self::EXCLUDE_DOUBLE_QUOTE)) {
				$excludeInQuotes .= '"';
			}

			if (self::EXCLUDE_GRACE_ACCENT === ($flag & self::EXCLUDE_GRACE_ACCENT)) {
				$excludeInQuotes .= '`';
			}

			if ($excludeInQuotes) {
				$excludeInQuotes = '(?<!\\\\)(?:\\\\\\\\)*(?<quote>[' . $excludeInQuotes . '])(?:(?!\k<quote>)[^\\\\]|\\\\.)*\k<quote>(*SKIP)(*FAIL)|';
				$this->excludeRegex .= $excludeInQuotes;
			}

			// Opening and Closing Bracket
			if (self::EXCLUDE_ROUND_BRACKET === ($flag & self::EXCLUDE_ROUND_BRACKET)) {
				$excludeInBrackets .= $this->getRecursionWrap('(', ')') . '(*SKIP)(*FAIL)|';
			}

			if (self::EXCLUDE_SQUARE_BRACKET === ($flag & self::EXCLUDE_SQUARE_BRACKET)) {
				$excludeInBrackets .= $this->getRecursionWrap('[', ']') . '(*SKIP)(*FAIL)|';
			}

			if (self::EXCLUDE_CURLY_BRACKET === ($flag & self::EXCLUDE_CURLY_BRACKET)) {
				$excludeInBrackets .= $this->getRecursionWrap('{', '}') . '(*SKIP)(*FAIL)|';
			}

			if (self::EXCLUDE_ARROW_BRACKET === ($flag & self::EXCLUDE_ARROW_BRACKET)) {
				$excludeInBrackets .= $this->getRecursionWrap('<', '>') . '(*SKIP)(*FAIL)|';
			}

			if ($excludeInBrackets) {
				$this->excludeRegex .= $excludeInBrackets;
			}

			// Custom match
			if (self::EXCLUDE_CUSTOM === ($flag & self::EXCLUDE_CUSTOM)) {
				foreach ($customConditions as $custom) {
					if (isset($custom['regex'])) {
						$excludeInCustoms .= $custom['regex'] . '(*SKIP)(*FAIL)|';
					} elseif (isset($custom['wrap'])) {
						if (is_string($custom['wrap'])) {
							if (1 === strlen($custom['wrap'])) {
								$wrap = preg_quote($custom['wrap']);
								$excludeInCustoms .= $wrap . '(?:[^\\\\' . $wrap . ']++|\\\\.)*' . $wrap . '(*SKIP)(*FAIL)|';
							} else {
								throw new ErrorHandler('The `wrap` parameter only allows one character.');
							}
						} elseif (is_array($custom['wrap'])) {
							if (isset($custom['wrap']['begin'], $custom['wrap']['end'])) {
								$excludeInCustoms .= $this->getRecursionWrap($custom['wrap']['begin'], $custom['wrap']['end']) . '(*SKIP)(*FAIL)|';
							}
						}
					}
				}
			}

			if ($excludeInCustoms) {
				$this->excludeRegex .= $excludeInCustoms;
			}

			return $this;
		}

		/**
		 * Set the regex options, such as start of string or end of string symbol.
		 *
		 * @param int $options The options
		 *
		 * @return RegexHelper Chainable
		 */
		public function setOptions(int $options)
		{
			$this->regexBeginEnd = $options;

			return $this;
		}

		/**
		 * Replace the regex with the given regex.
		 *
		 * @param string $regex A string of regex
		 *
		 * @return RegexHelper Chainable
		 */
		public function setRegex(string $regex)
		{
			$this->regex = $regex;

			return $regex;
		}

		/**
		 * Get the full regex.
		 *
		 * @param null|callable $callback if the callback is given, the regex will pass to the
		 *                                closure and return its returned result
		 *
		 * @return string The full regex
		 */
		public function getRegex(callable $callback = null)
		{
			$regex = '';
			if ($callback) {
				// If a closure is given, pass the regex to closure
				return '/' . $callback($this->excludeRegex . $this->regex) . '/' . implode('', array_keys($this->modifiers));
			}
			$begin = (self::REGEX_BEGIN === ($this->regexBeginEnd & self::REGEX_BEGIN)) ? '^' : '';
			$end   = (self::REGEX_END === ($this->regexBeginEnd & self::REGEX_END)) ? '$' : '';

			return '/' . $begin . $this->excludeRegex . $this->regex . $end . '/' . implode('', array_keys($this->modifiers));
		}

		/**
		 * Get the cached regex.
		 *
		 * @param string $name The cache name
		 *
		 * @return RegexHelper The cached RegexHelper object
		 */
		public static function GetCache(string $name)
		{
			$name = trim($name);
			if (!$name) {
				throw new ErrorHandler('Cache ' . $name . ' is not found.');
			}

			return self::$cached[$name] ?? null;
		}

		/**
		 * Get the bracket recursion wrapping regex.
		 *
		 * @param int $bracketType The bracket types
		 *
		 * @return string The regex of bracket recursion wrapping
		 */
		public function recursionWrap(int $bracketType = 0)
		{
			if (self::SQUARE_BRACKET === $bracketType) {
				$beginChar = '[';
				$endChar   = ']';
			} elseif (self::CURLY_BRACKET === $bracketType) {
				$beginChar = '{';
				$endChar   = '}';
			} elseif (self::ARROW_BRACKET === $bracketType) {
				$beginChar = '<';
				$endChar   = '>';
			} else {
				$beginChar = '(';
				$endChar   = ')';
			}

			$this->regexBeginEnd = 0b00;
			$this->regex         = $this->getRecursionWrap($beginChar, $endChar);

			return $this;
		}

		/**
		 * Get the bracket recursion wrapping regex by given begin and end character.
		 *
		 * @param string $beginChar The begining character
		 * @param string $endChar   The ending character
		 *
		 * @return string The regex of bracket recursion wrapping
		 */
		private function getRecursionWrap(string $beginChar, string $endChar)
		{
			if ($beginChar && $endChar) {
				$ignoreChar = $beginChar[0];
				$beginChar  = preg_quote($beginChar);
				$ignoreChar .= $endChar[strlen($endChar) - 1];
				$endChar    = preg_quote($endChar);
				$ignoreChar = preg_quote($ignoreChar);

				return '(?<rw' . ++$this->index . '>(?<!\\\\)(?:\\\\\\\\)*' . $beginChar . '(?:(?:[^\\\\' . $ignoreChar . ']*?|\\\\.)|(?&rw' . $this->index . '))*' . $endChar . ')';
			}

			return '';
		}
	}
}
