<?php
namespace framework\driver\email;

abstract class Email
{
    protected $from;
    
    abstract public function handle($options);
    
    public function __construct($config)
    {
        $this->init($config);
        isset($config['from']) && $this->from = $config['from'];
    }
    
    public function __call($method, $params)
    {
        return (new query\Query($this, ['from' => $this->from]))->$method(...$params);
    }
    
    public function send($to, $subject, $content)
    {
        return $this->handle([
            'to'        => [[$to]],
            'from'      => $this->from,
            'subject'   => $subject,
            'content'   => $content
        ]);
    }
}
