<?php
namespace Module
{
  class example extends \Core\IController
  {
    public function main()
    {
      if (CLI_MODE) {
        echo 'Welcome to CLI mode';
        foreach ($this->manager->getScriptParameters() as $param => $value) {
          echo "\n$param:" . str_repeat(' ', 12 - strlen($param)) . $value;
        }
      } else {
        $tplmanager = $this->loadview('main', true);
        $md = new \Core\Markdown();
        $md->loadFile($this->getViewPath() . 'markdown-sample.txt');

        $tplmanager->getRootBlock()->assign(array(
          'markdown' => $md->parse(),
          'showname' => true
        ));
      }
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
