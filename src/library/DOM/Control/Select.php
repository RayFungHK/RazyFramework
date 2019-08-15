<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace RazyFramework\DOM\Control
{
  use RazyFramework\DOM\Base;

  /**
   * The SELECT control.
   */
	class Select extends Base
	{
		/**
		 * An array contains the option text and its value.
		 *
		 * @var array
		 */
		private $options = [];

		/**
		 * The callback function to handle option element.
		 *
		 * @var \Closure
		 */
		private $callback;

		/**
		 * Select constructor.
		 *
		 * @param string $name       the name "id" value
		 * @param string $id         the attribute "id" value
		 * @param bool   $isMultiple Set true to specify a multiple selectbox
		 */
		public function __construct(string $name = '', string $id = '', bool $isMultiple = false)
		{
			$this->tag = 'select';
			if ($isMultiple) {
				$this->setAttribute('multiple');
			}

			parent::__construct($name, $id);
		}

		/**
		 * Add a option.
		 *
		 * @param array|string $value The option value or an array conatins the option value
		 * @param mixed        $label The option label or a group of the option value
		 *
		 * @return self Chainable
		 */
		public function addOption($value, $label = '')
		{
			if (is_array($value)) {
				foreach ($value as $val => $label) {
					$this->addOption($val, $label);
				}
			} else {
				$this->options[$value] = $label;
			}

			return $this;
		}

		/**
		 * Specify a closure to setup the option element by given option value.
		 *
		 * @param callable $callback the callback will be executed when the option is created
		 *
		 * @return self Chainable
		 */
		public function onSetupOption(callable $callback)
		{
			$callback->bindTo($this);
			$this->callback = $callback;

			return $this;
		}

		/**
		 * Generator the selectbox HTML code.
		 *
		 * @return string The HTML code
		 */
		public function saveHTML()
		{
			$this->text = '';
			foreach ($this->options as $value => $label) {
				$this->text .= $this->getOptionHTML($label, $value);
			}

			return parent::saveHTML();
		}

		/**
		 * Generator the option and optgroup HTML code into text.
		 *
		 * @param int|string $label The option label name
		 * @param mixed      $value The value of option or an array contains optgroup option
		 *
		 * @return string The HTML code
		 */
		private function getOptionHTML($label, $value)
		{
			if (is_scalar($label)) {
				$option = (new Option())->setText((string) $label)->setValue($value);
				if ($this->checkValue($value)) {
					$option->setAttribute('selected', 'selected');
				}

				return $option->saveHTML();
			}

			if (is_array($label)) {
				if ($this->callback) {
					return call_user_func($this->callback, $label, $value);
				}
				$html .= '<optgroup label="' . $name . '">';
				foreach ($value as $val => $label) {
					$option = new Option();
					$option->attribute('name', (is_scalar($label) ? $label : ''))->setValue($val);

					if ($this->checkValue($val)) {
						$option->setAttribute('selected', 'selected');
					}
					$html .= $option->saveHTML();
				}
				$html .= '</optgroup>';

				return $html;
			}
			if ($this->callback) {
				return call_user_func($this->callback, $label, $value);
			}

			return '';
		}

		/**
		 * Compare given the value is equal with or include in Control value.
		 *
		 * @param mixed $value The value to compare
		 *
		 * @return bool Return true if matched
		 */
		private function checkValue($value)
		{
			$value = (string) $value;
			if (!is_scalar($value) && !is_array($value)) {
				return false;
			}

			if (is_array($this->value)) {
				foreach ($this->value as $val) {
					if (is_scalar($val)) {
						if ((string) $val === $value) {
							return true;
						}
					}
				}

				return false;
			}

			return (string) $this->value === $value;
		}
	}
}
