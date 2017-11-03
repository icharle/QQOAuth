# 基于ThinkPHP3.2.3实现第三方QQ登录
## 杂说
最近在弄一个需要验证用户真实性的网页，开始是想到用微信登录的(毕竟现在用微信的人数很多，用起来比较方便吧),但是咧，没有获取到微信登录的权限，需要给钱才能用到微信登录这个功能，所以最后还是直接使用QQ登录好了，能够验证用户真实性就OK吧。
## SDK引入
官方的SDK很长，而且不适合引入到TP框架里边，因此，这里用到的SDK结合官方SDK进行了改写(参考网上的列子结合自己的理解重新整合了一份SDK)。
**方法：**先在`Vendor`文件夹中新建一个文件夹`QQ`,并且在里边新建一个`qqAuth.class.php`文件，并将下面代码贴入其中。

```
<?php
namespace Vendor\QQ;
class qqAuth {
    private static $data;
    //APP ID
    private $app_id="";
    //APP KEY
    private $app_key="";
    //回调地址
    private $callBackUrl="";
    //Authorization Code
    private $code="";
    //access Token
    private $accessToken="";
    public function __construct($appid,$appkey,$callback){
        $this->app_id=$appid;
        $this->app_key=$appkey;
        $this->callBackUrl=$callback;
        //检查用户数据
        if(empty($_SESSION['QC_userData'])){
            self::$data = array();
        }else{
            self::$data = $_SESSION['QC_userData'];
        }
    }
    //获取Authorization Code
    public function getAuthCode(){
        $url="https://graph.qq.com/oauth2.0/authorize";
        $param['response_type']="code";
        $param['client_id']=$this->app_id;
        $param['redirect_uri']=$this->callBackUrl;
        //-------生成唯一随机串防CSRF攻击
        $state = md5(uniqid(rand(), TRUE));
        $_SESSION['state']=$state;
        $param['state']=$state;
        $param['scope']="get_user_info";       //其它权限
        $param =http_build_query($param,'','&');
        $url=$url."?".$param;
        header("Location:".$url);
    }
    //通过Authorization Code获取Access Token
    private function getAccessToken(){
        $url="https://graph.qq.com/oauth2.0/token";
        $param['grant_type']="authorization_code";
        $param['client_id']=$this->app_id;
        $param['client_secret']=$this->app_key;
        $param['code']=$_GET['code'];
        $param['redirect_uri']=$this->callBackUrl;
        $param =http_build_query($param,'','&');
        $url=$url."?".$param;
        return $this->getUrl($url);
    }
    //获取openid
    public function getOpenID(){
        $rzt=$this->getAccessToken();
        parse_str($rzt,$data);
        $this->accessToken=$data['access_token'];
        $url="https://graph.qq.com/oauth2.0/me";
        $param['access_token']=$this->accessToken;
        $param =http_build_query($param,'','&');
        $url=$url."?".$param;
        $response=$this->getUrl($url);
        //--------检测错误是否发生
        if(strpos($response, "callback") !== false){
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos -1);
        }
        $user = json_decode($response);
        if(isset($user->error)){
            exit("错误代码：100007");
        }
        return $user->openid;
    }
    //获取信息
    public function getUsrInfo(){
        if($_GET['state'] != $_SESSION['state']){
            exit("错误代码：300001");
        }
        $this->code=$_GET['code'];
        $openid=$this->getOpenID();
        if(empty($openid)){
            return false;
        }
        $url="https://graph.qq.com/user/get_user_info";
        $param['access_token']=$this->accessToken;
        $param['oauth_consumer_key']=$this->app_id;
        $param['openid']=$openid;
        $param =http_build_query($param,'','&');
        $url=$url."?".$param;
        $rzt=$this->getUrl($url);
        return $rzt;
    }
    //CURL GET
    private function getUrl($url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        if (!empty($options)){
            curl_setopt_array($ch, $options);
        }
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

}
```

这个就是我们需要的SDK文件。

**接着，**我们在控制器中调用方法。

```
引入SDK包
use \Vendor\QQ\qqAuth;

/**
* 主显示页面，前台调用方法
*/
public function index(){

    $this->display();

}


/**
*登录获的Authorization Code
*/
public function QQLogin()
{
    $qqlogin=new qqAuth('You appid','You appkey','You callback url');
    $qqlogin->getAuthCode();
}


/**
*获取用户信息openid、userInfo
 */
public function Getinfo(){
    $qqlogin=new qqAuth('You appid','You appkey','You callback url');
    $result = $qqlogin->getUsrInfo();         //获取用户详细信息
    //$result = $qqlogin->getOpenID();         //获取openid
    //var_dump($result);
    echo "<script>window.close();</script>";       //授权成功后关闭页面
}



/**
*前台调用的JS，点击按钮后触发登录页面，并且在另一个页面展示
*/
<script>
    function toQQLogin() {
        var _url="{:U('Index/QQLogin')}";  //转向网页的地址;
        var name='QQ授权登录';    //网页名称，可为空;
        var iWidth=800; //弹出窗口的宽度;
        var iHeight=600;   //弹出窗口的高度;
        //获得窗口的垂直位置
        var iTop = (window.screen.availHeight - 30 - iHeight) / 2;
        //获得窗口的水平位置
        var iLeft = (window.screen.availWidth - 10 - iWidth) / 2;
        window.open(_url, name, 'height=' + iHeight +
            ',innerHeight=' + iHeight + ',width=' + iWidth +
            ',innerWidth=' + iWidth + ',top=' + iTop + ',left=' + iLeft +
            ',status=1,toolbar=no,menubar=no,location=1,resizable=no,scrollbars=0,titlebar=no');
    }
</script>
```

## 效果展示
![33-1](https://icharle-1251944239.cosgz.myqcloud.com/%E5%8D%9A%E5%AE%A2/%E5%AE%9E%E7%8E%B0%E7%AC%AC%E4%B8%89%E6%96%B9QQ%E7%99%BB%E5%BD%95/33-1.png)
[Demo效果](http://soarteam.cn/QQOAuth/)
[项目地址](https://github.com/icharle/QQOAuth)



