<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework\DOM
{
	/**
	 * The HTML Control base, provides common function.
	 */
	abstract class Base
	{
		/**
		 * The Control value.
		 *
		 * @var mixed
		 */
		protected $value;

		/**
		 * The tag name of the Control.
		 *
		 * @var string
		 */
		protected $tag = '';

		/**
		 * The attribute "name" value.
		 *
		 * @var string
		 */
		protected $name = '';

		/**
		 * An array contains the class name.
		 *
		 * @var array
		 */
		protected $className = [];

		/**
		 * An array contains the attribute.
		 *
		 * @var array
		 */
		protected $attribute = [];

		/**
		 * The attribute "id" value.
		 *
		 * @var string
		 */
		protected $id = '';

		/**
		 * An array conatins the dataset value.
		 *
		 * @var array
		 */
		protected $dataset = [];

		/**
		 * The text wrapped by the Control.
		 *
		 * @var string
		 */
		protected $text = '';

		/**
		 * Specify the Control is void element.
		 *
		 * @var bool
		 */
		protected $isVoid = false;

		/**
		 * Control constructor.
		 *
		 * @param string $name the name "id" value
		 * @param string $id   the attribute "id" value
		 */
		public function __construct(string $name = '', string $id = '')
		{
			$name       = trim($name);
			$this->name = $name;

			$id       = trim($id);
			$this->id = $id;
		}

		/**
		 * Set the attribute `name` value.
		 *
		 * @param mixed $value The value of the name
		 *
		 * @return self Chainable
		 */
		public function setName($value)
		{
			$value      = trim($value);
			$this->name = $value;

			return $this;
		}

		/**
		 * Get the value.
		 *
		 * @return mixed The value of the control
		 */
		public function getValue()
		{
			return $this->value;
		}

		/**
		 * Set the value.
		 *
		 * @param mixed $value The value of the control
		 *
		 * @return self Chainable
		 */
		public function setValue($value)
		{
			$this->value = $value;

			return $this;
		}

		/**
		 * Get the text.
		 *
		 * @return mixed The text of the control
		 */
		public function getText()
		{
			return $this->text;
		}

		/**
		 * Set the text.
		 *
		 * @param mixed $text
		 *
		 * @return self Chainable
		 */
		public function setText($text)
		{
			$this->text = $text;

			return $this;
		}

		/**
		 * Set the Control is void element, default.
		 *
		 * @param bool $enable Set true to set the Control as void element
		 *
		 * @return self Chainable
		 */
		public function setVoidElement(bool $enable)
		{
			$this->isVoid = $enable;

			return $this;
		}

		/**
		 * Add a class name.
		 *
		 * @param array|string $className A string of the class name or an array contains the class name
		 *
		 * @return self Chainable
		 */
		public function addClass($className)
		{
			if (is_string($className)) {
				$className = trim($className);
				if ($className) {
					$this->className[$className] = true;
				}
			} elseif (is_array($className)) {
				foreach ($className as $name) {
					$this->addClass($name);
				}
			}

			return $this;
		}

		/**
		 * Remove a class name.
		 *
		 * @param array|string $className A string of the class name or an array contains the class name
		 *
		 * @return self Chainable
		 */
		public function removeClass($className)
		{
			if (is_string($className)) {
				$className = trim($className);
				if ($className) {
					unset($this->className[$className]);
				}
			} elseif (is_array($className)) {
				foreach ($className as $name) {
					$this->removeClass($name);
				}
			}

			return $this;
		}

		/**
		 * Set the dataset value.
		 *
		 * @param array|string $parameter The parameter name or an array contains the dataset value
		 * @param mixed        $value     The value of the dataset
		 *
		 * @return self Chainable
		 */
		public function setDataset($parameter, $value = null)
		{
			if (is_string($parameter)) {
				$parameter = trim($parameter);
				if ($parameter) {
					$this->dataset[$parameter] = $value;
				}
			} elseif (is_array($parameter)) {
				foreach ($parameter as $param => $value) {
					$this->setDataset($param, $value);
				}
			}

			return $this;
		}

		/**
		 * Set the attribute.
		 *
		 * @param array|string $attribute The attribute name or an array contains the attribute value
		 * @param mixed        $value     The value of the attribute
		 *
		 * @return self Chainable
		 */
		public function setAttribute($attribute, $value = null)
		{
			if (is_string($attribute)) {
				$attribute = trim($attribute);
				if ($attribute) {
					$this->attribute[$attribute] = $value;
				}
			} elseif (is_array($attribute)) {
				foreach ($attribute as $attr => $value) {
					$this->setDataset($attr, $value);
				}
			}

			return $this;
		}

		/**
		 * Remove an attribute.
		 *
		 * @param string $attribute The attribute name
		 *
		 * @return self Chainable
		 */
		public function removeAttribute(string $attribute)
		{
			unset($this->attribute[$attribute]);

			return $this;
		}

		/**
		 * Generate the HTML code of the Control.
		 *
		 * @return string The HTML code
		 */
		public function saveHTML()
		{
			$control = '<' . $this->tag;
			if ($this->name) {
				$control .= ' name="' . $this->name . '"';
			}

			if ($this->id) {
				$control .= ' id="' . $this->id . '"';
			}

			if (count($this->attribute)) {
				foreach ($this->attribute as $attr => $value) {
					$control .= ' ' . $attr;
					if (null !== $value) {
						$control .= '="' . $this->getHTMLValue($value) . '"';
					}
				}
			}

			if (count($this->dataset)) {
				foreach ($this->dataset as $name => $value) {
					$control .= ' data-' . $name . '="' . $this->getHTMLValue($value) . '"';
				}
			}

			if (count($this->className)) {
				$control .= ' class="' . implode(' ', $this->className) . '"';
			}

			if (null !== $this->value) {
				$control .= ' value="' . $this->getHTMLValue($this->value) . '"';
			}

			if (!$this->isVoid) {
				$control .= '>' . $this->text . '</' . $this->tag . '>';
			} else {
				$control .= ' />';
			}

			return $control;
		}

		/**
		 * The value used to convert as HTML value.
		 *
		 * @param mixed $value The object to convert as HTML value
		 *
		 * @return string The value of HTML value
		 */
		public function getHTMLValue($value)
		{
			if (is_scalar($value)) {
				return htmlspecialchars((string) $value);
			}

			if (!is_resource($value)) {
				return htmlspecialchars(json_encode($value));
			}

			return '';
		}
	}
}
