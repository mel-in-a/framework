<?php
namespace %namespace%\Test\Model\Concern;

use Origin\TestSuite\OriginTestCase;
use App\Model\Concern\%class%Concern;

/**
 * @property \App\Model\User $User
 */
class %class%ConcernTest extends OriginTestCase
{
    public $fixtures = ['User'];

    public function startup()
    {
        $this->loadModel('User');
    }

    public function testConcernMethod()
    {
        $concern = new %class%Concern($this->User);
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}