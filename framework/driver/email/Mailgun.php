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
        $this->domain = $config['domain'];
        $this->apikey = $config['apikey'];
    }

    protected function handle()
    {
        $form = [
            'subject'   => $this->option['subject'],
            'to'        => $this->buildAddrs($this->option['to']),
            'from'      => $this->buildAddr($this->option['from'])
        ];
        if (isset($this->option['cc'])) {
            $form['cc'] = $this->buildAddrs($this->option['cc']);
        }
        if (isset($this->option['bcc'])) {
            $form['bcc'] = $this->buildAddrs($this->option['bcc']);
        }
        if (empty($this->option['ishtml'])) {
            $form['text'] = $this->option['content'];
        } else {
            $form['html'] = $this->option['content'];
        }
        if (isset($this->option['option'])) {
            $form = array_merge($this->option['option'], $from);
        }
        $client = Client::post($this->apiurl.$this->domain.'/messages')
                        ->header('Authorization', 'Basic '.base64_encode('api:'.$this->apikey))
                        ->form($form, $this->option['attach_is_buffer']);
        if (isset($this->option['attach'])) {
            $client->file('attachment', ...end($this->option['attach']));
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
        return false;
    }
    
    protected function buildAddr(array $addr)
    {
        return isset($addr[1]) ? "$addr[1] <$addr[0]>" : $addr[0];
    }
    
    protected function buildAddrs(array $addrs)
    {
        foreach ($addrs as $addr) {
            $arr[] = $this->buildAddr($addr);
        }
        return implode(',', $arr);
    }
}
