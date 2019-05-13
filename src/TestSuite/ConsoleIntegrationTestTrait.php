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

namespace Origin\TestSuite;

use Origin\Console\ConsoleInput;
use Origin\Console\ConsoleOutput;
use Origin\Console\ShellDispatcher;

class BufferedConsoleOutput extends ConsoleOutput
{
    protected $buffer = '';
    public function write(string $data)
    {
        $this->buffer .= $data;
    }
    public function read()
    {
        return $this->buffer;
    }
}

/**
 * A way to test controllers from a higher level
 */

trait ConsoleIntegrationTestTrait
{
    /**
     * Holds the console output
     *
     * @var \Origin\Console\ConsoleOutput
     */
    protected $output = null;

    /**
    * Holds the console output
    *
    * @var \Origin\Console\ConsoleInput
    */
    protected $input = null;

    /**
     * Shell
     *
     * @var \Origin\Console\Shell;
     */
    protected $shell = null;
    
    /**
     * Holds the result from the exec
     *
     * @var bool
     */
    protected $result = null;

    /**
     * Executes a shell console command
     *
     * @param string $command schema generate
     * @param array $input array of input that will be used as response to prompts
     * @return void
     */
    public function exec(string $command, array $input = [])
    {
        $this->shell = $this->result = null;

        $this->output = new BufferedConsoleOutput();
        $this->input = $this->getMockBuilder(ConsoleInput::class)->disableOriginalConstructor()->setMethods(['read'])->getMock();
        
        $x = 0;
        foreach ($input as $data) {
            $this->input->expects($this->at($x))->method('read')->will($this->returnValue($data));
            $x++;
        }
        $args = explode(' ', "console {$command}");
        $dispatcher = new ShellDispatcher($args, $this->output, $this->input);
        $this->result = $dispatcher->start();
       
        $this->shell = $dispatcher->shell();
    }

    /**
     * Asserts that console output contains text
     *
     * @param string $needle The text that you want to assert that is in the output
     * @return void
     */
    public function assertOutputContains(string $needle)
    {
        $this->assertContains($needle, $this->output->read());
    }

    /**
     * Asserts that console output is empty
     *
     * @return void
     */
    public function assertOutputEmpty()
    {
        $this->assertContains('', $this->output->read());
    }

    /**
     * Asserts that the shell was run and was not halted using shell::error()
     *
     * @return void
     */
    public function assertExitSuccess()
    {
        $this->assertTrue($this->result);
    }

    /**
    * Asserts that the shell was run was halted using shell::error()
    *
    * @return void
    */

    public function assertExitError()
    {
        $this->assertFalse($this->result);
    }

    /**
     * Assert an error contains
     *
     * @param string $message
     * @return void
     */
    public function assertErrorContains(string $message)
    {
        if (preg_match("/<alert> ERROR <\/alert>.*$/", $this->output->read(), $matches)) {
            $this->assertContains($message, $matches[0]);
        } else {
            $this->fail();
        }
    }
}