<?php
namespace framework\driver\rpc\query;

use framework\core\Loader;
use framework\core\http\Client;

/*
 * https://github.com/google/protobuf
 */
use Google\Protobuf\Internal\Message;

class GrpcSimple
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
        $request_class = strtr($this->options['request_scheme_format'], $replace);
        $i = 0;
        $request_object = new $request_class;
        foreach (get_class_methods($request_class) as $m) {
            if (strpos($m, 'set') === 0) {
                if (!isset($params[$i])) {
                    break;
                }
                $request_object->$m($params[$i]);
                $i++;
            }
        }
        $url    = $this->options['endpoint'].'/'.implode('.', $this->ns).'/'.$method;
        $size   = $request_object->byteSize();
        $body   = pack('C1N1a'.$size, 0, $size, $request_object->serializeToString());
        $client = Client::post($url)->body($body);
        if (!empty($this->options['simple_mode_enable_http2'])) {
            $client->curlopt(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        }
        if (!empty($this->options['simple_mode_headers'])) {
            $client->headers($this->options['simple_mode_headers']);
        }
        if (!empty($this->options['simple_mode_curlopts'])) {
            $client->curlopts($this->options['simple_mode_curlopts']);
        }
        $response = $client->response;
        if (isset($response->headers['grpc-status'])) {
            if ($response->headers['grpc-status'] === '0') {
                $esponse_class = strtr($this->options['response_scheme_format'], [
                    '{service}' => $service,
                    '{method}'  => ucfirst($method)
                ]);
                $result = unpack('Cencode/Nzise/a*message', $response->body);
                if ($result['zise'] !== strlen($result['message'])) {
                    error('Invalid input');
                }
                $esponse_object = new $esponse_class;
                $esponse_object->mergeFromString($result['message']);
                return $esponse_object;
            }
            error("[$response->headers[grpc-status]]$response->headers[grpc-message]");
        }
        error($client->error);
    }
}