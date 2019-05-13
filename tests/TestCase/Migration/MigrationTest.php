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

namespace Origin\Test\Migration;

use Origin\Migration\Migration;
use Origin\Migration\Adapter\MysqlAdapter;
use Origin\TestSuite\OriginTestCase;
use Origin\Exception\Exception;
use Origin\Core\Logger;

class CreateProductTableMigration extends Migration
{
    public function up(){
        $this->createTable('products',[
            'name' => 'string',
            'description' => 'text'
        ]);
    }
    public function down(){
        $this->dropTable('products');
    }
    public function reset(){
        $this->statements = [];
    }
}

class DidNotReadTheManualMigration extends Migration
{
    public function change(){
        $this->execute('SELECT * FROM read_the_manual');
    }

    public function reversable(){
        $this->execute('SELECT * FROM read_the_manual');
    }

}

class UsingExecuteMigration extends Migration
{
    public function up(){
        $this->execute('SELECT id,title,created from articles');
    }
    public function down(){
        $this->execute('SELECT id,title,created from articles');
    }
}

class MockMigration extends Migration
{   
    protected $calledBy = null;

    public function setCalledBy(string $name = null){
        $this->calledBy = $name;
    }
    public function calledBy(){
        return $this->calledBy;
    }

    public function reset(){
        $this->statements = [];
    }

    /**
     * Keep code short
     *
     * @return array
     */
    public function invokeStart(){
        $this->start();
        $reversableStatements = $this->reverseStatements();
        $this->statements = [];
        return $reversableStatements;
    }
}

class MigrationTest extends OriginTestCase
{
    public $fixtures = ['Origin.Article','Origin.User'];

    /**
     * Return the migration object
     *
     * @return \Origin\Migration\Migration
     */
    public function migration($calledBy='change')
    {
        $migration = new MockMigration(
            new MysqlAdapter(),
            ['datasource'=>'test']
        );
        $migration->setCalledBy($calledBy);
        return $migration;
    }

    public function testCreateTable(){
        $migration = $this->migration();
        $migration->createTable('products', [
            'name' => 'string',
            'description' => 'text',
            'column_1' => ['type'=>'string','default'=>'foo'],
            'column_2' => ['type'=>'string','default'=>'foo','null'=>true],
            'column_3' => ['type'=>'string','default'=>'foo','null'=>false],
            'column_4' => ['type'=>'string','null'=>false],
            'column_5' => ['type'=>'string','null'=>true],
            'column_6' => ['type'=>'INT','limit'=>5] // test non agnostic
        ], ['options'=>'ENGINE=InnoDB DEFAULT CHARSET=utf8']);
        
        
        $reversableStatements = $migration->invokeStart();
     
        $this->assertTrue($migration->columnExists('products', 'id'));
        $this->assertTrue($migration->indexExists('products', ['name'=>'PRIMARY']));

        $this->assertTrue($migration->columnExists('products', 'name', ['type'=>'string']));
        $this->assertTrue($migration->columnExists('products', 'description', ['type'=>'text']));
        
        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->tableExists('products'));
    }

    public function testCreateJoinTable()
    {
        $migration = $this->migration();
        $migration->createJoinTable('contacts', 'tags');
     
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->tableExists('contacts_tags'));
        $this->assertTrue($migration->columnExists('contacts_tags', 'contact_id'));
        $this->assertTrue($migration->columnExists('contacts_tags', 'tag_id'));

        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->tableExists('contacts_tags'));
    }
    
    public function testDropTable()
    {
        $migration = $this->migration();
        $migration->dropTable('articles');

        $reversableStatements = $migration->invokeStart();
        $this->assertFalse($migration->tableExists('articles'));

        $migration->rollback($reversableStatements);
        $this->assertTrue($migration->tableExists('articles'));
    }

    public function testRenameTable()
    {
        $migration = $this->migration();
        $migration->renameTable('articles', 'ez_articles');
        
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->tableExists('ez_articles'));

        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->tableExists('ez_articles'));
    }

    public function testAddColumn()
    {
        $migration = $this->migration();

        $migration->createTable('articles2');
        $reversableStatements = $migration->invokeStart(); // Can only add columns on existing tables

        $migration = $this->migration();
        $migration->addColumn('articles', 'category_id', 'integer');
        $migration->addColumn('articles', 'opens', 'integer', ['limit'=>3]);
        $migration->addColumn('articles', 'amount', 'decimal', ['precision'=>5,'scale'=>2]);
        $migration->addColumn('articles', 'balance', 'decimal'); // use defaults
        $migration->addColumn('articles', 'comment_1', 'string', ['default'=>'no comment']);
        $migration->addColumn('articles', 'comment_2', 'string', ['default'=>'foo','null'=>true]);
        $migration->addColumn('articles', 'comment_3', 'string', ['default'=>'123','null'=>false]);
        /**
         * If the table is created,when rolling back cant test fields, but fixture inserts data will cause
         * error cannot be null so this particular column is put in new table.
         */
        $migration->addColumn('articles2', 'comment_4', 'string', ['null'=>false]);
        $migration->addColumn('articles', 'comment_5', 'string', ['null'=>true]);

        $reversableStatements = array_merge($migration->invokeStart(),$reversableStatements); # add to end 

        $this->assertTrue($migration->columnExists('articles', 'category_id'));
        $this->assertTrue($migration->columnExists('articles', 'opens', ['limit'=>3]));
        $this->assertTrue($migration->columnExists('articles', 'amount', ['precision'=>5,'scale'=>2]));
        $this->assertTrue($migration->columnExists('articles', 'balance', ['precision'=>10,'scale'=>0]));
        $this->assertTrue($migration->columnExists('articles', 'comment_1', ['default'=>'no comment']));
        $this->assertTrue($migration->columnExists('articles', 'comment_2', ['default'=>'foo','null'=>true]));
        $this->assertTrue($migration->columnExists('articles', 'comment_3', ['default'=>'123','null'=>false]));
        $this->assertTrue($migration->columnExists('articles2', 'comment_4', ['null'=>false]));
        $this->assertTrue($migration->columnExists('articles', 'comment_5', ['null'=>true]));
    
        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->columnExists('articles', 'category_id'));
    }

    public function testColumns()
    {
        $migration = $this->migration();
        $expected = ['id','author_id','title','body','created','modified'];
        $this->assertSame($expected, $migration->columns('articles'));
    }

    public function testChangeColumn()
    {
        $migration = $this->migration();
 
        $migration->changeColumn('articles', 'title', 'string', ['limit'=>10]);
        $migration->changeColumn('articles', 'body', 'string');
    
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->columnExists('articles', 'title', ['limit'=>10]));
        $this->assertTrue($migration->columnExists('articles', 'body', ['type'=>'string']));
        
        $migration->rollback($reversableStatements);
        $this->assertTrue($migration->columnExists('articles', 'title', ['limit'=>255]));
        $this->assertTrue($migration->columnExists('articles', 'body', ['type'=>'text']));
    }

    public function testRemoveColumn()
    {
        $migration = $this->migration();
        $migration->removeColumn('articles', 'body');
        
        $reversableStatements = $migration->invokeStart();
        $this->assertFalse($migration->columnExists('articles', 'body'));

        $migration->rollback($reversableStatements);
        $this->assertTrue($migration->columnExists('articles', 'body'));
    }


    public function testRemoveColumns()
    {
        $migration = $this->migration();
        $migration->removeColumns('articles', ['title','created']);
        
        $reversableStatements = $migration->invokeStart();
        $this->assertFalse($migration->columnExists('articles', 'title'));
        $this->assertFalse($migration->columnExists('articles', 'created'));

        $migration->rollback($reversableStatements);
        $this->assertTrue($migration->columnExists('articles', 'title'));
        $this->assertTrue($migration->columnExists('articles', 'created'));
    }


    public function testRenameColumn()
    {
        $migration = $this->migration();
        $migration->renameColumn('articles', 'title', 'article_title');
        
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->columnExists('articles', 'article_title'));

        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->columnExists('articles', 'article_title'));
    }



    public function testAddIndex()
    {
        $migration = $this->migration();
        $migration->addIndex('articles', 'author_id');
        $migration->addIndex('articles', ['id','title']);
        $migration->addIndex('articles', 'created', ['unique'=>true]);
       
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->indexExists('articles', 'author_id'));
        $this->assertTrue($migration->indexExists('articles', ['id','title']));
        $this->assertTrue($migration->indexExists('articles', 'created'));
    
         $migration->rollback($reversableStatements);
        $this->assertFalse($migration->indexExists('articles', 'author_id'));
        $this->assertFalse($migration->indexExists('articles', ['id','title']));
        $this->assertFalse($migration->indexExists('articles', 'created'));
    }

    public function testRenameIndex()
    {
        $migration = $this->migration();
        $migration->addIndex('articles', 'author_id');
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->indexExists('articles', 'author_id'));
        
        $migration = $this->migration();
        $migration->renameIndex('articles', 'articles_author_id_index', 'aaii_index');

        $reversableStatements = array_merge($migration->invokeStart(),$reversableStatements);
       
        $this->assertTrue($migration->indexExists('articles', ['name'=>'aaii_index']));
        
         $migration->rollback($reversableStatements);
        $this->assertFalse($migration->indexExists('articles', ['name'=>'aaii_index']));
    }

    public function testRemoveIndex()
    {
        $migration = $this->migration();
        $migration->addIndex('articles', 'title');
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->indexExists('articles', 'title'));
      
        $migration = $this->migration();
        $migration->removeIndex('articles', 'title');
        $reversableStatements = $migration->invokeStart();
        $this->assertFalse($migration->indexExists('articles', 'title'));
      
         $migration->rollback($reversableStatements);
        $this->assertTrue($migration->indexExists('articles', 'title'));
    }
   
    public function testAddForeignKey()
    {
        // Prepare
        $migration = $this->migration();
        $migration->addColumn('articles', 'user_id', 'integer', ['default'=>1000]);
        $reversableStatements = $migration->invokeStart();

        $migration = $this->migration();
        $migration->addForeignKey('articles', 'users');
        $reversableStatements = array_merge($migration->invokeStart(),$reversableStatements);
        $this->assertTrue($migration->foreignKeyExists('articles', ['column'=>'user_id']));

        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->foreignKeyExists('articles', ['column'=>'user_id']));
       
    }

    public function testAddForeignKeyCustom()
    {
       
        # Prepare
        $migration = $this->migration();
        $migration->renameColumn('users', 'id', 'lng_id');
        $reversableStatements = $migration->invokeStart();
        $this->assertTrue($migration->columnExists('users', 'lng_id'));

        $migration = $this->migration();
        $migration->addForeignKey('articles', 'users', [
            'column'=>'author_id','primaryKey'=>'lng_id'
            ]);
       
      $reversableStatements = array_merge($migration->invokeStart(),$reversableStatements);
        $this->assertTrue($migration->columnExists('users', 'lng_id'));
        $this->assertTrue($migration->foreignKeyExists('articles', ['column'=>'author_id']));

        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->foreignKeyExists('articles', ['column'=>'author_id']));
    }

    public function testAddForeignKeyByName()
    {

        $migration = $this->migration();
        $migration->addColumn('articles', 'user_id', 'integer', ['default'=>1000]);
        $reversableStatements = $migration->invokeStart(); 

        $migration = $this->migration();
        $migration->addForeignKey('articles', 'users', ['name'=>'myfk_001']);
        $reversableStatements = array_merge($migration->invokeStart(),$reversableStatements);
        $this->assertTrue($migration->foreignKeyExists('articles', ['name'=>'myfk_001']));

        $migration->rollback($reversableStatements);
        $this->assertFalse($migration->foreignKeyExists('articles', ['name'=>'myfk_001']));
    }

    public function testRemoveForeignKey()
    {

        $migration = $this->migration();
        $migration->addColumn('articles', 'user_id', 'integer', ['default'=>1000]);
        $migration->start(); 

        $migration = $this->migration();
        $migration->addForeignKey('articles', 'users');
        $migration->addForeignKey('articles', 'users', ['column'=>'author_id','name'=>'fk_a1']);
        $migration->start(); 
        $this->assertTrue($migration->foreignKeyExists('articles', 'user_id'));
        $this->assertTrue($migration->foreignKeyExists('articles', ['name'=>'fk_a1']));

        # Test
        $migration = $this->migration();
        $migration->removeForeignKey('articles', 'users');
        $migration->removeForeignKey('articles', ['name'=>'fk_a1']);
        $reversableStatements = $migration->invokeStart();
        $this->assertFalse($migration->foreignKeyExists('articles', 'user_id'));
        $this->assertFalse($migration->foreignKeyExists('articles', ['name'=>'fk_a1']));
      
        $migration->rollback($reversableStatements);
        $this->assertTrue($migration->foreignKeyExists('articles', 'user_id'));
        $this->assertTrue($migration->foreignKeyExists('articles', ['name'=>'fk_a1']));
    }

    public function testUpDown(){
        $migration = new CreateProductTableMigration(new MysqlAdapter(),['datasource'=>'test']);
        $this->assertFalse($migration->tableExists('products'));
        $migration->start();
        $this->assertTrue($migration->tableExists('products'));

        $migration->reset();
        $migration->rollback();
        $this->assertFalse($migration->tableExists('products'));
    }

    public function testExecute(){
       $migration = new  UsingExecuteMigration(new MysqlAdapter(),['datasource'=>'test']);
       $migration->start();
       $this->assertNotEmpty($migration->statements());

       $migration = new  UsingExecuteMigration(new MysqlAdapter(),['datasource'=>'test']);
       $migration->rollback();
       $this->assertNotEmpty($migration->statements());
       
    }

    public function testExecuteExecptionChange(){
        $migration = new DidNotReadTheManualMigration(new MysqlAdapter(),['datasource'=>'test']);
        $this->expectException(Exception::class);
        $migration->start();
    }

    public function testExecuteExecptionReversable(){
        $migration = new DidNotReadTheManualMigration(new MysqlAdapter(),['datasource'=>'test']);
        $this->expectException(Exception::class);
        $migration->rollback();
    }

}