<?php
namespace LazyRecord\Schema;


use Exception;
use ReflectionObject;
use ReflectionClass;
use LazyRecord\ConfigLoader;
use LazyRecord\ClassUtils;
use LazyRecord\Schema\Relationship;
use LazyRecord\Schema\ColumnDeclare;

use ClassTemplate\TemplateClassFile;
use ClassTemplate\ClassTrait;

class DeclareSchema extends SchemaBase implements SchemaInterface
{

    /**
     * @var string[]
     */
    public $modelTraitClasses = array();

    /**
     * @var string[]
     */
    public $collectionTraitClasses = array();

    /**
     * Constructor of declare schema.
     *
     * The constructor calls `build` method to build the schema information.
     */
    public function __construct(array $options = array())
    {
        $this->build($options);
    }

    /**
     * Build schema
     */
    public function build(array $options = array())
    {
        $this->schema($options);

        // postSchema is added for mixin that needs all schema information, for example LocalizeMixin
        foreach ($this->mixinSchemas as $mixin) {
            $mixin->postSchema();
        }

        $this->primaryKey = $this->findPrimaryKey();

        // if the primary key is not define, we should append the default primary key => id
        // AUTOINCREMENT is only allowed on an INTEGER PRIMARY KEY
        if (null === $this->primaryKey) {
            if ($config = ConfigLoader::getInstance()) {
                if ($config->hasAutoId() && ! isset($this->columns['id'] )) {
                    $this->insertAutoIdColumn();
                }
            }
        }
    }

    public function schema() 
    {

    }


    public function getWriteSourceId()
    {
        return $this->writeSourceId ?: 'default';
    }

    public function getReadSourceId()
    {
        return $this->readSourceId ?: 'default';
    }

    public function using( $id ) 
    {
        $this->writeSourceId = $id;
        $this->readSourceId = $id;
        return $this;
    }

    public function writeTo( $id ) 
    {
        $this->writeSourceId = $id;
        return $this;
    }

    public function readFrom( $id ) 
    {
        $this->readSourceId = $id;
        return $this;
    }


    /**
     * Define seed class
     *
     * $this->seeds('User\Seed','Data\Seed');
     */
    public function seeds() 
    {
        $seeds = func_get_args();
        $this->seeds = array_map(function($class){ 
            return str_replace('::','\\',$class);
        }, $seeds);
        return $this;
    }

    public function addSeed($seed)
    {
        $this->seeds[] = $seed;
        return $this;
    }

    /**
     * bootstrap script (to create basedata)
     *
     * @param $record current model object.
     */
    public function bootstrap($record) 
    {

    }

    public function getColumns($includeVirtual = false) 
    {
        if ( $includeVirtual ) {
            return $this->columns;
        }

        $columns = array();
        foreach( $this->columns as $name => $column ) {
            // skip virtal columns
            if( $column->virtual )
                continue;
            $columns[ $name ] = $column;
        }
        return $columns;
    }

    public function getColumnLabels($includeVirtual = false) 
    {
        $labels = array();
        foreach( $this->columns as $column ) {
            if ( ! $includeVirtual && $column->virtual ) {
                continue;
            }
            if ( $column->label ) {
                $labels[] = $column->label;
            }
        }
        return $labels;
    }


    public function getColumnNames($includeVirtual = false)
    {
        if ( $includeVirtual ) {
            return array_keys($this->columns);
        }

        $names = array();
        foreach( $this->columns as $name => $column ) {
            if ( $column->virtual ) {
                continue;
            }
            $names[] = $name;
        }
        return $names;
    }


    public function getColumn($name)
    {
        if ( isset($this->columns[ $name ]) ) {
            return $this->columns[ $name ];
        }
    }

    public function hasColumn($name)
    {
        return isset($this->columns[ $name ]);
    }


    public function insertAutoIdColumn()
    {
        $column = new ColumnDeclare('id');
        $column->isa('int')
            ->integer()
            ->notNull()
            ->primary()
            ->autoIncrement();
        $this->primaryKey = 'id';
        $this->columns[ 'id' ] = $column;
        array_unshift($this->columnNames,'id');
    }

    /**
     * Find primary keys from columns
     */
    public function findPrimaryKey()
    {
        foreach( $this->columns as $name => $column ) {
            if ( $column->primary ) {
                return $name;
            }
        }
    }

    public function export()
    {
        $columnArray = array();
        foreach( $this->columns as $name => $column ) {

            // This idea is from:
            // http://search.cpan.org/~tsibley/Jifty-DBI-0.75/lib/Jifty/DBI/Schema.pm
            //
            // if the refer attribute is defined, we should create the belongsTo relationship
            if ( $refer = $column->refer ) {
                // remove _id suffix if possible
                $accessorName = preg_replace('#_id$#','',$name);
                $schema = null;
                $schemaClass = $refer;

                // convert class name "Post" to "PostSchema"
                if ( substr($refer, -strlen('Schema')) != 'Schema') {
                    if ( class_exists($refer. 'Schema', true) ) {
                        $refer = $refer . 'Schema';
                    }
                }

                if (! class_exists($refer)) {
                    throw new Exception("refer schema from '$refer' not found.");
                }

                $o = new $refer;
                // schema is defined in model
                $schemaClass = $refer;
                $this->belongsTo($accessorName, $schemaClass, 'id', $name);
            }
            $columnArray[ $name ] = $column->export();
        }
        return array(
            'label'             => $this->getLabel(),
            'table'             => $this->getTable(),
            'column_data'       => $columnArray,
            'column_names'      => $this->columnNames,
            'primary_key'       => $this->primaryKey,
            'model_class'       => $this->getModelClass(),
            'collection_class'  => $this->getCollectionClass(),
            'relations'         => $this->relations,
            'read_data_source'  => $this->readSourceId,
            'write_data_source' => $this->writeSourceId,
        );
    }

    public function dump()
    {
        return var_export( $this->export() , true );
    }

    /**
     * Define schema label.
     *
     * @param string $label label name
     */
    public function label($label)
    {
        $this->label = $label;
        return $this;
    }


    /**
     * Define table name
     *
     * @param string $table table name
     */
    public function table($table)
    {
        $this->table = $table;
        return $this;
    }


    /**
     * Use trait for the model class.
     *
     * @param string $class...
     * @return ClassTrait object
     */
    public function addModelTrait($traitClass) {
        $this->modelTraitClasses[] = $traitClass;
    }

    /**
     * Use trait for the model class.
     *
     * @param string $class...
     * @return ClassTrait object
     */
    public function addCollectionTrait($traitClass) {
        $this->collectionTraitClasses[] = $traitClass;
    }


    /** 
     * @return string[]
     */
    public function getModelTraitClasses() {
        return $this->modelTraitClasses;
    }


    /** 
     * @return string[]
     */
    public function getCollectionTraitClasses() {
        return $this->modelTraitClasses;
    }



    /**
     * Mixin
     *
     * Availabel mixins
     *
     *     $this->mixin('Metadata' , array( options ) );
     *     $this->mixin('I18n');
     *
     * @param string $class mixin class name
     */
    public function mixin($class, array $options = array())
    {
        if (! class_exists($class,true) ) {
            $class = 'LazyRecord\\Schema\\Mixin\\' . $class;
            if ( ! class_exists($class,true) ) {
                throw new Exception("Mixin class $class not found.");
            }
        }

        $mixin = new $class($this, $options);
        $this->addMixinSchemaClass($class);
        $this->mixinSchemas[] = $mixin;

        /* merge columns into self */
        $this->columns = array_merge($this->columns, $mixin->columns);
        $this->relations = array_merge($this->relations, $mixin->relations);
        $this->modelTraitClasses = array_merge($this->modelTraitClasses, $mixin->modelTraitClasses);
    }

    public function getLabel()
    {
        return $this->label ?: $this->_modelClassToLabel();
    }

    public function getTable()
    {
        return $this->table ?: $this->_classnameToTable();
    }

    /**
     * classname methods
     */
    public function getModelClass()
    {
        // If self class name is endded with 'Schema', remove it and return.
        $class = get_class($this);
        if( ( $p = strrpos($class, 'Schema' ) ) !== false ) {
            return substr($class , 0 , $p);
        }
        // throw new Exception('Can not get model class from ' . $class );
        return $class;
    }

    /**
     * Get directory from current schema object.
     *
     * @return string path
     */
    public function getDirectory()
    {
        static $dir;
        if ( $dir ) {
            return $dir;
        }

        $refl = new ReflectionObject($this);
        return $dir = dirname($refl->getFilename());
    }

    public function getModificationTime()
    {
        $refl = new ReflectionObject($this);
        return filemtime($refl->getFilename());
    }

    public function isNewerThanFile($path) 
    {
        if ( ! file_exists($path) ) {
            return true;
        }
        $mtime1 = $this->getModificationTime();
        $mtime2 = filemtime($path);
        return $mtime1 > $mtime2;
    }

    /**
     * Convert current model name to a class name.
     *
     * @return string table name
     */
    protected function _classnameToTable() 
    {
        return ClassUtils::convertClassToTableName($this->getModelName());
    }

    public $enableColumnAccessors = true;

    public function disableColumnAccessors()
    {
        $this->enableColumnAccessors = false;
    }

    /**
     * Define new column object
     *
     * @param string $name column name
     * @param string $class column class name
     * @return ColumnDeclare
     */
    public function column($name, $class = 'LazyRecord\\Schema\\ColumnDeclare')
    {
        if (isset($this->columns[$name])) {
            throw new Exception("column $name of ". get_class($this) . " is already defined.");
        }
        $this->columnNames[] = $name;
        return $this->columns[ $name ] = new $class( $name );
    }


    public function addColumn(ColumnDeclare $column)
    {
        if (isset($this->columns[$column->name])) {
            throw new Exception("column $name of ". get_class($this) . " is already defined.");
        }
        $this->columnNames[] = $column->name;
        return $this->columns[ $column->name ] = $column;
    }


    /**
     * define self primary key to foreign key reference
     *
     * comments(
     *    post_id => author.comment_id
     * )
     *
     * $post->publisher
     *
     * @param string $foreignClass foreign schema class.
     * @param string $foreignColumn foreign reference schema column.
     * @param string $selfColumn self column name
     */
    public function belongsTo($accessor, $foreignClass = NULL, $foreignColumn = NULL,  $selfColumn = 'id')
    {
        if ($foreignClass && NULL === $foreignColumn) {
            $s = new $foreignClass;
            $foreignColumn = $s->primaryKey;
        }
        return $this->relations[$accessor] = new Relationship($accessor, array(
            'type' => Relationship::BELONGS_TO,
            'self_schema' => get_class($this),
            'self_column' => $selfColumn,
            'foreign_schema' => $foreignClass,
            'foreign_column' => $foreignColumn,
        ));
    }



    /**
     * has-one relationship
     *
     *   model(
     *      post_id => post
     *   )
     */
    public function one($accessor,$foreignClass,$foreignColumn = null, $selfColumn)
    {
        // foreignColumn is default to foreignClass.primary key
        return $this->relations[ $accessor ] = new Relationship($accessor, array(
            'type'           => Relationship::HAS_ONE,
            'self_column'    => $selfColumn,
            'self_schema'    => $this->getSchemaProxyClass(),
            'foreign_column' => $foreignColumn,
            'foreign_schema' => $foreignClass,
        ));
    }


    public function hasMany()
    {
        // forward call
        return call_user_func_array(array($this,'many'), func_get_args());
    }

    /**
     * Add has-many relation
     *
     *
     * TODO: provide a relationship object to handle sush operation, that will be:
     *
     *    $this->hasMany('books','id')
     *         ->from('App_Model_Book','author_id')
     *
     */
    public function many($accessor,$foreignClass,$foreignColumn,$selfColumn)
    {
        return $this->relations[ $accessor ] = new Relationship($accessor, array(
            'type'           => Relationship::HAS_MANY,
            'self_column'    => $selfColumn,
            'self_schema'    => get_class($this),
            'foreign_column' => $foreignColumn,
            'foreign_schema' => $foreignClass,
        ));
    }


    /**
     *
     * @param string $accessor   accessor name
     * @param string $relationId a hasMany relationship 
     */
    public function manyToMany($accessor, $relationId, $foreignRelationId )
    {
        if ($r = $this->getRelation($relationId)) {
            return $this->relations[ $accessor ] = new Relationship($accessor, array(
                'type'              => Relationship::MANY_TO_MANY,
                'relation_junction' => $relationId,
                'relation_foreign'  => $foreignRelationId,
            ));
        }
        throw new Exception("Relation $relationId is not defined.");
    }

    protected function _modelClassToLabel() 
    {
        /* Get the latest token. */
        if ( preg_match( '/(\w+)(?:Model)?$/', $this->getModelClass() , $reg) ) 
        {
            $label = @$reg[1];
            if ( ! $label ) {
                throw new Exception( "Table name error" );
            }

            /* convert blah_blah to BlahBlah */
            return ucfirst(preg_replace( '/[_]/' , ' ' , $label ));
        }
    }

    public function __toString() 
    {
        return get_class($this);
    }

    public function getMsgIds() {
        $ids = [];
        $ids[] = $this->getLabel();
        foreach( $this->getColumnLabels() as $label ) {
            $ids[] = $label;
        }
        return $ids;
    }


    /**
     * Get the related class file path.
     *
     * @param string $class the scheam related class name
     *
     * $schema->getRelatedClassPath( $schema->getModelClass() );
     * $schema->getRelatedClassPath("App\\Model\\Book"); // return {app dir}/App/Model/Book.php
     *
     * @return string the class filepath.
     */
    public function getRelatedClassPath($class) {
        $_p = explode('\\',$class);
        $shortClassName = end($_p);
        return $this->getDirectory() . DIRECTORY_SEPARATOR . $shortClassName . '.php';
    }


    /**
     * Invode helper
     *
     * @param string $helperName 
     * @param array $arguments indexed array, passed to the init function of helper class.
     *
     * @return Helper\BaseHelper
     */
    public function helper($helperName, $arguments = array()) {
        $helperClass = 'LazyRecord\\Schema\\Helper\\' . $helperName . 'Helper';
        $helper = new $helperClass($this, $arguments);
        return $helper;
    }

}

