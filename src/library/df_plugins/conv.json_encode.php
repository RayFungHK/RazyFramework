<?php
return function() {
  if (!function_exists('deeprun')) {
    function deeprun($data) {
      if (!is_array($data)) {
        $data = $data->getArrayCopy();
      }

      if (is_array($data)) {
        foreach ($data as $key => $value) {
          if (is_array($value)) {
            $data[$key] = deeprun($value);
          }
        }
      }
      return $data;
    }
  }
  
  $this->value = json_encode(deeprun($this->value));
  return $this->value;
}
?>
