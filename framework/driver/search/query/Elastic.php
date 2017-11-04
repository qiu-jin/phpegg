<?php
namespace framework\driver\search\query;

use framework\core\http\Client;

class Elastic
{
    protected $endpoint;
    
	public function __construct($url, $index, $type)
    {
        $this->endpoint = "$url/$index/$type";
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
    
    public function indexRaw(...$params)
    {
        $count = count($params);
        if ($count === 1) {
            return $this->call('POST', null, $params[0]);
        } elseif ($count === 2) {
            return $this->call('PUT', $params[0], $params[1]);
        }
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
        return is_array($query) ? $this->call('POST', '_delete_by_query', ['query' => $query]) : $this->call('DELETE', $query);
    }

    protected function call($method, $query = null, $data = null)
    {
        $url = $this->endpoint;
        if ($query) {
            $url .= "/$query";
        }
        $client = new Client($method, $url);
        if ($data) {
            $client->json($data);
        }
        return $client->getJson();
    }
}
