<?php
namespace RazyFramework
{
  class ThrowError
  {
    public function __construct($errorModule, $errorCode, $message)
    {
      if (defined('CLI_MODE')) {
        die('[' . $errorModule . '] #' . $errorCode . ': ' . $message);
      } else {
        ob_clean();
        $errorPageOutput = file_get_contents(MATERIAL_PATH . 'errorthrow.html');

        echo sprintf($errorPageOutput, $errorModule, $errorCode, $message);
        die();
      }
    }
  }
}
?>
