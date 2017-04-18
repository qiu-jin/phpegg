<?php
namespace framework\extend\db;

use framework\core\Model;

class Table extends Chain
{
    protected $_db;
    protected $_pk = 'id';
    protected $_table;
    protected $_fields = [];
    
    public function __construct($table = null, $db = null)
    {
        $this->_db = Model::connect($db);
        if ($table) {
            $this->_table = $table;
        }
    }
    
    public static function get()
    {
        
    }
    
    public static function with()
    {
        
    }
    
    public function find()
    {
        
    }
    
    public function save()
    {
        
    }
    
    public function delete()
    {
        
    }
}
