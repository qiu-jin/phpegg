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
    
    public function get($id, $options = null)
    {
        return $this->getRaw($id, $options)['_source'] ?? null;
    }
    
    public function search($query, $options = null)
    {
        $result = $this->searchRaw($query, $options);
        return isset($result['hits']['hits']) ? array_column($result['hits']['hits'], '_source') : null;
    }
    
    public function set($id, $data, $options = null)
    {
        return $this->setRaw($id, $data, $options)['created'] ?? false;
    }

    public function create($data, $options = null)
    {
        return $this->indexRaw($data, $options)['created'] ?? false;
    }
    
    public function update($query, $data, $options = null)
    {
        return $this->updateRaw($query, $data, $options)['updated'] ?? false;
    }
    
    public function delete($query, $options = null)
    {
        return $this->deleteRaw($query, $options)['found'] ?? false;
    }
    
    public function getRaw($id, $options = null)
    {
        return $this->call('GET', $id, $options);
    }
    
    public function searchRaw($query, $options = null)
    {
        if (is_array($query)) {
            $options['query'] = $query;
            return $this->call('POST', '_search', null, $options);
        } else {
            $options['q'] = $query;
            return $this->call('GET', '_search', $options) ;
        }
    }
    
    public function setRaw($id, $data, $options = null)
    {
        return $this->call('PUT', $id, $options, $data);
    }
    
    public function createRaw($data, $options = null)
    {
        return $this->call('POST', null, $options, $data);
    }
    
    public function updateRaw($query, $data, $options = null)
    {
        if (is_array($query)) {
            $data['query'] = $query;
            return $this->call('POST', '_update_by_query', $options, $data);
        }
        return $this->call('POST', "$query/_update", $options, $data);
    }
    
    public function deleteRaw($query, $options = null)
    {
        if (is_array($query)) {
            return $this->call('POST', '_delete_by_query', $options, ['query' => $query]);
        }
        return $this->call('DELETE', $query, $options);
    }

    protected function call($method, $path = null, array $query = null, array $data = null)
    {
        $url = $this->endpoint;
        if ($path) {
            $url .= "/$path";
        }
        if ($query) {
            $url .= "?".http_build_query($query);
        }
        $client = new Client($method, $url);
        if ($data) {
            $client->json($data);
        }
        $response = $client->response;
        if ($response->status >= 200 && $response->status < 300) {
            return $response->json();
        }
        error($client->error);
    }
}
