<?php

namespace Davajlama\SchemaBuilder\Test\Driver\MySql\Suite;

use Davajlama\SchemaBuilder\Schema;
use Davajlama\SchemaBuilder\Schema\Type;
use Davajlama\SchemaBuilder\Test\TestCase;

/**
 * Description of AlterSchema
 *
 * @author David Bittner <david.bittner@seznam.cz>
 */
class SchemaTest extends TestCase
{
    /** @var \Davajlama\SchemaBuilder\Adapter\AdapterInterface */
    private $adapter;
    
    public function testSchema()
    {        
        if(getenv('TESTDSN') && getenv('TESTUSER')) {
            $this->createTest();
            $this->alterTest();
        } else {
            $this->markTestSkipped("Must set ENV VAR TESTDSN=dsn and TESTUSER=user");
        }
    }
    
    protected function createTest()
    {
        $adapter    = $this->getAdapter();
        $driver     = new \Davajlama\SchemaBuilder\Driver\MySqlDriver($adapter);
        $builder    = new \Davajlama\SchemaBuilder\SchemaBuilder($driver);
        $creator    = new \Davajlama\SchemaBuilder\SchemaCreator($driver);
        
        $patches = $builder->buildSchemaPatches($this->getOriginalSchema());
        
        $this->assertTrue($patches instanceof \Davajlama\SchemaBuilder\PatchList);
        $this->assertSame(2, $patches->count());
        
        // table articles
        $sql = 'CREATE TABLE `articles` (';
        $sql .= '`id` int(11) NOT NULL AUTO_INCREMENT, ';
        $sql .= '`author` VARCHAR(64) DEFAULT NULL, ';
        $sql .= '`content` TEXT DEFAULT NULL, ';
        $sql .= '`title` VARCHAR(255) DEFAULT NULL, ';
        $sql .= '`created` DATETIME DEFAULT CURRENT_TIMESTAMP, ';
        $sql .= 'PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
        
        $patch = $patches->first();
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::NON_BREAKABLE, $patch->getLevel());
        $this->assertSame($sql, $patch->getQuery());
        
        // table users
        $sql = 'CREATE TABLE `users` (';
        $sql .= '`id` int(11) NOT NULL AUTO_INCREMENT, ';
        $sql .= '`login` VARCHAR(64) NOT NULL, ';
        $sql .= '`password` VARCHAR(64) NOT NULL, ';
        $sql .= '`name` VARCHAR(64) DEFAULT NULL, ';
        $sql .= '`created` DATETIME DEFAULT NULL, ';
        $sql .= '`group` VARCHAR(32) NOT NULL DEFAULT \'admin\', ';
        $sql .= 'PRIMARY KEY (`id`), ';
        $sql .= 'UNIQUE KEY `login_UNIQUE` (`login`), ';
        $sql .= 'UNIQUE KEY `password_UNIQUE` (`password`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
        
        $patch = $patches->next();
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::NON_BREAKABLE, $patch->getLevel());
        $this->assertSame($sql, $patch->getQuery());
        
        $creator->applyPatches($patches);
    }
    
    protected function getOriginalSchema()
    {
        $schema = new Schema();
        
        $articles = $schema->createTable('articles');
        $articles->createId();
        $articles->createColumn('author', Type::varcharType(64));
        $articles->createColumn('content', Type::textType());
        $articles->createColumn('title', Type::varcharType(255));
        $articles->createColumn('created', Type::dateTimeType())
                    ->setDefaultValue(Schema\Value::expressionValue('CURRENT_TIMESTAMP'));
        
        $users = $schema->createTable('users');
        $users->createId();
        $users->createColumn('login', Type::varcharType(64))->nullable(false)->unique();
        $users->createColumn('password', Type::varcharType(64))->nullable(false)->unique();
        $users->createColumn('name', Type::varcharType(64));
        $users->createColumn('created', Type::dateTimeType());
        $users->createColumn('group', Type::varcharType(32))
                    ->nullable(false)->setDefaultValue(Schema\Value::stringValue('admin'));
        
        return $schema;
    }
    
    public function alterTest()
    {
        $adapter    = $this->getAdapter();
        $driver     = new \Davajlama\SchemaBuilder\Driver\MySqlDriver($adapter);
        $builder    = new \Davajlama\SchemaBuilder\SchemaBuilder($driver);
        $creator    = new \Davajlama\SchemaBuilder\SchemaCreator($driver);
        
        $patches = $builder->buildSchemaPatches($this->getUpdatedSchema());
        
        $this->assertTrue($patches instanceof \Davajlama\SchemaBuilder\PatchList);
        $this->assertSame(12, $patches->count());
        
        // table articles
        $patch = $patches->first();
        $sql = 'ALTER TABLE `articles` CHANGE COLUMN `author` `author` VARCHAR(255) DEFAULT NULL;';
        $this->assertSame($sql, $patch->getQuery());
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::BREAKABLE, $patch->getLevel());
        
        $patch = $patches->next();
        $sql = 'ALTER TABLE `articles` CHANGE COLUMN `content` `content` VARCHAR(255) DEFAULT NULL;';
        $this->assertSame($sql, $patch->getQuery());
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::BREAKABLE, $patch->getLevel());
        
        $patch = $patches->next();
        $sql = 'ALTER TABLE `articles` CHANGE COLUMN `created` `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;';
        $this->assertSame($sql, $patch->getQuery());
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::BREAKABLE, $patch->getLevel());
        
        $patch = $patches->next();
        $sql = 'ALTER TABLE `articles` ADD COLUMN `perex` VARCHAR(255) DEFAULT NULL AFTER `content`;';
        $this->assertSame($sql, $patch->getQuery());
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::NON_BREAKABLE, $patch->getLevel());
        
        $patch = $patches->next();
        $sql = 'ALTER TABLE `articles` DROP COLUMN `title`;';
        $this->assertSame($sql, $patch->getQuery());
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::BREAKABLE, $patch->getLevel());
        
        // table users
        $patch = $patches->next();
        $sql = 'ALTER TABLE `users` DROP INDEX `password_UNIQUE`;';
        $this->assertSame($sql, $patch->getQuery());
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::NON_BREAKABLE, $patch->getLevel());
        
        $patch = $patches->next();
        $sql = 'ALTER TABLE `users` CHANGE COLUMN `created` `created` DATETIME DEFAULT CURRENT_TIMESTAMP;';
        $this->assertSame($sql, $patch->getQuery());
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::BREAKABLE, $patch->getLevel());
        
        $patch = $patches->next();
        $sql = 'ALTER TABLE `users` CHANGE COLUMN `group` `group` VARCHAR(32) NOT NULL DEFAULT \'customer\';';
        $this->assertSame($sql, $patch->getQuery());
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::BREAKABLE, $patch->getLevel());
        
        $patch = $patches->next();
        $sql = 'ALTER TABLE `users` ADD COLUMN `firstname` VARCHAR(64) DEFAULT NULL AFTER `password`;';
        $this->assertSame($sql, $patch->getQuery());
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::NON_BREAKABLE, $patch->getLevel());
        
        $patch = $patches->next();
        $sql = 'ALTER TABLE `users` ADD COLUMN `lastname` VARCHAR(64) NOT NULL AFTER `firstname`;';
        $this->assertSame($sql, $patch->getQuery());
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::NON_BREAKABLE, $patch->getLevel());
        
        $patch = $patches->next();
        $sql = 'ALTER TABLE `users` ADD UNIQUE INDEX `lastname_UNIQUE` (`lastname`);';
        $this->assertSame($sql, $patch->getQuery());
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::NON_BREAKABLE, $patch->getLevel());
        
        $patch = $patches->next();
        $sql = 'ALTER TABLE `users` DROP COLUMN `name`;';
        $this->assertSame($sql, $patch->getQuery());
        $this->assertSame(\Davajlama\SchemaBuilder\Patch::BREAKABLE, $patch->getLevel());
        
        $creator->applyPatches($patches);
    }
    
    protected function getUpdatedSchema()
    {
        $schema = new Schema();
        
        $articles = $schema->createTable('articles');
        $articles->createId();
        $articles->createColumn('author', Type::varcharType(255)); // change type
        $articles->createColumn('content', Type::varcharType(255)); // change type
        //$articles->createColumn('title', Type::varcharType(255)); // remove
        $articles->createColumn('perex', Type::varcharType(255)); // added
        $articles->createColumn('created', Type::dateTimeType())
                    ->nullable(false) // notnull
                    ->setDefaultValue(Schema\Value::expressionValue('CURRENT_TIMESTAMP'));
        
        $users = $schema->createTable('users');
        $users->createId();
        $users->createColumn('login', Type::varcharType(64))->nullable(false)->unique();
        $users->createColumn('password', Type::varcharType(64))->nullable(false); // drop unique
        //$users->createColumn('name', Type::varcharType(64)); // remove
        $users->createColumn('firstname', Type::varcharType(64)); // added
        $users->createColumn('lastname', Type::varcharType(64))->nullable(false)->unique(); // added
        $users->createColumn('created', Type::dateTimeType())
                    ->setDefaultValue(Schema\Value::expressionValue('CURRENT_TIMESTAMP')); // set default value
        $users->createColumn('group', Type::varcharType(32))
                    ->nullable(false)
                    ->setDefaultValue(Schema\Value::stringValue('customer')); // change default value
        
        return $schema;
    }
    
    protected function getAdapter()
    {
        if($this->adapter === null) {
            //$dsn = 'mysql:host=localhost;dbname=buildertests';
            $dsn        = getenv('TESTDSN');
            $username   = getenv('TESTUSER');
            
            $options = array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            ); 

            $pdo = new \PDO($dsn, $username, null, $options);
            $this->adapter = new \Davajlama\SchemaBuilder\Bridge\PDOAdapter($pdo);
        }        
        
        return $this->adapter;
    }
}