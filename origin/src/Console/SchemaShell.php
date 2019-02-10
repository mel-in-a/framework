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

namespace Origin\Console;

use Origin\Model\Schema;
use Origin\Model\ConnectionManager;
use Origin\Model\QueryBuilder;

class SchemaShell extends Shell
{
    public function startup()
    {
        $this->out('<yellow>Schema Shell</yellow>');
        $this->loadTask('Status');
    }
    public function main()
    {
        $this->help();
    }
    public function generate()
    {
        $datasource = 'default';
        if (!empty($this->args)) {
            $datasource = $this->args[0];
        }
        $schema = new Schema();
        $connection = ConnectionManager::get($datasource);
        $tables = $connection->tables();
        $folder = CONFIG . DS . 'schema';
        if (!file_exists($folder)) {
            mkdir($folder);
        }

        foreach ($tables as $table) {
            $data = $schema->generate($table, $datasource);
            if (!$data) {
                $this->Status->error($table);
                continue;
            }
            
            $filename = $folder . DS . $table . '.php';
            $data = '<?php' . "\n" . '$schema = ' .var_export($data, true). ';';
            if (file_put_contents($filename, $data)) {
                $this->Status->ok(sprintf('Generated schema for %s', $table));
            } else {
                $this->Status->error(sprintf('Could not save to %s', $filename));
            }
        }
    }

    public function create()
    {
        $datasource = 'default';
        if (!empty($this->args)) {
            $datasource = $this->args[0];
        }

        $schema = new Schema();
        $connection = ConnectionManager::get($datasource);
        $folder = CONFIG . DS . 'schema';
        $files = scandir($folder);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $table = pathinfo($file, PATHINFO_FILENAME);
                $sql = $schema->createTable($table, $this->loadSchema($folder . DS . $file));
                if ($sql and $connection->execute($sql)) {
                    $this->Status->ok(sprintf('%s table created', $table));
                    continue;
                }
                $this->Status->error(sprintf('Could not create %s', $table));
            }
        }
    }

    public function dump()
    {
        $this->out('Schema Dump');
    
        $datasource = 'default';
        if (!empty($this->args)) {
            $datasource = $this->args[0];
        }
        $connection = ConnectionManager::get($datasource);

        
        $dump = ["SET FOREIGN_KEY_CHECKS=0;"];
        $records = [];
        foreach ($connection->tables() as $table) {
            # Create Table
            $connection->execute("SHOW CREATE TABLE {$table}");
            $result = $connection->fetch();
            if (empty($result['Create Table'])) {
                $this->Status->error($table);
                continue;
            }
            $dump[] = $result['Create Table'] .';';

            # Dump Records
            $builder = new QueryBuilder($table);
            $connection->execute("SELECT * FROM {$table}");
            $results = $connection->fetchAll();
            foreach ($results as $record) {
                $sql = $builder->insert($record)
                                ->write();

                $values = $builder->getValues();
                foreach ($values as $key => $value) {
                    if ($value === null) {
                        $replaceWith  = 'NULL';
                    } elseif (is_integer($value) or is_double($value) or is_float($value) or is_numeric($value)) {
                        $replaceWith  = $value;
                    } else {
                        $value = addslashes($value);
                        $replaceWith  = "'{$value}'";
                    }
                    $sql = preg_replace("/\B:{$key}/", $replaceWith, $sql);
                }
              
                $records[] = $sql .';';
            }
            $this->Status->ok(sprintf('Processed %s table with %d records ', $table, count($results)));
        }
        
        $result =  file_put_contents(CONFIG . DS . 'dump.sql', implode("\n\n", $dump) . "\n\n" . implode("\n", $records));
        if ($result) {
            $this->Status->ok('Saved to config/dump.sql');
        } else {
            $this->Status->error('Could not save to config/dump.sql');
        }
    }

    /**
     * We dont pdo sta
     *
     * @param array $data
     * @return void
     */
    protected function insertStatement(array $data)
    {
    }
    protected function loadSchema(string $filename)
    {
        include $filename;
        return $schema;
    }
    public function help()
    {
        $this->out('Usage: schema [command]');
        $this->out('Usage: schema [command] [datasource]');
        $this->out('');
        $this->out('Commands:');
        $this->out("generate\tgenerates the config\schema\\table.php files");
        $this->out("create\t\tcreates the tables using the schema files");
        $this->out("dump\t\tdumps the tables and records into an sql file");
    }
}