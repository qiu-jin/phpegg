<?php
namespace framework\driver\rpc\client;

use framework\util\Arr;
use framework\core\http\Client;

class GrpcHttp
{
	// 配置项
    protected $config = [
        /*
        // 服务端点（HTTP）
        'endpoint'
        // 公共headers（HTTP）
        'http_headers'
        // HTTP 请求编码
        'http_request_encode'	=> ['gzip' => 'gzencode'],
        // HTTP 响应解码
        'http_response_decode'	=> ['gzip' => 'gzdecode'],
        // service类名前缀
        'service_prefix'
        // schema定义文件加载规则
        'schema_loader_rules'
        */
        // CURL设置（HTTP）
        'http_curlopts' => [
        	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0
        ],
        // 请求参数协议类格式
        'http_request_message_format'	=> '{service}{method}Request',
        // 响应结构协议类格式
        'http_response_message_format'	=> '{service}{method}Response',
    ];
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->config = $config + $this->config;
    }
    
    /*
     * 发送请求
     */
    public function send($service, $method, $message)
    {
		if (isset($this->config['service_prefix'])) {
			$service = $this->config['service_prefix'].'\\'.$service;
		}
        $url    = $this->config['endpoint'].'/'.strtr($service, '\\', '.').'/'.$method;
		$client = Client::post($url);
        $data   = $this->makeRequestMessage($service, $method, $message)->serializeToString();
		if (isset($this->config['http_request_encode'])) {
			$encode_name = array_rand($this->config['http_request_encode']);
			$encode_function = $this->config['http_request_encode'][$encode_name];
			$client->header('grpc-encoding', $encode_name);
			$encode = 1;
			$data   = $encode_function($data);
		}
		if (isset($this->config['http_response_decode'])) {
			$client->header('grpc-accept-encoding', implode(', ', array_keys($this->config['http_response_decode'])));
		}
        if (isset($this->config['http_headers'])) {
            $client->headers($this->config['http_headers']);
        }
        if (isset($this->config['http_curlopts'])) {
            $client->curlopts($this->config['http_curlopts']);
        }
        $size = strlen($data);
        $client->body(pack('C1N1a'.$size, $encode ?? 0, $size, $data));
        $response = $client->response();
        if (isset($response->headers['grpc-status'])) {
            if ($response->headers['grpc-status'] === '0') {
                $result = unpack('Cencode/Nsize/a*data', $response->body);
                if ($result['size'] != strlen($result['data'])) {
                    error('Invalid input: size error');
                }
                if ($result['encode'] == 1) {
                    if (($decode_name = strtolower($response->headers['grpc-encoding'] ?? null))
                        && isset($this->config['http_response_decode'][$decode_name])
                    ) {
                        $result['data'] = ($this->config['http_response_decode'][$decode_name])($result['data']);
                    } else {
                    	error("Can't decode: $decode_name response");
                    }
                }
                $response_class = strtr($this->config['http_response_message_format'], [
                    '{service}' => $service,
                    '{method}'  => ucfirst($method)
                ]);
                $response_message = new $response_class;
                $response_message->mergeFromString($result['data']);
                return $response_message;
            }
            error("[{$response->headers['grpc-status']}]".$response->headers['grpc-message']);
        }
        error($client->error);
    }
	

    /*
     * 生成请求信息实例
     */
    protected function makeRequestMessage($service, $method, $message)
    {
		if (isset($this->config['http_request_message_format'])) {
            if (is_array($message)) {
	            $class = strtr($this->config['http_request_message_format'], [
	                '{service}' => $service,
	                '{method}'  => ucfirst($method)
	            ]);
	            $message = new $class;
	            $message->mergeFromJsonString(json_encode($message));
	            return $message;
            }
		} else {
            if (is_subclass_of($message, Message::class)) {
                return $message;
            }
		}
		throw new \Exception('Invalid params');
    }
}
