<?php
namespace Origin\Test\Command;

use Origin\Model\ConnectionManager;
use Origin\TestSuite\OriginTestCase;
use Origin\TestSuite\ConsoleIntegrationTestTrait;

class DbSchemaDumpCommandTest extends OriginTestCase
{
    use ConsoleIntegrationTestTrait;

    public $fixtures = ['Origin.Post'];

    protected function deleteFile(string $filename)
    {
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
    public function testDumpSQL()
    {
        $filename = APP . DS . 'db' . DS . 'dump.sql';
        $this->deleteFile($filename);

        $this->exec('db:schema:dump --datasource=test dump');
        $this->assertExitSuccess();
        $this->assertOutputContains('Dumping schema to ' . ROOT . '/tests/TestApp/db/dump.sql');
        $this->assertTrue(file_exists($filename));
        
        $this->assertOutputContains('* posts');

        $contents = file_get_contents($filename);
        // Different versions of MySQL also return different results, so test sample
      
        if (ConnectionManager::get('test')->engine() === 'mysql') {
            $this->assertContains('CREATE TABLE `posts` (', $contents);
            $this->assertContains('`title` varchar(255) NOT NULL,', $contents);
        } else { //pgsql
            $this->assertContains('CREATE TABLE posts (', $contents);
            $this->assertContains('title VARCHAR(255) NOT NULL', $contents);
        }
    }

    public function testDumpSqlException()
    {
        $this->exec('db:schema:dump --datasource=test dump', ['n']);
        $this->assertExitError();
        $this->assertErrorContains('Error saving schema file');
    }

    public function testDumpPHP()
    {
        $filename = APP . DS . 'db' . DS . 'dump.php';
        $this->deleteFile($filename);

        $this->exec('db:schema:dump --datasource=test --type=php dump');
        $this->assertExitSuccess();
        $this->assertOutputContains('Dumping schema to ' . ROOT . '/tests/TestApp/db/dump.php');
        $this->assertTrue(file_exists($filename));
        $this->assertOutputContains('* posts');
        include_once $filename;
        
        // Random spot checks. Don't test schema again @see Origin\Test\Model\DatasourceTest
        $this->assertArrayHasKey('posts', $schema);
        $this->assertEquals('primaryKey', $schema['posts']['id']['type']); //
        $this->assertEquals(6, count($schema['posts']));
    }

    public function testDumpPHPException()
    {
        $this->exec('db:schema:dump --datasource=test --type=php dump', ['n']);
        $this->assertExitError();
        $this->assertErrorContains('Error saving schema file');
    }

    public function testDumpUnkownType()
    {
        $this->exec('db:schema:dump --datasource=test --type=ruby');
        $this->assertExitError();
        $this->assertErrorContains('The type `ruby` is invalid');
    }

    public function testExecuteInvalidDatasource()
    {
        $this->exec('db:schema:dump --datasource=foo');
        $this->assertExitError();
        $this->assertErrorContains('foo datasource not found');
    }
}
