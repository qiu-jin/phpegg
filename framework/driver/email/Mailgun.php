<?php
namespace framework\driver\email;

use framework\core\http\Client;

class Mailgun extends Email
{
    protected $acckey;
    protected $domain;
    protected static $host = 'https://api.mailgun.net/v3';
    
    protected function init($config)
    {
        $this->domain = $config['domain'];
        $this->acckey = $config['acckey'];
    }

    public function handle($options)
    {
        $form = [
            'subject'   => $options['subject'],
            'to'        => $this->buildAddrs($options['to']),
            'from'      => $this->buildAddr($options['from'])
        ];
        if (isset($options['cc'])) {
            $form['cc'] = $this->buildAddrs($options['cc']);
        }
        if (isset($options['bcc'])) {
            $form['bcc'] = $this->buildAddrs($options['bcc']);
        }
        if (empty($options['ishtml'])) {
            $form['text'] = $options['content'];
        } else {
            $form['html'] = $options['content'];
        }
        if (isset($options['option'])) {
            $form = array_merge($options['option'], $from);
        }
        $client = Client::post(self::$host."/$this->domain/messages")
                        ->header('Authorization', 'Basic '.base64_encode('api:'.$this->acckey))
                        ->form($form);
        if (isset($options['attach'])) {
            if (empty($options['attach_is_buffer'])) {
                $client->file('attachments', ...end($options['attach']));
            } else {
                $client->buffer('attachments', ...end($options['attach']));
            }
        }
        $result = $client->getJson();
        if (empty($result['id'])) {
            return error($result['message'] ?? $client->getErrorInfo());
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
