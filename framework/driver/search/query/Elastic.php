<?php
namespace framework\driver\search\query;

use framework\core\http\Client;

class Elastic
{
    protected $url;
    
	public function __construct($url)
    {
        $this->url = $url;
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
    
    public function getMulti(...$params)
    {
        return $this->getMultiRaw(...$params)['_source'] ?? null;
    }
    
    public function getRaw($id)
    {
        return $this->send('GET', "/$id");
    }
    
    public function searchRaw($query)
    {
        return is_array($query) ? $this->send('POST', '/_search', ['query' => $query]) : $this->send('GET', '/_search?q='.$query) ;
    }
    
    public function indexRaw(...$params)
    {
        $count = count($params);
        if ($count === 1) {
            return $this->send('POST', null, $params[0]);
        } elseif ($count === 2) {
            return $this->send('PUT', '/'.$params[0], $params[1]);
        }
    }
    
    public function updateRaw($query, $data)
    {
        if (is_array($query)) {
            $data['query'] = $query;
            return $this->send('POST', '/_update_by_query', $data);
        }
        return $this->send('POST', "/$find/_update", $data);
    }
    
    public function getMultiRaw(...$params)
    {
        return $this->send('GET', "/_mget", count($params) > 1 ? ['ids' => $params] : $params[0]);
    }
    
    public function deleteRaw($query)
    {
        return is_array($query) ? $this->send('POST', '/_delete_by_query', ['query' => $query]) : $this->send('DELETE', "/$query");
    }

    public function send($method, $query = null, $data = null)
    {
        $client = new Client($method, "$this->url/$query");
        if ($data) {
            $client->json($data);
        }
        return $client->json;
    }
}
