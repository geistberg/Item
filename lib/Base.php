<?php

require_once(__DIR__ . '/config.php');

class Base {

    public function __construct($args = [], $SHARE = null) {
        $this->config = new Config;
        $this->args   = $args;
        if ( $SHARE ) {
            $this->SHARE = $SHARE;
        } else {
            if ( get_class($this) != 'Base' ) {
                $class  = get_class($this);
                $origin = debug_backtrace()[2];
                die('DOOOM! SHARE NOT PASSED TO ' . $class . 
                    ': CHECK FOR new ' . $class . '($args, $SHARE); ON '
                    . $origin['file'] . ' : ' . $origin['line']);    
            };
            $this->SHARE = (object)[
                'memcache' => $this->memcache(),
                'db_ro'    => $this->db_ro(),
                'db'       => $this->db(),
            ];
        };
        $this->cache = $this->SHARE->memcache;
    }
    
    public function arg($key = null) {
        if ( !$key ) {
            return null;
        }
        return isset($this->args[$key]) ? $this->args[$key] : null;
    }

    public function query($sql, $params = [], $create = null) {
        if ( !is_array($params) ) {
            $params = [ $params ];
        }
        $sth = $this->SHARE->db->prepare($sql);
        foreach( $params as $key => $value ) {
            $sth->bindValue(++$key, $value);
        }
        $result = null;
        try {
            $result = $sth->execute();
        } catch ( Exception $e ) {
            return [ 'error' => $e=>getMessage(), 'success' => null ];
        }
        if ( $result ) {
            $resp =  $create ? $this->SHARE->db->lastInsertId() : $sth->fetchAll(PDO::FETCH_CLASS);
            return [ 'success' => $resp, 'error' => null ];
        } else {
            return [ 'error' => $sth->errorInfo()[2], 'success' => null ];
        }
    }

    public function query_ro($sql, $params = []) {
        if ( !is_array($params) ) {
            $params = [ $params ];
        }
        $sth = $this->SHARE->db_ro->prepare($sql);
        foreach( $params as $key => $value ) {
            $sth->bindValue(++$key, $value);
        }
        $result = null;
        try {
            $result = $sth->execute();
        } catch ( Exception $e ) {
            return [ 'error' => $e=>getMessage(), 'success' => null ];
        }
        $result = $sth->execute();
        if ( $result ) {
            return [ 'success' => $sth->fetchAll(PDO::FETCH_CLASS), 'error' => null ];
        } else {
            return [ 'error' => $sth->errorInfo()[2], 'success' => null ];
        }
    }
    
    private function memcache() {
        $config = $this->config->memcache;
        $cache  = new Memcached;
        $cache->addServer($config['server'], $config['port']);
        return $cache;
    }

    private function db() {
        $config = $this->config->database['write'];
        $host   = $config['hostname'];
        $name   = $config['database'];
        $port   = $config['port'];
        $driver = $config['driver'];
        return new PDO("$driver:host=$host;dbname=$name;port=$port", $config['username'], $config['password']);
    }

    private function db_ro() {
        $config = $this->config->database['read_only'];
        $host   = $config['hostname'];
        $name   = $config['database'];
        $port   = $config['port'];
        $driver = $config['driver'];
        return new PDO("$driver:host=$host;dbname=$name;port=$port", $config['username'], $config['password']);
    }

}

?>
