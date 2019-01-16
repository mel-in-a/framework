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

namespace Origin\Exception;

class UnauthorizedException extends Exception
{
    public function __construct($message = null, $code = 401)
    {
        if ($message === null) {
            $message = 'Unauthorized';
        }
        parent::__construct($message, $code);
    }
}
