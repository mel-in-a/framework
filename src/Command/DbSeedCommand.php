<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright    Copyright (c) Jamiel Sharief
 * @link         https://www.originphp.com
 * @license      https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Origin\Command;

class DbSeedCommand extends Command
{
    use DbSchemaTrait;
    protected $name = 'db:seed';

    protected $description = 'Seeds the database with initial records';

    public function initialize()
    {
        $this->addOption('datasource', [
            'description' => 'Use a different datasource',
            'short' => 'ds',
            'default' => 'default',
        ]);
        $this->addArgument('name', [
            'description' => 'seed or Plugin.seed',
        ]);
    }
 
    /**
     * Seed needs to skip if file does not exist
     */
    public function execute()
    {
        $name = $this->arguments('name');
        if ($name and ! file_exists($this->schemaFilename($name))) {
            $this->throwError(sprintf('Seed file `%s` could not be found', $this->schemaFilename($name)));
        }
        if ($name === null) {
            $name = 'seed';
        }
        $datasource = $this->options('datasource');
        $filename = $this->schemaFilename($name);
        if (file_exists($filename)) {
            $this->loadSchema($filename, $datasource);
        } else {
            $this->io->status('skipped', 'Seed SQL file');
        }
    }
}
