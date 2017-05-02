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

    public function handle()
    {
        $form = $this->buildFrom();
        if ($form) {
            $client = Client::post($this->baseurl)->header('Authorization', 'api: '.$this->apikey)->form($form, $this->option['attach_is_buffer']);
            if (isset($this->option['attach'])) {
                foreach ($this->option['attach'] as $attach) {
                    $client->file('attachment[]', ...$attach);
                }
            }
            $result = $client->getJson();
            if (isset($result['id'])) {
                return true;
            }
            if (isset($result['message'])) {
                $this->log = $result['message'];
            } else {
                $clierr = $client->getError();
                $this->log = $clierr ? "$clierr[0]: $clierr[1]" : 'unknown error';
            }
        }
        return false;
    }
    
    protected function buildFrom()
    {
        $form = [
            'subject'   => $this->option['subject'],
            'to'        => $this->buildaddrs($this->option['to']),
            'from'      => $this->buildaddr($this->option['from'])
        ];
        if (isset($this->option['cc'])) {
            $from['cc'] = $this->buildaddrs($this->option['cc'])
        }
        if (isset($this->option['bcc'])) {
            $from['bcc'] = $this->buildaddrs($this->option['bcc'])
        }
        if (isset($this->option['option'])) {
            $from = array_merge($this->option['option'], $from);
        }
        if (empty($this->option['ishtml'])) {
            $from['test'] = $this->option['content'];
        } else {
            $from['html'] = $this->option['content'];
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