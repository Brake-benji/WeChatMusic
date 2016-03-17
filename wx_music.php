<?php

/**
 * wechat php test
 */
//define your token
define("TOKEN", "weixin");
$wechatObj = new wechatCallbackapiTest();
$wechatObj->valid();

class wechatCallbackapiTest {

    //验证方法
    public function valid() {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg() {
        //get post data, May be due to the different environments
        //xml 传输数据
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; //接受微信服务器发送过来的数据
        //extract post data
        if (!empty($postStr)) {
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
              the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);  //安全
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);  //把XML转换为对象
            $fromUsername = $postObj->FromUserName;  //开发者账号
            $toUsername = $postObj->ToUserName;  //用户的账号
            $keyword = trim($postObj->Content);  //消息内容
            $time = time();  //时间
            $textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
							</xml>";
            if (!empty($keyword)) {
                $contentStr = $postObj->Recognition;  //获取用户语音消息
                $content = preg_replace("/[^\x{4e00}-\x{9fa5}]/u", '', $contentStr);  //正则替换非法字符
                //$content = '点歌信仰';

                if (strpos($content, '点歌') !== false) {
                    /*$url = 'http://s.music.163.com/search/get/?type=1&s=[$keyword]&limit=1';  //API地址
                    $contentStr = file_get_contents($url); //把网页内容获取到当前
                    $msgType = "text";
                    //$contentStr = $keyword;  //复读机
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                    //echo $resultStr;*/
                    $this->getMusic($postObj,$msg);
                }
            } else {
                echo "Input something...";
            }
        } else {
            echo "";
            exit;
        }
    }

//返回音乐类型的模板
      public function getMusic($postObj,$msg){

      $keyword = mb_substr($msg, 2, mb_strlen($msg, 'utf-8'), 'utf-8');

      $url = 'http://s.music.163.com/search/get/?type=1&s=[$keyword]&limit=1';
      $content = file_get_contents($url); //把网页内容获取到当前
      //把json解析成PHP能认识的数据
      //var_dump(json_decode($content,true));
      $music = json_decode($content, true); //转换出数组
      //echo $music;
      $add = $music["result"]["songs"]["0"]["audio"];  //地址
      $songName = $music["result"]["songs"]["0"]["name"];  //歌曲名
      $songer = $music["result"]["songs"]["0"]["artist"][0]['name'];  //歌手

      $musictpl = "<xml>
      <ToUserName><![CDATA[%s]]></ToUserName>
      <FromUserName><![CDATA[%s]]></FromUserName>
      <CreateTime>%s</CreateTime>
      <MsgType><![CDATA[music]]></MsgType>
      <Music>
      <Title><![CDATA[%s]]></Title>
      <Description><![CDATA[%s]]></Description>
      <MusicUrl><![CDATA[%s]]></MusicUrl>
      <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
      </Music>
      </xml>";

      $fromUsername = $postObj->FromUserName;
      $toUsername = $postObj->ToUserName;
      $time = time();

      $msgType = "text";
      //$contentStr = $keyword;  //复读机
      $resultStr = sprintf($musictpl, $fromUsername, $toUsername, $time, $songName, $songer, $add, $add);
      echo $resultStr;
      } 

    private function checkSignature() {
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

}

?>