<?php
namespace framework\driver\sms;

use framework\core\http\Client;

class Nexmo extends Sms
{
    protected static $endpoint = 'https://rest.nexmo.com/sms/json';
    
    protected function handle($to, $template, $data, $signname = null)
    {
        $message = $this->template[$template];
        if ($data) {
            foreach ($data as $k => $v) {
                $replace['{'.$k.'}'] = $v;
            }
            $message = strtr($message, $replace);
        }
        $client = Client::post(self::$endpoint)->json([
            'from'      => $signname ?? $this->signname,
            'text'      => $message,
            'to'        => is_array($to) ? "$to[0]$to[1]" : "86$to",
            'api_key'   => $this->acckey,
            'api_secret'=> $this->seckey,
            'type'      => strlen($message) === mb_strlen($message) ? 'text' : 'unicode'
        ]);
        $result = $client->response->json();
        if (isset($result['messages'][0])) {
            if ($result['messages'][0]['status'] === '0') {
                return true;
            }
            return warning('['.$result['messages'][0]['status'].'] '.$result['messages'][0]['error-text']);
        }
        return warning($client->error);
    }
}
