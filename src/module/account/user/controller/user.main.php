<?php
namespace Core
{
  return function() {
    $templateManager = new TemplateManager(MATERIAL_PATH . 'testtpl.html');
    $root = $templateManager->getRootBlock();
    $root->newBlock('level-1-a')->assign(array(
      'name' => 'Ray Fung',
      'desc' => array(
        'age' => 27,
        'gender' => 'male',
        'msg' => 'Hello World'
      ),
      'hobby' => ['football', 'videogame', 'boardgame']
    ))->newBlock('level-2');

    $root->newBlock('level-1-a')->assign(array(
      'name' => 'Tom',
      'desc' => array(
        'age' => 21,
        'gender' => 'male'
      )
    ))->newBlock('level-2');

    $root->newBlock('level-1-a')->assign(array(
      'name' => 'Mary',
      'desc' => array(
        'age' => 23,
        'gender' => 'female'
      )
    ));
    $index = 1;
    $templateManager('/level-1-a:last-block/level-2')->assign('text', 'Reassign')->each(function() use (&$index) {
      $this->assign('text', 'Reassigned: ' . $index);
      $index++;
    });
    $templateManager->parse();
  };
}
?>
