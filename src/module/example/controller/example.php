<?php
namespace Module
{
  class example extends \Core\IController
  {
    public function main()
    {
      $this->loadview('main', true);
    }

    public function reroute()
    {
      echo 'Re-Route';
    }

    public function onMessage()
    {
      echo 'onMessage';
    }

    public function method()
    {
      return 'Method';
    }

    public function cli($argA = null, $argB = null)
    {
      echo str_repeat('=', 24) . "\n";
      echo "Here is CLI Mode\n";
      echo str_repeat('=', 24) . "\n";
    }
  }
}
?>
