<?php

namespace Davajlama\SchemaBuilder;

/**
 * Description of Patch
 *
 * @author David Bittner <david.bittner@seznam.cz>
 */
class Patch
{
    const NON_BREAKABLE = 0;
    const BREAKABLE = 1;
    
    /** @var string */
    private $query;
    
    /** @var int */
    private $level;

    /**
     * @param string $query
     * @param int $level
     */
    public function __construct($query, $level)
    {
        $this->query = $query;
        $this->level = $level;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

}
