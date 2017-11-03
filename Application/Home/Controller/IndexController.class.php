<?php
namespace Home\Controller;
use Think\Controller;
use \Vendor\QQ\qqAuth;
class IndexController extends Controller {

    /**
     * 主显示页面
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
}