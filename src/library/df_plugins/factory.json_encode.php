<?php
return function() {
  function deeprun($data) {
    if (!is_array($data) && $data instanceof \ArrayAccess) {
      $data = $data->getArrayCopy();
    }

    if (is_array($data)) {
      foreach ($data as $key => $value) {
        if (is_array($value) || $value instanceof \ArrayAccess) {
          $data[$key] = deeprun($value);
        }
      }
    }
    return $data;
  }
  return json_encode(deeprun($this));
}
?>
