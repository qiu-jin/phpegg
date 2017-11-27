<?php
namespace framework\driver\search\query;

use framework\core\http\Client;

class ElasticBatch
{
    protected $mget;
    protected $bulk;
    protected $type;
    protected $index;
    protected $endpoint;
    protected $common_index;
    
    public function __construct($endpoint, $index, $type)
    {
        $this->type = $type;
        $this->endpoint = $endpoint;
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
    
    public function get($id, $options = [])
    {
        $this->mget[] = array_merge($options, $this->getQuery($id));
        return $this;
    }
    
    public function set($id, $data)
    {
        $this->bulk[] = json_encode(['index' => $this->getQuery($id)]);
        $this->bulk[] = json_encode($data);
        return $this;
    }
    
    public function create($data)
    {
        $this->bulk[] = json_encode(['index' => $this->getQuery()]);
        $this->bulk[] = json_encode($data);
        return $this;
    }
    
    public function update($id, $data)
    {
        $this->bulk[] = json_encode(['update' => $this->getQuery($id)]);
        $this->bulk[] = json_encode($data);
        return $this;
    }
    
    public function delete($id)
    {
        $this->bulk[] = json_encode(['delete' => $this->getQuery($id)]);
        return $this;
    }
    
    public function call()
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
            throw new \Exception('Query is empty');
        }
        $client = Client::post("$this->endpoint/$method")->body($body);
        $response = $client->response;
        if ($response->status >= 200 && $response->status < 300) {
            return $response->json();
        }
        error($client->error);
    }
    
    protected function getQuery($id = null)
    {
        if ($this->index) {
            $index = $this->index;
            $this->index = null;
            return $query = ['_index' => $index, '_type' => $this->type];
        } elseif ($this->common_index) {
            return $query = ['_index' => $this->common_index, '_type' => $this->type];
        } else {
            throw new \Exception('Index is empty');
        }
        if (isset($id)) {
            $query['_id'] = $id;
        }
        return $query;
    }
}
