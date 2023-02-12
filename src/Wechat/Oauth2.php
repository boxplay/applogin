<?php
namespace boxplay\OAuthLogin\Wechat;

use boxplay\OAuthLogin\ApiException;
use boxplay\OAuthLogin\Base;
use boxplay\OAuthLogin\Wechat\Event;

class OAuth2 extends Base
{
    use Event;
    public $userInfo;
    public function __get($name)
    {
        if ($name === 'access_token') {
            return $this->__getAccessToken('');
        }
        if ($name === 'userInfo') {
            return 123;
        }
    }
    /**
     * api接口域名
     */
    const API_DOMAIN = 'https://api.weixin.qq.com/cgi-bin';
    const WECHAT_DOMAIN = 'https://mp.weixin.qq.com/cgi-bin';

    const TOKEN = 'devland';
    /**
     * 检验授权凭证AccessToken是否有效
     * @param string $accessToken
     * @return bool
     */
    public function validateAccessToken($accessToken = null)
    {
        try
        {
            $this->getUserInfo($accessToken);
            return true;
        } catch (ApiException $e) {
            return false;
        }
    }
    public function accept()
    {
        return $this->checkSignature();
    }
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = $this->wechatToken?$this->wechatToken:'devland';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    public function getAuthUrl($callbackUrl = null, $state = null, $scope = null)
    {
        return (empty($params) ? '' : ('?' . $this->http_build_query($params)));
    }
    /**
     * 第二步:处理回调并获取access_token。与getAccessToken不同的是会验证state值是否匹配，防止csrf攻击。
     * @param string $storeState 存储的正确的state
     * @param string $code 第一步里$callbackUrl地址中传过来的code，为null则通过get参数获取
     * @param string $state 回调接收到的state，为null则通过get参数获取
     * @return string
     */
    protected function __getAccessToken($storeState, $code = null, $state = null)
    {
        // $tokenData = file_put_contents(dirname(__FILE__) . '/1.txt', '12312');
        //检查是否存在token 的json文件
        $jsonData = '';
        if (file_exists(dirname(__FILE__) . '/token.json')) {
            $jsonData = file_get_contents(dirname(__FILE__) . '/token.json');
        }
        $tokenData = $jsonData ? json_decode($jsonData, true) : '';
        if (!$tokenData) {
            //没有json 文件
            //保存一份
            $json = $this->_getWechatToken();
            return $json['access_token'];
        }
        //存在json 文件
        //判断是否过期
        if ($tokenData['expires_in'] <= time()) {
            //过期状态
            //重新获取json
            $json = $this->_getWechatToken();
            return $json['access_token'];
        }
        return $this->accessToken = $tokenData['access_token'];

    }

    protected function _getWechatToken()
    {
        $this->result = $this->http->curlPost($this->getUrl('/token', [
            'grant_type' => 'client_credential',
            'appid' => $this->appid,
            'secret' => $this->appSecret,
        ]), []);
        if (isset($this->result['access_token'])) {
            $this->result['expires_in'] += time() - 600;
            $saveData = file_put_contents(dirname(__FILE__) . '/token.json', json_encode($this->result));
        }
        return $this->result;
    }

    /**
     * 获取url地址
     * @param string $name 跟在域名后的文本
     * @param array $params GET参数
     * @return string
     */
    public function getUrl($name, $params = array(), $domain = 'api')
    {
        $base = $domain === 'wechat' ? static::WECHAT_DOMAIN : static::API_DOMAIN;
        return $base . $name . (empty($params) ? '' : ('?' . $this->http_build_query($params)));
    }
    /**
     * 获取用户资料
     * @param string $accessToken
     * @return array
     */
    public function getUserInfo($openid = '', $scene_str = '')
    {
        $this->result = $this->http->curlGet($this->getUrl('/user/info', [
            'access_token' => $this->access_token,
            'openid' => $openid,
            'lang' => 'zh_CN',
        ]));
        if (!isset($this->result['errcode'])) {
            $this->userInfo = $this->result;
            $this->openid = $this->result['openid'];
        }
        $this->result['scene_str'] = $scene_str;
        return $this->result;
    }

    /**
     * 刷新AccessToken续期
     * @param string $refreshToken
     * @return bool
     */
    public function refreshToken($refreshToken)
    {
        // 不支持
        return false;
    }

    public function getTempQrcode($str = '')
    {
        $url = $this->getUrl('/qrcode/create', ['access_token' => $this->access_token]);
        $this->result = $this->http->curlPost($url, [
            'expire_seconds' => 60 * 60 * 24,
            'action_name' => 'QR_STR_SCENE',
            'action_info' => [
                'scene' => [
                    'scene_str' => $str,
                ],
            ],
        ]);
        if (isset($this->result['ticket'])) {
            //换取场景二维码
            return $this->getUrl('/showqrcode', [
                'ticket' => urlencode($this->result['ticket']),
            ], 'wechat');
        }
        return $this->result;
    }
    /** \
     * 处理xml数据
     */
    public function dealXmlData()
    {
        $raw = file_get_contents("php://input");
        $data = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA);
        file_put_contents(dirname(__FILE__) . '/raw.txt', $raw);
        $this->xmlData = json_decode(json_encode($data), true);
        return $this->xmlData;
    }

    public function dealEvent()
    {
        switch ($this->xmlData['MsgType']) {
            //扫码登录
            case 'SCAN':
                return $this->subscribe();
                break;
            //扫码登录
            case 'event':
                return $this->subscribe();
                break;
            default:
                return $this->welcome();
                break;
        }
        // return $this->welcome();
    }

    public function subscribe()
    {
        //获取用户信息
        $openid = $this->xmlData['FromUserName'];
        return $this->getUserInfo($openid, $this->xmlData['EventKey']);
        if (isset($this->xmlData['Event']) && $this->xmlData['Event'] === 'SCAN') {
            return $this->getUserInfo($openid, $this->xmlData['EventKey']);
        }

    }
}
