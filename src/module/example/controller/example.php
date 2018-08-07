<?php
namespace RazyFramework
{
  class example extends IController
  {
    public function main()
    {
      if (CLI_MODE) {
        echo 'Welcome to CLI mode';
        foreach ($this->manager->getScriptParameters() as $param => $value) {
          echo "\n$param:" . str_repeat(' ', 12 - strlen($param)) . $value;
        }
      } else {
        $sampleClass = new \sampleClass();
        $sampleClassNS = new \Custom\objectClass();

        $tplmanager = $this->loadview('main');

        $md = new Markdown();
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
