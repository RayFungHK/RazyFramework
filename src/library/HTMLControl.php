<?php
namespace Core
{
  class HTMLControl
  {
    const TYPE_SELECT = 'select';
    const TYPE_TEXT = 'text';
    const TYPE_PASSWORD = 'password';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_RADIO = 'radio';
    const TYPE_TEXTAREA = 'textarea';

    private $type = '';
    private $value = '';
    private $attributeList = array();
    private $date = null;

    public function __construct($type, $data = null)
    {
      $this->type = trim($type);
      $this->data = $data;
    }

    public function attribute($attr, $value = '')
    {
      if (is_array($attr)) {
        foreach ($attr as $attribute => $value) {
          $this->attribute($attribute, $value);
        }
      } else {
        if (!isset($this->attributeList[$attr])) {
          $this->attributeList[$attr] = '';
        }
        $this->attributeList[$attr] = htmlentities(strval($value));
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
      $this->value = $value;
      return $this;
    }

    public function saveHTML()
    {
      if ($this->type == HTMLControl::TYPE_PASSWORD || $this->type == HTMLControl::TYPE_TEXT || $this->type == HTMLControl::TYPE_CHECKBOX || $this->type == HTMLControl::TYPE_RADIO) {
        $tagName = 'input';
      } else {
        $tagName = $this->type;
      }

      $controlString = '<' . $tagName;
      if (count($this->attributeList)) {
        foreach ($this->attributeList as $attribute => $value) {
          $controlString .= ' ' . $attribute . '="' . $value . '"';
        }
      }

      if ($this->type == HTMLControl::TYPE_SELECT) {
        $controlString .= '>';
        if (is_array($this->data) && count($this->data)) {
          foreach ($this->data as $value => $label) {
            // If the value is an array, create optgroup
            if (is_array($label) && count($label)) {
              $controlString .= '<optgroup value="$key">';
              foreach ($label as $optionKey => $optionValue) {
                $controlString .= '<option value="' . $optionKey . '"' . (($this->value == $optionValue) ? ' selected="selected"' : '') . '>' . $optionValue . '</option>';
              }
              $controlString .= '</optgroup>';
            } else {
              $controlString .= '<option value="' . $value . '"' . (($this->value == $value) ? ' selected="selected"' : '') . '>' . $label . '</option>';
            }
          }
        }
        $controlString .= '</' . $tagName . '>';
      } elseif ($this->type == HTMLControl::TYPE_PASSWORD || $this->type == HTMLControl::TYPE_TEXT || $this->type == HTMLControl::TYPE_CHECKBOX || $this->type == HTMLControl::TYPE_RADIO) {
        if (isset($this->attributeList['value']) && ($this->type == HTMLControl::TYPE_CHECKBOX || $this->type == HTMLControl::TYPE_RADIO)) {
          if ($this->value == $this->attributeList['value']) {
            $controlString .= ' checked="checked"';
          }
        }
        $controlString .= ' />';
      } else {
        $controlString .= '>' . strval($this->data) . '</' . $tagName . '>';
      }
      return $controlString;
    }
  }
}
?>
