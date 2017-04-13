<?php
namespace framework\driver\search\query;

class Elastic
{
    protected $index;
    protected $search;
    protected $option = ['type' => 'default'];
    
    public function __construct($search, $index)
    {
        $this->index = $index;
        $this->search = $search;
    }
    
    public function type($type)
    {
        $this->option['type'] = $type;
        return $this;
    }
    
    public function select()
    {

    }
    
    public function where()
    {

    }
    
    public function get()
    {

    }
    
    public function find()
    {

    }
}