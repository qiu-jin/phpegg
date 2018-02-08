<?php
namespace framework\driver\search\query;

use framework\core\http\Client;

class Elastic
{
    protected $raw;
    protected $endpoint;
    
	public function __construct($url, $index, $type)
    {
        $this->endpoint = "$url/$index/$type";
    }
    
    public function get($id, $options = null)
    {
        $result = $this->call('GET', $id, $options);
        return $this->raw ? $result : ($result['_source'] ?? null);
    }
    
    public function find($query, $options = null)
    {
        if (is_array($query)) {
            $this->options['query'] = $query;
            $result = $this->call('POST', '_search', null, $options);
        } else {
            $this->options['q'] = $query;
            $result = $this->call('GET', '_search', $options) ;
        }
        if ($this->raw) return $result;
        return isset($result['hits']['hits']) ? array_column($result['hits']['hits'], '_source') : null;
    }
    
    public function set($id, $data, $options = null)
    {
        $result = $this->call('PUT', $id, $options, $data);
        return $this->raw ? $result : ($result['created'] ?? false);
    }

    public function create($data, $options = null)
    {
        $result = $this->call('POST', null, $options, $data);
        return $this->raw ? $result : ($result['created'] ?? false);
    }
    
    public function update($query, $data, $options = null)
    {
        if (is_array($query)) {
            $data['query'] = $query;
            $result = $this->call('POST', '_update_by_query', $options, $data);
        } else {
            $result = $this->call('POST', "$query/_update", $options, $data);
        }
        return $this->raw ? $result : ($result['updated'] ?? false);
    }
    
    public function delete($query, $options = null)
    {
        if (!is_array($query)) {
            $result = $this->call('DELETE', $query, $options);
        } else {
            $result = $this->call('POST', '_delete_by_query', $options, ['query' => $this->where]);
        }
        return $this->raw ? $result : ($result['found'] ?? false);
    }
    
    public function raw($bool = true)
    {
        $this->raw = (bool) $bool;
        return $this;
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
