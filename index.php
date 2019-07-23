<?php

header('Content-Type: text');

require 'wechat.base.api.php';

// 目前: 测试账号对应开发者ID和开发者Secrect
define('APP_ID', 'wxadfce85759f4629d');
define('APP_SECRECT', 'e03b11b28d406a42b04f60920ab72c7a');

// 1.实例化对象; Obj是object公认缩写
$wechatObj = new WechatAPI();
// 2.对象调用方法; msg是message公认缩写
if (isset($_GET['echostr'])) {
    // 需要验证/校验
    $wechatObj->validMsg();
} else {
    // 3.调用方法: 接收用户消息, 返回消息
    $wechatObj->reponseMsg();
}

class WechatAPI
{
    /**
     * 验证消息的确来自于微信服务器
     *
     * @return String
     */
    public function validMsg()
    {
        if ($this->isCheckSignature()) {
            // 返回echostr参数值
            echo $_GET['echostr'];
            exit;
        }
    }

    /**
     * 生成加密字符串, 并和signature参数值判断
     *
     * @return Bool
     */
    private function isCheckSignature()
    {
        // 1.读取token, timestamp + nonce
        $token = 'weixin';
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        $signature = $_GET['signature'];

        // 2.字典序排序; tmp是temporary临时的缩写
        $tmpArr = [$token, $timestamp, $nonce];
        sort($tmpArr);

        // 3.生成一个字符串 + sha1加密
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        // 4.加密字符串和signature对比
        if ($tmpStr == $signature) {
            // 相等, 返回echostr参数值
            return true;
        } else {
            // 不相等, 什么都不做
            return false;
        }
    }

    /**
     * 接收用户消息, 返回消息给用户
     *
     * @return String XML格式字符串
     */
    public function reponseMsg()
    {
        // 1.接收
        $xmlStr = file_get_contents('php://input');

        // 2.判断接收数据是否为空, 不为空
        if (!empty($xmlStr)) {
            // 3.转换对象
            $xmlObj = simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA);

            // 4.判断消息类型(多种)
            switch ($xmlObj->MsgType) {
                case 'text': // 接收文本消息
                    $result = $this->receiveTextMsg($xmlObj);
                    break;
                case 'image': // 接收图片消息
                    $result = $this->receiveImageMsg($xmlObj);
                    break;
                case 'event': // 接收事件消息
                    $result = $this->receiveEventMsg($xmlObj);
                    break;
                default: // 剩余消息类型
                    $content = "尝试点击<a href='http://www.jd.com'>商品</a>, 获得更多惊喜";
                    $result = $this->transmitTextXML($xmlObj, $content);
                    break;
            }

            // 5.echo返回
            echo $result;
        }
    }

    /**
     * 接收事件消息类, 判断事件类型, 返回XML字符串
     *
     * @param  SimpleXMLElement $xmlObj
     *
     * @return String
     */
    private function receiveEventMsg($xmlObj)
    {
        switch ($xmlObj->Event) {
            case 'CLICK': // 自定义菜单事件
                $result = $this->handleClickButton($xmlObj);
                break;
            case 'subscribe': // 关注事件
                $result = $this->handleSubscribe($xmlObj);
                break;

            default: // 未处理的其他事件
                # code...
                break;
        }

        return $result;
    }

    /**
     * 处理关注事件
     *
     * @param  SimpleXMLElement $xmlObj
     *
     * @return String
     */
    private function handleSubscribe($xmlObj)
    {
        $accessToken = getAccessToken(APP_ID, APP_SECRECT);
        $userInfo = getUserInfo($accessToken, $xmlObj->FromUserName);
        $result = $this->transmitTextXML($xmlObj, $userInfo);

        return $result;
    }

    /**
     * 判断/处理用户点击哪个click类型按钮
     *
     * @param  SimpleXMLElement $xmlObj
     *
     * @return String
     */
    private function handleClickButton($xmlObj)
    {
        switch ($xmlObj->EventKey) {
            case 'V1001': // "宅急送|天天1元"对应key是"V1001"
                // TODO: 从MySQL数据库随机读取
                $newsArr = [
                    ['Title' => '习大大', 'Description' => '习大大携夫人彭麻麻...', 'PicUrl' => 'http://1.shirleytest.applinzi.com/images/596c7157N852de046.jpg', 'Url' => 'http://1.shirleytest.applinzi.com/news.html'],
                    ['Title' => '习大大,,', 'Description' => '习大大携夫人彭麻麻...', 'PicUrl' => 'http://1.shirleytest.applinzi.com/images/5959f2beNbb7c699b.jpg', 'Url' => 'http://m.dianpign.com'],
                    ['Title' => '习大大...', 'Description' => '习大大携夫人彭麻麻...', 'PicUrl' => 'http://1.shirleytest.applinzi.com/images/59bf3c47N91d65c73.jpg', 'Url' => 'http://www.csdn.net'],
                    ['Title' => '习大大...', 'Description' => '习大大携夫人彭麻麻...', 'PicUrl' => 'http://1.shirleytest.applinzi.com/images/CW-t-fypceiq6378139.jpg', 'Url' => 'http://www.apple.com.cn'],
                ];
                $result = $this->transmitNewsXML($xmlObj, $newsArr);
                break;

            default:
                # code...
                break;
        }
        return $result;
    }

    /**
     * 给定二维数组, 拼接返回图文消息XML字符串
     *
     * @param  SimpleXMLElement  $xmlObj
     * @param  Array $newsArr
     *
     * @return String
     */
    private function transmitNewsXML($xmlObj, $newsArr)
    {
        if (!is_array($newsArr)) {
            return;
        }

        $tmpStr = '<item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
        </item>';
        // 循环遍历拼接item部分
        $itemStr = '';
        foreach ($newsArr as $itemArr) {
            $itemStr .= sprintf($tmpStr, $itemArr['Title'], $itemArr['Description'], $itemArr['PicUrl'], $itemArr['Url']);
        }

        $leftStr = "<xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[news]]></MsgType>
        <ArticleCount>%s</ArticleCount>
        <Articles>$itemStr</Articles>
        </xml>";

        $result = sprintf($leftStr, $xmlObj->FromUserName, $xmlObj->ToUserName, time(), count($newsArr));

        return $result;
    }

    /**
     * 接收用户发送的文本消息, 判断/处理消息内容, 返回XML字符串
     *
     * @return String
     */
    private function receiveTextMsg($xmlObj)
    {
        // 1.拼接返回内容
        $contentStr = '你发送的是文本消息, 返回输入内容:' . $xmlObj->Content;

        // 2.拼接
        $result = $this->transmitTextXML($xmlObj, $contentStr);

        // 3.返回
        return $result;
    }

    /**
     * 接收用户发送的图片消息, 返回图片消息XML字符串
     *
     * @return String
     */
    private function receiveImageMsg($xmlObj)
    {
        // 1.拼接返回内容
        $contentStr = '你发送的是图片消息, 返回图片url:' . $xmlObj->PicUrl;

        // 2.拼接
        $result = $this->transmitImageXML($xmlObj);

        // 3.返回
        return $result;
    }

    /**
     * 拼接返回文本消息XML字符串
     *
     * @return String
     */
    private function transmitTextXML($xmlObj, $contentStr)
    {
        // 1.拼接返回文本消息XML字符串
        $resultStr = '<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>';
        $result = sprintf($resultStr, $xmlObj->FromUserName, $xmlObj->ToUserName, time(), $contentStr);

        // 2.return返回
        return $result;
    }

    /**
     * 拼接返回图片消息XML字符串
     *
     * @return String
     */
    private function transmitImageXML($xmlObj)
    {
        // 1.xml字符串
        $imageStr = '<xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[image]]></MsgType>
  <Image>
    <MediaId><![CDATA[%s]]></MediaId>
  </Image>
</xml>';
        // 2.sprintf
        $result = sprintf($imageStr, $xmlObj->FromUserName, $xmlObj->ToUserName, time(), $xmlObj->MediaId);

        // 3.返回
        return $result;
    }
}

/**
 * 1.用户发送文本消息, 微信服务器post XML数据包(XML格式字符串)
 * ToUserName: 开发者微信号(公众号); 消息接收方
 * FromUserName: 用户微信号加密(openID); 消息发送方
 * CreateTime: 用户发送消息时间戳
 * MsgType: 用户发送消息类型; text表示文本类型(关键词)
 * Content: 用户发送消息内容
 * MsgId: 用户发送消息ID标识
<xml>
<ToUserName><![CDATA[toUser]]></ToUserName>
<FromUserName><![CDATA[fromUser]]></FromUserName>
<CreateTime>1348831860</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[1234]]></Content>
<MsgId>1234567890123456</MsgId>
</xml>

<ToUserName>toUser</ToUserName>
特殊情况: XML标签内容包含特殊符号 < / >, 发生解析错误;
<ToUserName>bob<!/\></ToUserName>

<ToUserName><![CDATA[bob<!/\>]]></ToUserName>

<ToUserName><![CDATA[toUser]]></ToUserName>

2.公众号(新浪云), 返回文本消息XML字符串, 给用户
ToUserName: 用户微信号加密(openID); 消息接收方
FromUserName: 开发者微信号(公众号); 消息发送方
CreateTime: 返回消息时间戳
MsgType: 消息类型; text返回是文本类型(关键词)
Content: 返回给用户消息内容

<xml>
<ToUserName><![CDATA[???]]></ToUserName>
<FromUserName><![CDATA[???]]></FromUserName>
<CreateTime>???</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[???]]></Content>
</xml>

3.用户发送图片消息, 接收XML字符串
<xml>
<ToUserName><![CDATA[toUser]]></ToUserName>
<FromUserName><![CDATA[fromUser]]></FromUserName>
<CreateTime>1348831860</CreateTime>
<MsgType><![CDATA[image]]></MsgType>
<PicUrl><![CDATA[this is a url]]></PicUrl>
<MediaId><![CDATA[media_id]]></MediaId>
<MsgId>1234567890123456</MsgId>
</xml>

4. 公众号返回图片给用户
ToUserName: 用户微信号加密(openID); 消息接收方
FromUserName: 开发者微信号(公众号); 消息发送方
CreateTime: 返回消息时间戳
MsgType: 消息类型; image返回是图片类型(关键词)
MediaId: 多媒体ID标识;
<xml>
<ToUserName><![CDATA[???]]></ToUserName>
<FromUserName><![CDATA[???]]></FromUserName>
<CreateTime>???</CreateTime>
<MsgType><![CDATA[image]]></MsgType>
<Image>
<MediaId><![CDATA[???]]></MediaId>
</Image>
</xml>

5. 用户点击click类型的按钮, 接收下面XML数据包
ToUserName: 消息接收方; 开发者微信号(公众号);
FromUserName: 消息发送方; 用户微信号加密(openID);
CreateTime: 用户点击时间戳
MsgType: event表示事件消息类型(关键词)
Event:属于哪种事件; CLICK表示自定义菜单事件(关键词)
EventKey: 点击click类型的哪个按钮, EventKey内容和创建自定义菜单, 设置key一致 (例如: "key":"V1001")

<xml>
<ToUserName><![CDATA[toUser]]></ToUserName>
<FromUserName><![CDATA[FromUser]]></FromUserName>
<CreateTime>123456789</CreateTime>
<MsgType><![CDATA[event]]></MsgType>
<Event><![CDATA[CLICK]]></Event>
<EventKey><![CDATA[EVENTKEY]]></EventKey>
</xml>

6. 公众号返回图文消息XML数据包结构
ToUserName: 用户微信号加密(openID); 消息接收方
FromUserName: 开发者微信号(公众号); 消息发送方
CreateTime: 返回消息时间戳
MsgType: news表示图文消息类型(关键词)
ArticleCount: 图文消息条数; 1表示返回1条图文消息
Title: 图文消息文本标题
Description: 图文消息详情描述
PicUrl: 图文消息图片URL地址
Url: 点击跳转H5页面URL地址
<xml>
<ToUserName><![CDATA[???]]></ToUserName>
<FromUserName><![CDATA[???]]></FromUserName>
<CreateTime>???</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>1</ArticleCount>
<Articles>
<item>
<Title><![CDATA[???]]></Title>
<Description><![CDATA[???]]></Description>
<PicUrl><![CDATA[???]]></PicUrl>
<Url><![CDATA[???]]></Url>
</item>
</Articles>
</xml>

7. 声明一个方法, 给定正确参数, 返回正确图文消息XML字符串
8. 两条图文消息XML数据包结构
<xml>
<ToUserName><![CDATA[???]]></ToUserName>
<FromUserName><![CDATA[???]]></FromUserName>
<CreateTime>???</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>???</ArticleCount>
<Articles>
<item>
<Title><![CDATA[???]]></Title>
<Description><![CDATA[???]]></Description>
<PicUrl><![CDATA[???]]></PicUrl>
<Url><![CDATA[???]]></Url>
</item>
<item>
<Title><![CDATA[???]]></Title>
<Description><![CDATA[???]]></Description>
<PicUrl><![CDATA[???]]></PicUrl>
<Url><![CDATA[???]]></Url>
</item>
<item>
<Title><![CDATA[???]]></Title>
<Description><![CDATA[???]]></Description>
<PicUrl><![CDATA[???]]></PicUrl>
<Url><![CDATA[???]]></Url>
</item>
<item>
<Title><![CDATA[???]]></Title>
<Description><![CDATA[???]]></Description>
<PicUrl><![CDATA[???]]></PicUrl>
<Url><![CDATA[???]]></Url>
</item>
</Articles>
</xml>

 */
