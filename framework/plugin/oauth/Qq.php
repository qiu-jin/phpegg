<?php
namespace Plugin\Oauth;

class QQ
{
    static private $app_id = '';
    static private $app_secret = '';
    static private $redirect_uri = '';
    
    static public function request()
    {
        $param = array(
                'client_id'     => self::$app_id,
                'redirect_uri'  => self::$redirect_uri,
                'response_type' => 'code'
        );
        return 'https://graph.qq.com/oauth2.0/authorize?'.http_build_query($param);
    }

    static public function callback($ret)
    {
        if ($ret['code']) {
            $param = array(
                    'client_id'     => self::$app_id,
                    'client_secret' => self::$app_secret,
                    'code'          => $ret['code'],
                    'redirect_uri'  => self::$redirect_uri
            );
            $res = \Util\Http::request('https://graph.qq.com/oauth2.0/token?'.http_build_query($param));
            parse_str($res, $token_data);
            if ($token_data['access_token']) {
                $res = \Util\Http::request('https://graph.qq.com/oauth2.0/me?access_token='.$token_data['access_token']);
                if (strpos($res, 'callback') !== false) {
                   $lpos = strpos($res, '(');
                   $rpos = strrpos($res, ')');
                   $res = substr($res, $lpos+1, $rpos-$lpos-1);
                   $openid_data = json_decode($res, true);
                    if ($openid_data['openid']) {
                        $user_data = \Util\Http::request_json('https://graph.qq.com/user/get_user_info?', http_build_query(array(
                            'access_token'      => $token_data['access_token'],
                            'oauth_consumer_key'=> self::$app_id,
                            'openid'            => $openid_data['openid']
                        )));
                        if ($user_data['ret'] === 0) {
                            return array(
                                'access_token'  => $token_data['access_token'],
                                'openid'        => $openid_data['openid'],
                                'username'      => $user_data['nickname'],
                                'avatar'        => (isset($user_data['figureurl_qq_2']) ? $user_data['figureurl_qq_2'] : $user_data['figureurl_qq_1'])
                            );
                        }
                    }
                }
            }
        }
        return false;
    }
}
