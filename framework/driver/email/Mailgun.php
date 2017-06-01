<?php
namespace framework\driver\email;

use framework\core\http\Client;

class Mailgun extends Email
{
    protected $acckey;
    protected $domain;
    protected static $host = 'https://api.mailgun.net/v3/';
    
    protected function init($config)
    {
        $this->domain = $config['domain'];
        $this->acckey = $config['acckey'];
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
        $client = Client::post(self::$host.$this->domain.'/messages')
                        ->header('Authorization', 'Basic '.base64_encode('api:'.$this->acckey))
                        ->form($form, $this->option['attach_is_buffer']);
        if (isset($this->option['attach'])) {
            $client->file('attachment', ...end($this->option['attach']));
        }
        $result = $client->json;
        if (empty($result['id'])) {
            return error(isset($result['message']) ? $result['message'] : $client->error);
        }
        return true;
    }
    
    protected function buildAddr(array $addr)
    {
        return isset($addr[1]) ? "$addr[1] <$addr[0]>" : $addr[0];
    }
    
    protected function buildAddrs(array $addrs)
    {
        foreach ($addrs as $addr) {
            $arrs[] = $this->buildAddr($addr);
        }
        return implode(',', $arrs);
    }
}
