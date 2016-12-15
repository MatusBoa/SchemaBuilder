<?php

namespace Davajlama\SchemaBuilder\Schema;

/**
 * Description of Table
 *
 * @author David Bittner <david.bittner@seznam.cz>
 */
class Table
{
    /** @var string */
    private $name;
    
    /** @var string */
    private $engine = 'InnoDB';
    
    /** @var string */
    private $charset = 'utf8';
    
    /** @var Column[] */
    private $columns = [];
    
    /** @var Index[] */
    private $indexes = [];
    
    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * @return string
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }
        
    /**
     * @param Column $column
     * @return self
     */
    public function addColumn(Column $column)
    {
        $this->columns[$column->getName()] = $column;
        return $this;
    }
    
    /**
     * @param string $name
     * @return Column|null
     */
    public function getColumn($name)
    {
        return array_key_exists($name, $this->columns) ? $this->columns[$name] : null;
    }
    
    /**
     * @param string $name
     * @param TypeInterface $type
     * @return Column
     */
    public function createColumn($name, TypeInterface $type)
    {
        $this->addColumn($column = new Column($name, $type));
        return $column;
    }

    /**
     * @param string $name
     * @return Column
     */
    public function createId($name = 'id')
    {
        return $this->createColumn($name, new Type\IntegerType())
                    ->primary()
                    ->autoincrement();
    }
    
    /**
     * @param Index $index
     * @return self
     */
    public function addIndex(Index $index)
    {
        $this->indexes[] = $index;
        return $this;
    }
    
    /**
     * @param bool $unique
     * @return Index
     */
    public function createIndex($unique = false)
    {
        $this->addIndex($index = new Index($unique));
        return $index;
    }
    
    /**
     * @param string[] $columns
     * @return Index
     */
    public function createUniqueIndex()
    {
        $this->addIndex($index = new Index(true));
        return $index;
    }
    
    /**
     * @return Index[]
     */
    public function getIndexes()
    {
        return $this->indexes;
    }
    
}
