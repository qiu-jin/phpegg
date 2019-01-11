<?php
namespace framework\driver\sms;

use framework\util\Str;
use framework\core\http\Client;

class Nexmo extends Sms
{
    protected $area_code;
    protected static $endpoint = 'https://rest.nexmo.com/sms/json';
    
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->area_code = $config['area_code'] ?? '86';
    }
    
    protected function handle($to, $template, $data, $signname = null)
    {
        $message = $this->template[$template];
        if ($data) {
            $message = Str::formatReplace($message, $data);
        }
        $client = Client::post(self::$endpoint)->json([
            'from'      => $signname ?? $this->signname,
            'text'      => $message,
            'to'        => is_array($to) ? "$to[0]$to[1]" : $this->area_code.$to,
            'api_key'   => $this->acckey,
            'api_secret'=> $this->seckey,
            'type'      => strlen($message) === mb_strlen($message) ? 'text' : 'unicode'
        ]);
        $result = $client->response()->json();
        if (isset($result['messages'][0])) {
            if ($result['messages'][0]['status'] === '0') {
                return true;
            }
            return warn('['.$result['messages'][0]['status'].'] '.$result['messages'][0]['error-text']);
        }
        return warn($client->error);
    }
}
