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
  class HTMLControl
  {
  	const TYPE_SELECT          = 'select';
  	const TYPE_MULTIPLE_SELECT = 'mulitple-select';
  	const TYPE_TEXT            = 'text';
  	const TYPE_PASSWORD        = 'password';
  	const TYPE_CHECKBOX        = 'checkbox';
  	const TYPE_RADIO           = 'radio';
  	const TYPE_TEXTAREA        = 'textarea';

  	private $type          = '';
  	private $value         = '';
  	private $attributeList = [];
  	private $date;

  	public function __construct(string $type, $data = null)
  	{
  		$this->type = trim($type);
  		$this->data = $data;
  	}

  	public function attribute(string $attr, $value = '')
  	{
  		if (is_array($attr)) {
  			foreach ($attr as $attribute => $value) {
  				$this->attribute($attribute, $value);
  			}
  		} else {
  			if (!isset($this->attributeList[$attr])) {
  				$this->attributeList[$attr] = '';
  			}
  			$this->attributeList[$attr] = htmlentities((string) $value);
  		}

  		return $this;
  	}

  	public function setData($value = null)
  	{
  		$this->date = $value;

  		return $this;
  	}

  	public function setValue($value = null)
  	{
      if (is_array($value)) {
        $value = array_flip($value);
      }
  		$this->value = $value;

  		return $this;
  	}

  	public function saveHTML()
  	{
  		if (self::TYPE_PASSWORD === $this->type || self::TYPE_TEXT === $this->type || self::TYPE_CHECKBOX === $this->type || self::TYPE_RADIO === $this->type) {
  			$tagName = 'input';
  		} else {
    		if (self::TYPE_MULTIPLE_SELECT === $this->type) {
    			$tagName = 'select';
    		} else {
        	$tagName = $this->type;
        }
  		}

  		if (self::TYPE_MULTIPLE_SELECT === $this->type) {
  			$this->attributeList['multiple'] = 'multiple';
  		}

  		$controlString = '<' . $tagName;
  		if (count($this->attributeList)) {
  			foreach ($this->attributeList as $attribute => $value) {
  				$controlString .= ' ' . $attribute . '="' . $value . '"';
  			}
  		}

  		if (self::TYPE_SELECT === $this->type || self::TYPE_MULTIPLE_SELECT === $this->type) {
  			$controlString .= '>';
  			if (is_array($this->data) && count($this->data)) {
  				foreach ($this->data as $value => $label) {

  					// If the value is an array, create optgroup
  					if (is_array($label) && count($label)) {
  						$controlString .= '<optgroup label="' . $label . '">';
  						foreach ($label as $optionKey => $optionValue) {
  							$controlString .= '<option value="' . $optionKey . '"' . (($this->checkValue($optionKey, $this->value, self::TYPE_MULTIPLE_SELECT === $this->type)) ? ' selected="selected"' : '') . '>' . $optionValue . '</option>';
  						}
  						$controlString .= '</optgroup>';
  					} else {
  						$controlString .= '<option value="' . $value . '"' . (($this->checkValue($value, $this->value, self::TYPE_MULTIPLE_SELECT === $this->type)) ? ' selected="selected"' : '') . '>' . $label . '</option>';
  					}
  				}
  			}
  			$controlString .= '</' . $tagName . '>';
  		} elseif (self::TYPE_PASSWORD === $this->type || self::TYPE_TEXT === $this->type || self::TYPE_CHECKBOX === $this->type || self::TYPE_RADIO === $this->type) {
  			if (isset($this->attributeList['value']) && (self::TYPE_CHECKBOX === $this->type || self::TYPE_RADIO === $this->type)) {
  				if ($this->value === $this->attributeList['value']) {
  					$controlString .= ' checked="checked"';
  				}
  			}
  			$controlString .= ' />';
  		} else {
  			$controlString .= '>' . (string) ($this->data) . '</' . $tagName . '>';
  		}

  		return $controlString;
  	}

  	private function checkValue($value, $compare, bool $multiple = false)
  	{
  		if ($multiple) {
  			if (!is_array($compare)) {
  				return false;
  			}

  			return array_key_exists($value, $compare);
  		}

  		return (string) $value === (string) $compare;
  	}
  }
}
