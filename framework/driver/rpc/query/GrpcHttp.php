<?php
namespace framework\driver\rpc\query;

use framework\core\http\Client;

/*
 * https://github.com/google/protobuf
 */
use Google\Protobuf\Internal\Message;

class GrpcHttp
{
    protected $ns;
    protected $rpc;
    protected $options;
    
    public function __construct($rpc, $ns, $options)
    {
        $this->ns = $ns;
        $this->rpc = $rpc;
        $this->options = $options;
    }
    
    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if (!$this->ns) {
            throw new \Exception('Service is empty');
        }
        $service = implode('\\', $this->ns);
        $replace = [
            '{service}' => $service,
            '{method}'  => ucfirst($method)
        ];
        $request_class  = strtr($this->options['request_scheme_format'], $replace);
        $request_object = $this->rpc->arrayToRequest($request_class, $params);
        $url    = $this->options['endpoint'].'/'.implode('.', $this->ns).'/'.$method;
        $data   = $request_object->serializeToString();
        $size   = strlen($data);
        $body   = pack('C1N1a'.$size, 0, $size, $data);
        $client = Client::post($url)->body($body);
        if (!empty($this->options['enable_http2'])) {
            $client->curlopt(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        }
        if (!empty($this->options['headers'])) {
            $client->headers($this->options['headers']);
        }
        if (!empty($this->options['curlopts'])) {
            $client->curlopts($this->options['curlopts']);
        }
        $response = $client->response;
        if (isset($response->headers['grpc-status'])) {
            if ($response->headers['grpc-status'] === '0') {
                $response_class = strtr($this->options['response_scheme_format'], [
                    '{service}' => $service,
                    '{method}'  => ucfirst($method)
                ]);
                $result = unpack('Cencode/Nzise/a*message', $response->body);
                if ($result['zise'] !== strlen($result['message'])) {
                    error('Invalid input');
                }
                $response_object = new $response_class;
                $response_object->mergeFromString($result['message']);
                if (empty($this->options['response_to_array'])) {
                    return $response_object;
                }
               return $this->rpc->responseToArray($response_object);
            }
            error("[{$response->headers['grpc-status']}]".$response->headers['grpc-message']);
        }
        error($client->error);
    }
}