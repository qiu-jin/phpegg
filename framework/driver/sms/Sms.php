<?php
namespace framework\driver\sms;

abstract class Sms
{
    protected $acckey;
    protected $seckey;
    protected $signname;
    protected $template;
    
    abstract public function send($to, $template, $data);
    
    public function __construct(array $config)
    {
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        $this->signname = $config['signname'];
        $this->template = $config['template'];
    }
}
