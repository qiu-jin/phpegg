<?php
namespace framework\driver\search\query;

use framework\core\http\Client;

class Elastic
{
    protected $ns;
    protected $url;
    protected $type;
    
	public function __construct($url, $type, $name)
    {
        $this->url = $url;
        $this->type = $type;
        $this->ns[] = $name;
    }
    
    public function __get($name)
    {
        if (count($this->ns) === 1) {
            $this->ns[] = $name;
            return $this;
        }
        throw new \Exception('Ns error');
    }
    
    public function get($id)
    {
        return $this->getRaw($id)['_source'] ?? null;
    }
    
    public function search($query)
    {
        $result = $this->searchRaw($query);
        return isset($result['hits']['hits']) ? array_column($result['hits']['hits'], '_source') : null;
    }
    
    public function put($id, $data)
    {
        return $this->putRaw(...$params)['created'] ?? false;
    }
    
    public function index(...$params)
    {
        return $this->indexRaw(...$params)['created'] ?? false;
    }
    
    public function update($query, $data)
    {
        return $this->updateRaw($query, $data)['updated'] ?? false;
    }
    
    public function delete($query)
    {
        return $this->deleteRaw($query)['found'] ?? false;
    }
    
    public function getRaw($id)
    {
        return $this->call('GET', $id);
    }
    
    public function searchRaw($query)
    {
        if (is_array($query)) {
            return $this->call('POST', '_search', ['query' => $query]);
        } else {
            return $this->call('GET', '_search?q='.$query) ;
        }
    }
    
    public function putRaw($id, $data)
    {
        return $this->call('PUT', $id, $data);
    }
    
    public function indexRaw($data)
    {
        return $this->call('POST', null, $data);
    }
    
    public function updateRaw($query, $data)
    {
        if (is_array($query)) {
            $data['query'] = $query;
            return $this->call('POST', '_update_by_query', $data);
        }
        return $this->call('POST', "$query/_update", $data);
    }
    
    public function deleteRaw($query)
    {
        return is_array($query) ? $this->call('POST', '_delete_by_query', ['query' => $query]) : $this->send('DELETE', $query);
    }

    protected function call($method, $query = null, $data = null)
    {
        $url = "$this->url/$this->ns[0]/";
        $url .= count($this->ns) === 1 ? $this->type : $this->ns[1];
        if ($query) {
            $url .= "/$query";
        }
        $client = new Client($method, $url);
        if ($data) {
            $client->json($data);
        }
        return $client->json;
    }
}
