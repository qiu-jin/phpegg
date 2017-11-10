<?php
namespace framework\driver\email;

use framework\core\http\Client;

class Sendcloud extends Email
{
    protected $acckey;
    protected $seckey;
    protected $templates;
    protected static $host = 'http://api.sendcloud.net/apiv2/mail';
    
    protected function init($config)
    {
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        if (isset($config['templates'])) {
            $this->templates  = $config['templates'];
        }
    }
    
    public function __call($method, $params)
    {
        return (new query\Sendcloud($this, [
            'from'      => $this->from,
            'templates' => $this->templates
        ]))->$method(...$params);
    }
    
    public function handle($options)
    {
        $from = [
            'apiUser'   => $this->acckey,
            'apiKey'    => $this->seckey,
            'from'      => $options['from'][0],
            'to'        => implode(';', array_column($options['to'], 0))
        ];
        if (isset($options['from'][1])) {
            $from['fromName'] = $options['from'][1];
        }
        if (isset($options['cc'])) {
            $from['cc'] = implode(';', array_column($options['cc'], 0));
        }
        if (isset($options['bcc'])) {
            $from['bcc'] = implode(';', array_column($options['bcc'], 0));
        }
        if (isset($options['sendtemplate'])) {
            $method = 'sendtemplate';
        } else {
            $method = 'send';
            $form['subject'] = $options['subject'];
            if (empty($options['ishtml'])) {
                $form['plain'] = $options['content'];
            } else {
                $form['html'] = $options['content'];
            }
        }
        if (isset($options['options'])) {
            $from = array_merge($options['options'], $from);
        }
        $client = Client::post(self::$host."/$method")->form($form);
        if (isset($options['attach'])) {
            if (empty($options['attach_is_buffer'])) {
                $client->file('attachments', ...end($options['attach']));
            } else {
                $client->buffer('attachments', ...end($options['attach']));
            }
        }
        $result = $client->response->json();
        if (empty($result['result'])) {
            return error($result['message'] ?? $client->error);
        }
        return true;
    }
}
