<?php
namespace framework\driver\email;

use framework\core\http\Client;

class Mailgun extends Email
{
    private $apikey;
    private $domain;
    private $apiurl = 'https://api.mailgun.net/v3/';
    
    protected function init($config)
    {

    }
    
    public function raw($name, $value)
    {
        $this->option['raw'][$name] = $value;
        return $this;
    }
    
    public function send($to, $subject, $content)
    {
        $form = $this->buildFrom($to, $subject, $content);
        if ($form) {
            $client = Client::post($this->baseurl)->header('Authorization', 'api: '.$this->apikey)->form($form);
            $result = $client->json;
            if (isset($result['id'])) {
                return true;
            }
            if (isset($result['message'])) {
                $this->log = $result['message'];
            } else {
                $clierr = $client->error;
                $this->log = $clierr ? "$clierr[0]: $clierr[1]" : 'unknown error';
            }
        }
        return false;
    }
    
    protected function buildFrom($to, $subject, $content)
    {
        $this->option['to'][] = (array) $to;
        $form = [
            'subject'   => $subject,
            'to'        => $this->buildaddrs($this->option['to']),
            'from'      => $this->buildaddr($this->option['from'])
        ];
        if (isset($this->option['cc'])) {
            $from['cc'] = $this->buildaddrs($this->option['cc'])
        }
        if (isset($this->option['bcc'])) {
            $from['bcc'] = $this->buildaddrs($this->option['bcc'])
        }
        if (isset($this->option['raw'])) {
            $from = array_merge($this->option['raw'], $from);
        }
        if (empty($this->option['ishtml'])) {
            $from['test'] = $content;
        } else {
            $from['html'] = $content;
        }
        
        return $from;
    }
    
    protected function buildaddr(array $addr)
    {
        return isset($addr[1]) ? "$addr[0]<$addr[1]>" : $addr[0];
    }
    
    protected function buildaddrs(array $addrs)
    {
        foreach ($addrs as $addr) {
            $arr[] = $this->buildaddr($addr);
        }
        return implode(',', $arr);
    }
}