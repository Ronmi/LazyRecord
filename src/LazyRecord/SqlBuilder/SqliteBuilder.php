<?php
namespace LazyRecord\SqlBuilder;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\QueryBuilder;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\RuntimeColumn;
use LazyRecord\Schema\Relationship;
use LazyRecord\Schema\ColumnDeclare;
use SQLBuilder\ArgumentArray;

/**
 * Schema SQL builder
 *
 * @see http://www.sqlite.org/docs.html
 */
class SqliteBuilder extends BaseBuilder 
{
    public function buildColumnSql(SchemaInterface $schema, ColumnDeclare $column) {
        $name = $column->name;
        $isa  = $column->isa ?: 'str';
        $type = $column->type;
        if( ! $type && $isa == 'str' )
            $type = 'text';

        $args = new ArgumentArray;
        $sql = $column->buildDefinitionSql($this->driver, $args);

        /**
         * build sqlite reference
         *    create table track(
         *        trackartist INTEGER,
         *        FOREIGN KEY(trackartist) REFERENCES artist(artistid)
         *    )
         * @see http://www.sqlite.org/foreignkeys.html
         *
         * CREATE TABLE album(
         *     albumartist TEXT,
         *     albumname TEXT,
         *     albumcover BINARY,
         *     PRIMARY KEY(albumartist, albumname)
         *     );
         *
         * CREATE TABLE song(
         *     songid     INTEGER,
         *     songartist TEXT,
         *     songalbum TEXT,
         *     songname   TEXT,
         *     FOREIGN KEY(songartist, songalbum) REFERENCES album(albumartist, albumname)
         * );
         */
        foreach( $schema->relations as $rel ) {
            switch( $rel['type'] ) {
            case Relationship::BELONGS_TO:
            case Relationship::HAS_MANY:
            case Relationship::HAS_ONE:
                if ($name != 'id' && $rel['self_column'] == $name)
                {
                    $fSchema = new $rel['foreign_schema'];
                    $fColumn = $rel['foreign_column'];
                    $sql .= ' REFERENCES ' . $fSchema->getTable() . '(' . $fColumn . ')';
                }
                break;
            }
        }
        return $sql;
    }

    public function dropTable(SchemaInterface $schema)
    {
        return 'DROP TABLE IF EXISTS ' 
            . $this->driver->quoteIdentifier( $schema->getTable() )
            . ';';
    }

    public function buildIndex(SchemaInterface $schema)
    {
        return array();
    }

    public function buildForeignKeys(SchemaInterface $schema) {
        return array();
    }

}
