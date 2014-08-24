<?php
namespace LazyRecord;
use CLIFramework\Application;

class Console extends Application
{
    const name = 'LazyRecord';
    const VERSION = "1.16.1";

    public function brief()
    {
        return 'LazyRecord ORM';
    }

    public function init()
    {
        parent::init();

        /**
         * command for initialize related file structure
         */
        $this->registerCommand('init',    'LazyRecord\Command\InitCommand');

        /**
         * command for building config file.
         */
        $this->registerCommand('build-conf',   'LazyRecord\\Command\\BuildConfCommand');
        $this->registerCommand('conf',   'LazyRecord\\Command\\BuildConfCommand');

        /**
         * schema command.
         */
        $this->registerCommand('schema'); // the schema command builds all schema files and shows a diff after building new schema

        // XXX: move list to the subcommand of schema command, eg:
        //    $ lazy schema list
        //    $ lazy schema build
        //
        $this->registerCommand('list-schema'    , 'LazyRecord\\Command\\ListSchemaCommand');

        $this->registerCommand('build-schema'   , 'LazyRecord\\Command\\BuildSchemaCommand');

        $this->registerCommand('build-js-model'   , 'LazyRecord\\Command\\BuildJsModelCommand');

        $this->registerCommand('clean-schema'   , 'LazyRecord\\Command\\CleanSchemaCommand');

        $this->registerCommand('build-basedata' , 'LazyRecord\\Command\\BuildBaseDataCommand');

        $this->registerCommand('sql'            , 'LazyRecord\\Command\\BuildSqlCommand');

        $this->registerCommand('diff'           , 'LazyRecord\\Command\\DiffCommand');

        $this->registerCommand('migrate'        , 'LazyRecord\\Command\\MigrateCommand');

        $this->registerCommand('prepare'        , 'LazyRecord\\Command\\PrepareCommand');

        $this->registerCommand('meta'           , 'LazyRecord\\Command\\MetaCommand');
        $this->registerCommand('version'        , 'LazyRecord\\Command\\VersionCommand');

        $this->registerCommand('create-db'      , 'LazyRecord\\Command\\CreateDBCommand');
    }

    public static function getInstance() 
    {
        static $self;
        if( $self )
            return $self;
        return $self = new self;
    }

}
