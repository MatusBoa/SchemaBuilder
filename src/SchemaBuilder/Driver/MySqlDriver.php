<?php

namespace Davajlama\SchemaBuilder\Driver;

use Davajlama\SchemaBuilder\Adapter\AdapterInterface;
use Davajlama\SchemaBuilder\Driver\MySql\Generator;
use Davajlama\SchemaBuilder\Driver\MySql\Inspector;
use Davajlama\SchemaBuilder\DriverInterface;
use Davajlama\SchemaBuilder\Patch;
use Davajlama\SchemaBuilder\PatchList;
use Davajlama\SchemaBuilder\Schema;
use Davajlama\SchemaBuilder\Schema\Table;

/**
 * Description of MySqlDriver
 *
 * @author David Bittner <david.bittner@seznam.cz>
 */
class MySqlDriver implements DriverInterface
{
    /** @var AdapterInterface */
    private $adapter;
    
    /** @var Generator */
    private $generator;
    
    /** @var Inspector */
    private $inspector;

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    /**
     * @param Schema $schema
     * @return PatchList
     */
    public function buildSchemaPatches(Schema $schema)
    {
        $patches = new PatchList();
        foreach($schema->getTables() as $table) {
            $patches->addPatches($this->buildTablePatches($table));
        }

        $patches->addPatches($this->getGenerator()->dropTablePatches($schema, $this->getInspector()->showTables()));

        return $patches;
    }

    /**
     * @param Table $table
     * @return PatchList
     */
    public function buildTablePatches(Table $table)
    {
        $patches = new PatchList();
        if($this->getInspector()->existsTable($table->getName())) {
            $rawColumns = $this->getInspector()->describeTable($table->getName());
            $patches->addPatches($this->getGenerator()->alterTablePatches($table, $rawColumns));
            
            $rawIndexes = $this->getInspector()->showIndexes($table->getName());
            $patches->addPatches($this->getGenerator()->alterIndexes($table, $rawIndexes));
        } else {
            $patches->addPatches($this->getGenerator()->createTablePatches($table));
            $patches->addPatches($this->getGenerator()->createIndexes($table));
        }

        $this->fillHashes($patches);

        return $patches;
    }

    /**
     * @param Patch $patch
     */
    public function applyPatch(Patch $patch)
    {
        $this->getAdapter()->query($patch->getQuery());
    }

    /**
     * @param PatchList $list
     */
    protected function fillHashes(PatchList $list)
    {
        $stack = [];
        foreach($list->toArray() as $patch) {
            $hash = '';
            do {
                $hash = $this->makeHash($patch->getQuery(), $hash);
            } while(array_key_exists($hash, $stack));

            $stack[$hash] = true;
            $patch->setHash($hash);
        }
    }

    protected function makeHash($query, $hash = '')
    {
        return substr(sha1($query . $hash), 0, 10);
    }

    /**
     * @return Generator
     */
    protected function getGenerator()
    {
        if($this->generator === null) {
            $this->generator = new Generator();
        }
        
        return $this->generator;
    }
    
    /**
     * @return Inspector
     */
    protected function getInspector()
    {
        if($this->inspector === null) {
            $this->inspector = new Inspector($this->getAdapter());
        }
        
        return $this->inspector;
    }
    
    /**
     * @return AdapterInterface
     */
    protected function getAdapter()
    {
        return $this->adapter;
    }
    
}
