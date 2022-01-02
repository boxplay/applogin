<?php
namespace boxplay\OAuthLogin\Yuque;

use boxplay\OAuthLogin\ApiException;
use boxplay\OAuthLogin\Base;

class OAuth2 extends Base
{
/**
 * 授权接口域名
 */
    const AUTH_DOMAIN = 'https://www.yuque.com/oauth2/authorize';

    /**
     * api接口域名
     */
    const API_DOMAIN = 'https://www.yuque.com/api/v2/';

    /**
     * token 获取地址
     */
    const TOKEN_DOMAIN = 'https://www.yuque.com/oauth2/token';

    /**
     * 获取登录授权url地址
     * @param string $name 跟在域名后的文本
     * @param array $params GET参数
     * @return string
     */
    public function getAuthLoginUrl()
    {
        $params = [
            'client_id' => $this->appid,
            'scope' => 'repo,topic',
            'redirect_uri' => $this->callbackUrl,
            'state' => $this->state,
            'response_type' => 'code',
        ];
        return static::AUTH_DOMAIN . (empty($params) ? '' : ('?' . $this->http_build_query($params)));
    }

    public function getAuthUrl($callbackUrl = null, $state = null, $scope = null)
    {
        return static::AUTH_DOMAIN . (empty($params) ? '' : ('?' . $this->http_build_query($params)));
    }

    public function error()
    {
        throw new ApiException($this->result['error'], 0);
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
        $this->result = $this->http->curlPost(static::TOKEN_DOMAIN, array(
            'client_id' => $this->appid,
            'client_secret' => $this->appSecret,
            'code' => isset($code) ? $code : (isset($_GET['code']) ? $_GET['code'] : ''),
            'grant_type' => 'authorization_code',
        ));
        if (isset($this->result['error'])) {
            return $this->result;
        } else {
            return $this->accessToken = $this->result['access_token'];
        }
    }

    /**
     * 获取url地址
     * @param string $name 跟在域名后的文本
     * @param array $params GET参数
     * @return string
     */
    public function getUrl($name, $params = array())
    {
        return static::API_DOMAIN . $name . (empty($params) ? '' : ('?' . $this->http_build_query($params)));
    }
    /**
     * 获取用户资料
     * @param string $accessToken
     * @return array
     */
    public function getUserInfo($accessToken = null)
    {
        $this->result = $this->http->curlGet($this->getUrl('user'), array(
            'X-Auth-Token' => null === $accessToken ? $this->accessToken : $accessToken,
        ));
        if (isset($this->result['message'])) {
            throw new ApiException($this->result['message'], 0);
        } else {
            $this->openid = $this->result['data']['id'];
            return $this->result;
        }
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
}
