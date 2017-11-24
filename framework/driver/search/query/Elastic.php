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

    public function index(...$params)
    {
        return $this->indexRaw(...$params)['created'] ?? false;
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
    
    public function indexRaw(...$params)
    {
        $count = count($params);
        if ($count === 1) {
            return $this->call('POST', null, $params[0]);
        } elseif ($count === 2) {
            if (is_array($params[0])) {
                return $this->call('POST', $params[0], $params[1]);
            } else {
                return $this->call('PUT', $params[0], null, $params[1]);
            }
        } elseif ($count === 3) {
            return $this->call('PUT', $params[0], $params[2], $params[1]);
        }
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
            $url .= "?".http_build_query($options);
        }
        $client = new Client($method, $url);
        if ($data) {
            $client->json($data);
        }
        if ($result = $client->response->json()) {
            return $result;
        }
        error($client->error);
    }
}
