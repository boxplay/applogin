<?php
namespace boxplay\OAuthLogin\Util;

use GuzzleHttp\Client;

class HttpRequest
{
    public $client;

    protected function createHttp()
    {
        if (!$this->client instanceof Client) {
            $this->client = new Client();
        }
        return $this->client;
    }
    /**
     * post 请求
     * @param string url 请求地址
     * @param array or object data 请求参数
     * @return array or null or object
     */
    public function curlPost($url, $data)
    {
        if (!$url) {
            return $this->error('连接格式不正确', ErrorCode::INVALID_DATA);
        }
        $resp = $this->HttpRequest('post', $url, $data);
        return \GuzzleHttp\json_decode($resp->getBody()->__toString(), true);
    }

    public function request_by_curl($method = 'get', $remote_server, $post_string = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
        // curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * @params url 请求地址
     * @return array or object or null
     */
    public function curlGet($url)
    {
        if (!$url) {
            return $this->error('连接格式不正确', ErrorCode::INVALID_DATA);
        }
        $resp = $this->HttpRequest('get', $url);
        return \GuzzleHttp\json_decode($resp->getBody()->__toString(), true);
    }

    /**
     * @param string $method 请求方式
     * @param string $url 请求地址
     * @param array or object or null $data 请求参数
     * @return array or object o rnull
     */
    protected function HttpRequest($method, $url, $data = [])
    {
        try {
            return $this->createHttp()->request(
                $method,
                $url,
                [
                    'json' => $data,
                    'headers' => [
                        'Content_type' => 'application/json; charset=utf-8',
                    ],
                ]

            );
        } catch (Exception $error) {
            return 'error';
        }
    }
}
