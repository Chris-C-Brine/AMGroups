<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait AMGroups {

    /**
     *  Overrides Accessor function processing to include $custom_groups keys as group_name
     *  and runs the coorisponding getGroupNameAccessor function on the values
     *
     *  @return string
     */
    public function getAttributeValue($key) {
        
        // If the value has already been mutated, don't alter it, 
        $value = parent::getAttributeValue($key);
        
        // But if the key is in the array of our custom_groups, mutate and return it
        $custom_groups = !empty($this->custom_groups) ? $this->custom_groups : [];
        foreach ($custom_groups as $mutator => $arr) {
            
            $method = 'get'.Str::studly($mutator).'Accessor';
            if ( is_array($arr) && method_exists($this, $method) ) {
                if($value === $this->getAttributeFromArray($key) && in_array($key, $arr) ) {
                    $value = $this->{$method}($value);
                }
            }
        }
        
        return $value;
    }
    
    /**
     *  Overrides Mutator function processing to include $custom_groups keys as group_name
     *  and runs the coorisponding setGroupNameMutator function on the values
     *
     *  @return array
     */
    public function setAttribute($key, $value) {
        
        $custom_groups = !empty($this->custom_groups) ? $this->custom_groups : [];
        foreach ($custom_groups as $mutator => $arr) {
                
            $method = 'set'.Str::studly($mutator).'Mutator';
            if ( is_array($arr) && method_exists($this, $method) && in_array($key, $arr)) {
                return $this->{$method}($key, $value);
            }
        }
        
       return parent::setAttribute($key, $value);
    }
    
    /**
     *  Returns a full list of table columns
     *
     *  @return array
     */
    public static function tableColumns($with_pk = FALSE) {
        
        $static = with(new static);
        
        // Get the table name
        $table_name = $static->getTable();
        
        // Remember table columns forever if it isn't remembered
        $cache_name = 'relation_schema-'.$table_name;
        
        if (!Cache::has($cache_name)) {
            $tableColumns = Schema::getColumnListing($table_name);
            sort($tableColumns);
            Cache::forever($cache_name, $tableColumns);
        } else {
            $tableColumns = Cache::get($cache_name);
        }
        
        // debug
        if (!is_array($tableColumns)) {
            Cache::put('test', $tableColumns, 600);
            return redirect('setup/var_dump');
        }
        
        // return table columns with or without primary key (default to return without PK)
        return $with_pk ? $tableColumns : array_diff($tableColumns, [$static->getKeyName()]);
        
    }
    
    /**
     *  Flushes the table column lists from cache
     *
     *  @return null
     */
    public static function tableReset() {
        $table_name = with(new static)->getTable();
        Cache::forget('relation_schema-'.$table_name);
    }
    
    /**
     *  Returns result of a method on a new object as a static result
     *
     *  @return mixed
     */
    public static function Static($function){
        return with(new static)->$function();
    }
}