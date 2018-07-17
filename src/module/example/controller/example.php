<?php
namespace Module
{
  class example extends \Core\IController
  {
    public function main()
    {
      echo 'Main Route';
      $this->manager->trigger('onMessage');
      echo $this->manager->execute('example.method');
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
