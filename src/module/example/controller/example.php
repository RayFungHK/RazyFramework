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
        $text = '*italic*
**bold**asdsada**sadsada**
~~strike~~
__underline__
    code
# 1
## 2
### 3
#### 4
##### 5
###### 6
{$parameters=http://rayfung.hk/}
{$parameters}
{:relative_link}
{:http://rayfung.hk/}[Title Text]
{:[Inline Text]$parameters}[Title Text]
{:[{!http://rayfung.hk/images.png}[Title Text]]$parameters}[Title Text]

# Dillin*ger*

[![N|Solid](https://cldup.com/dTxpPi9lDf.thumb.png)](https://nodesource.com/products/nsolid)

Dillinger is a cloud-enabled, mobile-ready, offline-storage, AngularJS powered HTML5 Markdown editor.';
        $md = new \Core\Markdown($text);
        echo $md->result();
        //$this->loadview('main', true);
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
