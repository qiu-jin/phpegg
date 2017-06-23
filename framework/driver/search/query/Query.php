<?php
namespace framework\driver\search\query;

abstract class Elastic
{
    protected $type;
    protected $index;
    protected $search;
    
	public function __construct($search, $index)
    {
        $this->index = $index;
        $this->search = $search;
    }
    
    public function __get($type)
    {
        $this->type = $type;
        return $this;
    }
    
    public function get($id)
    {
        $result = $this->search->send('GET', $id, null, $this->index, $this->type);
    }
    
    public function index($data)
    {
        return $this->send('PUT', $id, $data, $this->index, $this->type);

        return $this->send('POST', null, $data, $this->index, $this->type);
    }
    
    public function search($data)
    {
        $this->send('POST', '_search', $query, $this->index, $this->type);
    }
    
    public function delete($id)
    {
        $this->send('DELETE', $id, null, $this->index, $this->type);
    }
    
    public function clear()
    {
        
    }
}
