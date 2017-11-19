<?php
namespace framework\driver\sms;

use framework\core\http\Client;

class Baidu extends Sms
{
    protected $version = 'bce-auth-v1';
    protected $endpoint = 'http://sms.bj.baidubce.com';
    protected $expiration = 180;
    
    public function __construct(array $config)
    {
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        $this->template = $config['template'];
        isset($config['host']) && $this->host = $config['host'];
        isset($config['expiration']) && $this->expiration = $config['expiration'];
    }

    protected function handle($to, $template, $data)
    {
        $path = '/bce/v2/message';
        $body = json_encode([
            'invoke'            => uniqid(),
            'phoneNumber'       => $to,
            'TemplateCode'      => $this->template[$template],
            'contentVar'        => $data
        ]);
        $client = Client::post("$endpoint$path")->headers($this->buildHeaders($path, $body))->body($body);
        $result = $client->response->json();
        if (isset($result['code']) && $result['code'] === '1000') {
            return true;
        }
        return error($result['message'] ?? $client->error);
    }
    
    protected function buildHeaders($path, $body)
    {
        $time = gmdate('Y-m-d\TH:i:s\Z');
        $headers = [
            'Host' => $this->host,
            'Content-Type' => 'application/json',
            'Content-Length' => strlen($body),
            'x-bce-date' => $time,
            'x-bce-content-sha256' => hash('sha256', $body)
        ];
        ksort($headers);
        foreach ($headers as $k => $v) {
            $sendheaders[] = "$k: $v";
            $k = strtolower($k);
            $signheaders[] = $k;
            $canonicalheaders[] = "$k:".rawurlencode(trim($v));
        }
        $signkey = hash_hmac('sha256', "$this->version/$this->acckey/$time/$this->expiration", $this->seckey);
        $signature = hash_hmac('sha256', "POST\n$path\n\n".implode("\n", $canonicalheaders), $signkey);
        $sendheaders[] = "Authorization: $this->version/$this->acckey/$time/$this->expiration/".implode(';', $signheaders)."/$signature";
        return $sendheaders;
    }
}