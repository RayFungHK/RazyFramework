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
  }
}
?>
