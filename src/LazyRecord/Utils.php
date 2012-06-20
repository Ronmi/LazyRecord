<?php
namespace LazyRecord;
use LazyRecord\Schema\SchemaFinder;

class Utils
{
    static function getSchemaClassFromPathsOrClassNames($loader, $args,$logger = null)
    {
        $classes = array();
        if( count($args) && ! file_exists($args[0]) ) {
            // it's classnames
            foreach( $args as $class ) {
                // call class loader to load
                if( class_exists($class,true) ) {
                    $classes[] = $class;
                }
                else {
                    if( $logger )
                        $logger->warn( "$class not found." );
                    else
                        echo ">>> $class not found.\n";
                }
            }
        }
        else {
            $finder = new SchemaFinder;
            if( count($args) && file_exists($args[0]) ) {
                $finder->paths = $args;
            } 
            // load schema paths from config
            elseif( $paths = $loader->getSchemaPaths() ) {
                $finder->paths = $paths;
            }
            $finder->loadFiles();

            // load class from class map
            if( $classMap = $loader->getClassMap() ) {
                foreach( $classMap as $file => $class ) {
                    if( ! is_integer($file) && is_string($file) )
                        require $file;
                }
            }
            $classes = $finder->getSchemaClasses();
        }
        return $classes;
    }

    static function breakDSN($dsn) {
        // break DSN string down into parameters
        $params = array();
        if( strpos( $dsn, ':' ) === false ) {
            $params['driver'] = $dsn;
            return $params;
        }

        list($driver,$paramString) = explode(':',$dsn,2);
        $params['driver'] = $driver;

        if( $paramString === ':memory:' ) {
            $params[':memory:'] = 1;
            return $params;
        }

        $paramPairs = explode(';',$paramString);
        foreach( $paramPairs as $pair ) {
            if( preg_match('#(\S+)=(\S+)#',$pair,$regs) ) {
                $params[$regs[1]] = $regs[2];
            }
        }
        return $params;
    }

    static function evaluate($data, $params = array() ) {
        if( $data && is_callable($data) ) {
            return call_user_func_array($data, $params );
        }
        return $data;
    }
}
