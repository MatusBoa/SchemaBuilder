<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 05.03.2019
 * Time: 17:40
 */

namespace Davajlama\SchemaBuilder\Driver\SQLite;

use Davajlama\SchemaBuilder\PatchList;
use Davajlama\SchemaBuilder\Schema\Index;

/**
 * Description of Generator
 *
 * @author David Bittner <david.bittner@seznam.cz>
 * @todo This class waiting for refactoring
 */
class Generator
{
    /** @var Translator */
    private $translator;

    public function createTablePatches(\Davajlama\SchemaBuilder\Schema\Table $table)
    {
        $primary = [];
        $columns = [];

        foreach($table->getColumns() as $column) {
            if($column->isPrimary()) {
                $primary[] = $column->getName();
            }
        }

        foreach($table->getColumns() as $column) {
            $columns[] = $this->getTranslator()->transColumn($column, count($primary) > 1);
        }

         //tady zrejme pokud bude vic jak jeden primary
        if(count($primary) > 1) {
            $columns[] = $this->getTranslator()->transPrimaryKey($primary);
        }

        $indexes = new PatchList();
        foreach($table->getColumns() as $column) {
            if($column->isUnique()) {
                $index = new Index($column->isUnique());
                $index->addColumn($column->getName());
                //$name = $this->createIndexName($index);

                $indexes->addPatch($this->createIndex($table, $index));
            }
        }

        $header = $this->getTranslator()->transCreateTableHeader($table->getName());
        $footer = $this->getTranslator()->transCreateTableFooter($table->getEngine(), $table->getCharset());
        $body   = implode(', ', $columns);

        $query = $header . $body . $footer;

        $list = new \Davajlama\SchemaBuilder\PatchList();
        $list->createPatch($query, \Davajlama\SchemaBuilder\Patch::NON_BREAKABLE);
        $list->addPatches($indexes);

        return $list;
    }

    public function alterTablePatches(\Davajlama\SchemaBuilder\Schema\Table $table, Array $rawColumns)
    {
        $original = [];
        foreach($rawColumns as $position => $row) {
            $row['Position'] = $position;
            $original[$row['Field']] = $row;
        }

        $list = new \Davajlama\SchemaBuilder\PatchList();
        $before = null;
        $nonExists = [];
        foreach($table->getColumns() as $column) {

            if(array_key_exists($column->getName(), $original)) {
                $origColumn = $original[$column->getName()];
                unset($original[$column->getName()]);

                if($patches = $this->compareColumns($origColumn, $column, $table)) {
                    $list->addPatches($patches);
                }

            } else {
                $nonExists[] = [
                    'before' => $before,
                    'column' => $column,
                ];
            }

            $before = $column;
        }

        foreach($nonExists as $non) {
            $pos = ($before = $non['before']) ? "AFTER `{$before->getName()}`" : 'FIRST';
            $col = $non['column'];

            $query = "ALTER TABLE `{$table->getName()}` ";
            $query .= "ADD COLUMN " . $this->getTranslator()->transColumn($col);
            $query .= " $pos;";

            $list->createPatch($query, \Davajlama\SchemaBuilder\Patch::NON_BREAKABLE);

            /*if($col->isUnique()) {
                $name = $this->createIndexName([new \Davajlama\SchemaBuilder\Schema\IndexColumn($col->getName())], $col->isUnique());
                $query = "ALTER TABLE `{$table->getName()}` ";
                $query .= "ADD UNIQUE INDEX `$name` (`{$col->getName()}`)";
                $query .= ";";

                $list->createPatch($query, \Davajlama\SchemaBuilder\Patch::NON_BREAKABLE);
            }*/
        }

        foreach($original as $row) {
            $query = "ALTER TABLE `{$table->getName()}` ";
            $query .= "DROP COLUMN `{$row['Field']}`";
            $query .= ";";

            $list->createPatch($query, \Davajlama\SchemaBuilder\Patch::BREAKABLE);
        }

        return $list;
    }

    protected function compareColumns($row, \Davajlama\SchemaBuilder\Schema\Column $column, \Davajlama\SchemaBuilder\Schema\Table $table)
    {

        $list = new \Davajlama\SchemaBuilder\PatchList();

        $changeUnique   = false;
        $change         = false;

        $type = $row['Type'];
        if(strtolower($type) !== strtolower($this->getTranslator()->transType($column->getType()))) {
            $change = true;
        }

        $nullable = $row['Null'] === 'YES';
        if($nullable !== $column->isNullable()) {
            $change = true;
        }

        /*$unique = $row['Key'] === 'UNI';
        if($unique !== $column->isUnique()) {
            $changeUnique = true;
        }*/

        $default = $row['Default'];
        // db returns NULL or STRING
        if(($default === null && $default !== $column->getDefault()->getValue()) || ($default !== null && (string)$default !== (string)$column->getDefault()->getValue())) {
            $change = true;
        }

        if($change) {
            $query = "ALTER TABLE `{$table->getName()}` ";
            $query .= "CHANGE COLUMN `{$column->getName()}` " . $this->getTranslator()->transColumn($column);
            $query .= ";";

            $list->createPatch($query, \Davajlama\SchemaBuilder\Patch::BREAKABLE);
        }

        /*if($changeUnique) {

            $query = "ALTER TABLE `{$table->getName()}` ";
            $name = $this->createIndexName([new \Davajlama\SchemaBuilder\Schema\IndexColumn($column->getName())], true);
            if($column->isUnique()) {
                $query .= "ADD UNIQUE INDEX `$name` (`{$column->getName()}`)";
            } else {
                $query .= "DROP INDEX `$name`";
            }

            $query .= ";";
            $list->createPatch($query, \Davajlama\SchemaBuilder\Patch::NON_BREAKABLE);
        }*/

        return $list;
    }

    public function createIndexes(\Davajlama\SchemaBuilder\Schema\Table $table)
    {
        $list = new \Davajlama\SchemaBuilder\PatchList();
        foreach($table->getIndexes() as $index) {
            $list->addPatch($this->createIndex($table, $index));
        }

        return $list;
    }

    public function alterIndexes(\Davajlama\SchemaBuilder\Schema\Table $table, Array $rawIndexes)
    {
        $list = new \Davajlama\SchemaBuilder\PatchList();

        $transformed = [];
        foreach($rawIndexes as $row) {
            $transformed[$row['Key_name']] = $row;
        }

        $indexesNames = [];
        $createIndexPatches = [];
        foreach($table->getColumns() as $column) {
            if($column->isPrimary()) {
                $indexesNames['PRIMARY'] = true;
            }

            if($column->isUnique()) {
                $index = new \Davajlama\SchemaBuilder\Schema\Index(true);
                $index->addColumn($column->getName());

                $name = $this->createIndexName($index);
                if(isset($transformed[$name])) {
                    $indexesNames[$name] = true;
                } else {
                    $createIndexPatches[] = $this->createIndex($table, $index);
                }
            }
        }

        foreach($table->getIndexes() as $index) {
            $name = $this->createIndexName($index);
            if(isset($indexesNames[$name]) || isset($transformed[$name])) {
                $indexesNames[$name] = true;
                continue;
            } else {
                $createIndexPatches[] = $this->createIndex($table, $index);
                $indexesNames[$name] = true;
            }
        }

        foreach($transformed as $name => $ind) {
            if(!isset($indexesNames[$name])) {
                $query = "ALTER TABLE `{$table->getName()}` DROP INDEX `$name`;";
                $list->createPatch($query, \Davajlama\SchemaBuilder\Patch::NON_BREAKABLE);
            }
        }

        foreach($createIndexPatches as $patch) {
            $list->addPatch($patch);
        }

        return $list;
    }

    protected function createIndex(\Davajlama\SchemaBuilder\Schema\Table $table, \Davajlama\SchemaBuilder\Schema\Index $index)
    {
        $cols = [];
        $name = $this->createIndexName($index);

        foreach($index->getColumns() as $column) {
            $order = $column->isASC() ? 'ASC' : 'DESC';
            $cols[] = "`{$column->getName()}` $order";
        }

        $unique = $index->isUnique() ? 'UNIQUE' : '';

        $columns = implode(', ', $cols);
        $sql = "CREATE {$unique} INDEX IF NOT EXISTS `$name` ON `{$table->getName()}` ($columns);";

        return new \Davajlama\SchemaBuilder\Patch($sql, \Davajlama\SchemaBuilder\Patch::NON_BREAKABLE);
    }

    protected function createIndexName(Index $index)
    {
        if($index->getName()) {
            return $index->getName();
        }

        $name = $index->isUnique() ? 'unique' : 'index';
        foreach($index->getColumns() as $column) {
            $order = $column->isASC() ? 'ASC' : 'DESC';
            $name .= '_' . $column->getName() . '_' . strtolower($order);
        }

        return $name;
    }

    /**
     * @return Translator
     */
    protected function getTranslator()
    {
        if($this->translator === null) {
            $this->translator = new Translator();
        }

        return $this->translator;
    }

}