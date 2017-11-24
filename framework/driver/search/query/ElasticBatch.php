<?php
namespace framework\driver\search\query;

use framework\core\http\Client;

class ElasticBatch
{
    protected $url;
    protected $type;
    protected $mget;
    protected $bulk;
    protected $index;
    protected $common_index;
    
    public function __construct($url, $index, $type)
    {
        $this->url = $url;
        $this->type = $type;
        $this->common_index = $index;
    }
    
    public function __get($name)
    {
        if (!isset($this->index)) {
            $this->index = $name;
            return $this;
        }
        throw new \Exception('Ns error');
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
        $this->bulk[] = json_encode(['index' => $query]);
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
    
    public function call($return_raw_result = false)
    {
        if (isset($this->bulk)) {
            if (isset($this->mget)) {
                throw new \Exception('No support mix get and other');
            }
            $method = '_bulk';
            $body = implode("\n", $this->bulk)."\n";
        } elseif (isset($this->mget)) {
            if (isset($this->bulk)) {
                throw new \Exception('No support mix get and other');
            }
            $method = '_mget';
            $body = json_encode(['docs' => $this->mget]);
        } else {
            throw new \Exception('No query');
        }
        $client = Client::post("$this->url/$method")->body($body);
        if ($result = $client->response->json()) {
            if ($return_raw_result) {
                return $result;
            }
            if ($method === '_bulk') {
                return $result['items'] ?? false;
            }
        }
        error($client->error);
    }
    
    protected function getIndexType()
    {
        if ($this->index) {
            $index = $this->index;
            $this->index = null;
            return ['_index' => $index, '_type' => $this->type];
        } elseif ($this->common_index) {
            return ['_index' => $this->common_index, '_type' => $this->type];
        }
        throw new \Exception('Ns error');
    }
}
