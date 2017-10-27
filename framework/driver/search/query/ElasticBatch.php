<?php
namespace framework\driver\search\query;

use framework\core\http\Client;

class ElasticBatch
{
    protected $ns;
    protected $url;
    protected $type;
    protected $prev;
    protected $mget;
    protected $bulk;
    
    public function __construct($url, $type)
    {
        $this->url = $url;
        $this->type = $type;
    }
    
    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function get($id, $options = null)
    {
        $query = $this->getIndexType();
        $query['_id'] = $id;
        if (isset($options)) {
            $query = array_merge($options, $query);
        }
        $this->mget[] = $query;
        return $this;
    }
    
    public function index(...$params)
    {
        $query = $this->getIndexType();
        $count = count($params);
        if ($count === 1) {
            $data = $params[0];
        } elseif ($count === 2) {
            $query['_id'] = $params[0];
            $data = $params[1];
        } else {
            throw new \Exception('Params error');
        }
        $this->bulk[] = json_encode(['update' => $query]);
        $this->bulk[] = json_encode($data);
        return $this;
    }
    
    public function update($id, $data)
    {
        $query = $this->getIndexType();
        $query['_id'] = $id;
        $this->bulk[] = json_encode(['update' => $query]);
        $this->bulk[] = json_encode($data);
        return $this;
    }
    
    public function delete($id)
    {
        $query = $this->getIndexType();
        $query['_id'] = $id;
        $this->bulk[] = json_encode(['delete' => $query]);
        return $this;
    }
    
    protected function call($return_raw_result = false)
    {
        if (isset($this->bulk)) {
            if (isset($this->mget)) {
                throw new \Exception('No support mix get and other');
            }
            $method = '_bulk';
            $body = implode("\r\n", $this->bulk);
        } elseif (isset($this->mget)) {
            if (isset($this->bulk)) {
                throw new \Exception('No support mix get and other');
            }
            $method = '_mget';
            $body = json_encode(['docs' => $this->mget]);
        } else {
            throw new \Exception('No query');
        }
        $client = Client::post("$this->url/$method");
        $client->body($body);
        $result = $client->json;
        if ($return_raw_result) {
            return $result;
        }
        if ($method === '_bulk') {
            return $result['items'] ?? false;
        }
    }
    
    protected function getIndexType()
    {
        if (!isset($this->prev)) {
            $count = count($this->ns);
            if ($count === 1) {
                $this->prev = ['_index' => $this->ns[0], '_type' => $this->ns[1]];
            } elseif ($count === 2) {
                $this->prev = ['_index' => $this->ns[0], '_type' => $this->$type];
            } else {
                throw new \Exception('Ns error');
            }
            $this->ns = null;
        }
        return $this->prev;
    }
}
