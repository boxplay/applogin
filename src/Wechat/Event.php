<?php
namespace boxplay\OAuthLogin\Wechat;

trait Event
{
    public function welcome($welcome)
    {
        return $this->toXml($welcome);
    }

    private function toXml($welcome = '欢迎关注公众号')
    {
        $str = "<xml>
          <ToUserName><![CDATA[%s]]></ToUserName>
          <FromUserName><![CDATA[%s]]></FromUserName>
          <CreateTime>%s</CreateTime>
          <MsgType><![CDATA[text]]></MsgType>
          <Content><![CDATA[%s]]></Content>
        </xml>";
        return sprintf($str, $this->xmlData['FromUserName'], $this->xmlData['ToUserName'], time(), $welcome);
    }
}
