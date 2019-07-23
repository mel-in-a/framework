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
use Origin\Model\Behavior\BehaviorRegistry;
use Origin\Model\Exception\MissingBehaviorException;

class BehaviorRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testThrowException()
    {
        $this->expectException(MissingBehaviorException::class);
        $model = new Model(['name' => 'Post']);
        $registry = new BehaviorRegistry($model);
        $registry->load('foo');
    }
}
