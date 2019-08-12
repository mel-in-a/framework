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

use Origin\Model\Model;

class DbRollbackCommand extends Command
{
    protected $name = 'db:rollback';
    protected $description = 'Rollsback the last migration';

    /**
     * Undocumented variable
     *
     * @var \Origin\Migration\Migration
     */
    protected $Migration = null;

    public function initialize()
    {
        $this->addOption('datasource', [
            'description' => 'Use a different datasource','short' => 'ds','default' => 'default',
        ]);
    }
 
    public function execute()
    {
        $this->Migration = new Model([
            'name' => 'Migration',
            'datasource' => $this->options('datasource'),
        ]);

        $lastMigration = $this->lastMigration();
        if ($lastMigration) {
            $this->runCommand('db:migrate', [$lastMigration - 1,'--datasource' => $this->options('datasource')]);
        } else {
            $this->io->warning('No migrations found');
        }
    }

    /**
     * Gets the last migration version
     *
     * @return string|null
     */
    private function lastMigration() : ?string
    {
        $lastMigration = $this->Migration->find('first', ['order' => 'version DESC']);
        if ($lastMigration) {
            return $lastMigration->version;
        }

        return null;
    }
}
