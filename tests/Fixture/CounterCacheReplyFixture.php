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

namespace Origin\Test\Fixture;

use Origin\TestSuite\Fixture;

class CounterCacheReplyFixture extends Fixture
{
    public $datasource = 'test';

    public $schema = [
         'id' => ['type' => 'primaryKey'],
         'post_id' => ['type' => 'integer'],
         'description' => 'text',
         'created' => 'datetime',
         'modified' => 'datetime',
    ];

    public $records = [];
}