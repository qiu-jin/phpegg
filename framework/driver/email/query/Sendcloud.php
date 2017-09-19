<?php
namespace framework\driver\email\query;

class Sendcloud extends Query
{   
    public function template($template, $vars = null, $is_api_template = false)
    {
        if ($is_api_template) {
            if (isset($this->options['templates'][$template])) {
                $this->options['sendtemplate'] = true;
                if ($vars) {
                    foreach ($vars as $k => $v) {
                        $xsmtpapi['sub']['%'.$k.'%'] = array($v);
                    }
                    $this->options['options']['xsmtpapi'] = json_encode($xsmtpapi);
                }
                $this->options['options']['templateInvokeName'] = $this->options['templates'][$template];
                return $this;
            } else {
                return error('Template not exists');;
            }
        }
        return parent::template($template, $vars);
    }
}
