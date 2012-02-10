<?php
namespace Lazy;
use SQLBuilder\Driver;


/**
 * QueryDriver
 *
 * to setup QueryDriver:
 *
 *      $driver = QueryDriver::getInstance('data_source_id');
 *      $driver->configure('driver','pgsql');
 *      $driver->configure('quote_column',true);
 *      $driver->configure('quote_table',true);
 *
 *
 */
class QueryDriver extends Driver
{
    static $drivers = array();

    static function getInstance($id = 'default')
    {
        if( isset(static::$drivers[ $id ]) )
            return static::$drivers[ $id ];
        return static::$drivers[ $id ] = new static;
    }

    static function free()
    {
        static::$drivers = array();
    }


    /* extended methods */

}

