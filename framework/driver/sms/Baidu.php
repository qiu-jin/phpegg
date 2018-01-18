<?php
namespace framework\driver\sms;

use framework\core\http\Client;

class Baidu extends Sms
{
    protected $region;
    protected $expiration;
    protected static $version = 'bce-auth-v1';
    protected static $endpoint = 'http://sms.%s.baidubce.com/bce/v2';
    
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->region = $config['region'] ?? 'bj';
        $this->expiration = $config['expiration'] ?? 180;
    }

    protected function handle($to, $template, $data)
    {
        $body = json_encode([
            'invoke'            => uniqid(),
            'phoneNumber'       => $to,
            'TemplateCode'      => $this->template[$template],
            'contentVar'        => $data
        ]);
        $url    = sprintf($this->endpoint, $this->region).'/message';
        $client = Client::post($url)->headers($this->buildHeaders($url, $body))->body($body);
        $result = $client->response->json();
        if (isset($result['code'])) {
            if ($result['code'] === '1000') {
                return true;
            }
            return error("[$result[code]]$result[message]");
        }
        return error($client->error);
    }
    
    protected function buildHeaders($url, $body)
    {
        $time = gmdate('Y-m-d\TH:i:s\Z');
        $parsed_url = parse_url($url);
        $headers = [
            'Host' => $parsed_url['host'],
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
        $signkey = hash_hmac('sha256', self::$version."/$this->acckey/$time/$this->expiration", $this->seckey);
        $signature = hash_hmac('sha256', "POST\n$parsed_url[path]\n\n".implode("\n", $canonicalheaders), $signkey);
        $shstr = implode(';', $signheaders);
        $sendheaders[] = "Authorization: ".self::$version."/$this->acckey/$time/$this->expiration/$shstr/$signature";
        return $sendheaders;
    }
}