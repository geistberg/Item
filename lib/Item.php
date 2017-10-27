<?php

require_once(__DIR__ . '/Base.php');

class Item extends Base {

    public function __construct($args = [], $SHARE = null) {
        parent::__construct($args, $SHARE);
        $this->table       = $this->arg('table');
        $this->primary_key = $this->arg('primay_key');
    }

    public function id() {
        return $this->args[$this->primary_key];
    }

    public function create($args) {
        if ( !$args ) {
            return [ 'error' => 'nothing to create', 'success' => null ];
        }
        $table   = $this->table;
        $sql     = "INSERT INTO $table ( ";
        $binds   = [];
        $columns = [];
        $values  = [];
        foreach ( $args as $key => $value ) {
            $columns[] = $key;
            $values[]  = '?';
            $binds[]   = $value;
        }
        $sql .= implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
        $resp = $this->query($sql, $binds, 'create');
        if ( $resp['success'] ) {
            $args[$this->primary_key] = $resp['success'];
            $this->args = $args;
        } else {
            return $resp;
        }
    }

    public function info() {
        $table = $this->table;
        $key   = $this->primary_key;
        $id    = $this->id;
        $memcache_key = "item_info:$table:$id";
        $info  = $this->cache->get($memcache_key);
        if (!$info) {
            $sql   = "SELECT * FROM $table WHERE $key = ?";
            $query = $this->query($sql, $id);
            if ( $query['error'] ) { return $query; }
            $info  = $query['success'][0];
            $this->cache->set($memcache_key, $info, time() + 86400);
        }
        return $info;
    }

    public function update($args) {
        if ( !$args ) {
            return [ 'error' => 'nothing to update', 'success' => null ];
        }
        $table         = $this->table;
        $primary_key   = $this->primary_key;
        $info          = $this->info();
        $primary_value = $info->$primary_key;
        $sql           = "UPDATE $table SET ";
        $values        = [];
        $binds         = [];
        foreach ( $args as $key => $value ) {
            $values[]  ="$key = ? ";
            $binds[]   = $value;
        }
        $sql .= implode(',', $values);
        $sql .= "WHERE $primary_key = $primary_value";
        $result = $this->query($sql, $binds);
        if ( $result['success'] ) {
            $this->clear_cache();
        }
        return $result;
    }
    
    public function delete() {
        $table         = $this->table;
        $primary_key   = $this->primary_key;
        $info          = $this->info();
        $primary_value = $info->$primary_key;
        if ( !$primary_value ) {
            return [ 'error' => 'nothing to delete', 'success' => null ];
        }
        $sql = "DELETE FROM $table WHERE $primary_key = $primary_value";
        $this->clear_cache();
        return $this->query($sql);
    }

    public function clear_cache() {
        $table = $this->table;
        $id    = $this->id;
        $this->cache->delete("item_info:$table:$id");
    }

}

?>
