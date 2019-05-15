<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Test\Model\Behavior;

use Origin\Model\Model;
use Origin\Model\Entity;
use Origin\Model\Behavior\Behavior;
use Origin\Model\ConnectionManager;
use Origin\TestSuite\OriginTestCase;

class Tester extends Model
{
    public function initialize(array $config)
    {
        $this->loadBehavior('Tester', ['className' => 'Origin\Test\Model\Behavior\BehaviorTesterBehavior']);
    }
}

class BehaviorTesterBehavior extends Behavior
{
    public function beforeFind($query = array())
    {
        $query += ['return' => true];
        if (is_bool($query['return'])) {
            return $query['return'];
        }
        $query['beforeFind'] = true;

        return $query;
    }

    public function foo($a, $b, $c, $d)
    {
        return 'bar';
    }
}

class BehaviorTest extends OriginTestCase
{

    public $fixtures = ['Origin.Article'];

    public function testBeforeFind()
    {
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $query = ['foo'=>'bar'];
        $this->assertEquals($query, $behavior->beforeFind($query));
    }

    public function testAfterFind()
    {
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $results = ['foo'=>'bar'];
        $this->assertEquals($results, $behavior->afterFind($results));
    }

    public function testBeforeValidate()
    {
        $entity = new Entity(['name'=>'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertTrue($behavior->beforeValidate($entity));
    }

    public function testAfterValidate()
    {
        $entity = new Entity(['name'=>'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertNull($behavior->afterValidate($entity, true));
    }

    public function testBeforeSave()
    {
        $entity = new Entity(['name'=>'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertTrue($behavior->beforeSave($entity, ['option1'=>true]));
    }

    public function testAfterSave()
    {
        $entity = new Entity(['name'=>'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertNull($behavior->afterSave($entity, true, ['option1'=>true]));
    }

    public function testBeforeDelete()
    {
        $entity = new Entity(['name'=>'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertTrue($behavior->beforeDelete($entity));
    }


    public function testAfterDelete()
    {
        $entity = new Entity(['name'=>'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertNull($behavior->afterDelete($entity, true));
    }

    public function testModel()
    {
        $entity = new Entity(['name'=>'Foo']);
        $behavior = new Behavior(new Model(['name' => 'Post']));
        $this->assertInstanceOf(Model::class, $behavior->model());
    }



    public function testFindCallbacks()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
          ->setMethods(['beforeFind', 'afterFind'])
          ->setConstructorArgs([$Article])
          ->getMock();

        $behavior->expects($this->once())
       ->method('beforeFind')
         ->willReturn($this->returnArgument(0));

        $behavior->expects($this->once())
        ->method('afterFind')
          ->willReturn($this->returnArgument(0));

        // As we are injecting mock, we need to enable it as well
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $Article->find('first');
    }

    public function testValidateCallbacks()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
          ->setMethods(['beforeValidate', 'afterValidate'])
          ->setConstructorArgs([$Article])
          ->getMock();

        $behavior->expects($this->once())
       ->method('beforeValidate')->willReturn(true);

        $behavior->expects($this->once())
        ->method('afterValidate');

        // As we are injecting mock, we need to enable it as well
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $article = $Article->new(array(
        'user_id' => 3, 'title' => 'testValidateCallbacks',
        'body' => 'testValidateCallbacks',
        'slug' => 'test-validate-callbacks',
        'created' => date('Y-m-d'),
        'modified' => date('Y-m-d'),
      ));

        $this->assertTrue($Article->save($article));
    }

    public function testValidateCallbacksAbort()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
          ->setMethods(['beforeValidate', 'afterValidate'])
          ->setConstructorArgs([$Article])
          ->getMock();

        $behavior->expects($this->once())
       ->method('beforeValidate')
       ->willReturn(false);

        $behavior->expects($this->never())
        ->method('afterValidate');

        // As we are injecting mock, we need to enable it as well
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $article = $Article->new(array(
        'user_id' => 3, 'title' => 'testValidateCallbacksAbort',
        'body' => 'testValidateCallbacksAbort',
        'slug' => 'test-validate-callbacks-abort',
        'created' => date('Y-m-d'),
        'modified' => date('Y-m-d'),
      ));

        $this->assertFalse($Article->save($article));
    }

    public function testSaveCallbackAbort()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
        ->setMethods(['beforeSave', 'afterSave'])
        ->setConstructorArgs([$Article])
        ->getMock();

        $behavior->expects($this->once())
     ->method('beforeSave')
       ->willReturn(false);

        $behavior->expects($this->never())
       ->method('afterSave');

        // As we are injecting mock, we need to enable it as well
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $article = $Article->new(array(
       'user_id' => 3, 'title' => 'testSaveCallbacksAbort',
       'body' => 'testSaveCallbacksAbort',
       'slug' => 'test-save-callbacks-abor',
       'created' => date('Y-m-d'),
       'modified' => date('Y-m-d'),
     ));

        $this->assertFalse($Article->save($article));
    }

    public function testSaveCallbacks()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
          ->setMethods(['beforeSave', 'afterSave'])
          ->setConstructorArgs([$Article])
          ->getMock();

        $behavior->expects($this->once())
       ->method('beforeSave')
         ->willReturn($this->returnArgument(0));

        $behavior->expects($this->once())
        ->method('afterSave')
          ->willReturn($this->returnArgument(0), $this->returnArgument(1));

        // As we are injecting mock, we need to enable it as well
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');

        $article = $Article->new(array(
          'user_id' => 3, 'title' => 'SaveCallbacks',
          'body' => 'SaveCallbacks',
          'slug' => 'test-save-callbacks',
          'created' => date('Y-m-d'),
          'modified' => date('Y-m-d'),
        ));

        $this->assertTrue($Article->save($article));
    }

    public function testDeleteCallbacksAbort()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
          ->setMethods(['beforeDelete', 'afterDelete'])
          ->setConstructorArgs([$Article])
          ->getMock();

        $behavior->expects($this->once())
       ->method('beforeDelete')
         ->willReturn(false);

        $behavior->expects($this->never())
        ->method('afterDelete');

        // As we are injecting mock, we need to enable it as well
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');
        $article = $Article->get(1000);
        $this->assertFalse($Article->delete($article));
    }

    public function testDeleteCallbacks()
    {
        $Article = new Model(array('name' => 'Article', 'datasource' => 'test'));

        $behavior = $this->getMockBuilder('Origin\Test\Model\Behavior\BehaviorTesterBehavior')
          ->setMethods(['beforeDelete', 'afterDelete'])
          ->setConstructorArgs([$Article])
          ->getMock();

        $behavior->expects($this->once())
       ->method('beforeDelete')
         ->willReturn($this->returnArgument(0));

        $behavior->expects($this->once())
        ->method('afterDelete');

        // As we are injecting mock, we need to enable it as well
        $Article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $Article->behaviorRegistry()->enable('BehaviorTester');

        $Article->loadBehavior('BehaviorTester');
        $article = $Article->get(1000);
        $this->assertTrue($Article->delete($article));
    }

    public function testMixin()
    {
        $article = new Model(['name' => 'Post']);
        $behavior = new BehaviorTesterBehavior($article);

        $article->behaviorRegistry()->set('BehaviorTester', $behavior);
        $article->behaviorRegistry()->enable('BehaviorTester');

        $this->assertEquals('bar', $article->foo(1, 2, 3, 4));

        $this->expectException(\Origin\Exception\Exception::class);
        $article->bar();
    }
}
