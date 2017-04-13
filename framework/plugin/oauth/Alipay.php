<?php
namespace Plugin\Oauth;

class Alipay
{
    static private  $pid = '';
    static private  $key = '';
    static private  $return_url = '';

    static public function request()
    {
        $param = array(
                '_input_charset'    => 'utf-8',
                'anti_phishing_key' => time(),
                'exter_invoke_ip'   => $_SERVER['REMOTE_ADDR'],
                'partner'           => self::$pid,
                'return_url'        => self::$return_url,
                'service'           => 'alipay.auth.authorize',
                'target_service'    => 'user.auth.quick.login'
        );
        $param['sign'] = self::sign($param);
        $param['sign_type'] = 'MD5';
        return 'https://mapi.alipay.com/gateway.do?'.http_build_query($param);
    }

    static public function callback($ret)
    {
        if ($ret['sign'] && $ret['notify_id'] && $ret['sign'] === self::sign($ret)) {
            $res = \Util\Http::request('http://notify.alipay.com/trade/notify_query.do?partner='.self::$pid.'&notify_id='.$ret['notify_id']);
            if ($res && preg_match('/true$/i', $res)) return $ret;
        }
        return false;
    }
    
    static private function sign($data)
    {
        foreach ($data as $k => $v) {
            if ($k == 'sign' || $k == 'sign_type' || empty($v)) unset($data[$k]);
        }
        ksort($data);
        reset($data);
        return md5(urldecode(http_build_query($data)).self::$key);
    }
}

