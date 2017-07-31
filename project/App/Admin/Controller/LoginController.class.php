<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-05-18
 * Time: 10:43
 */

namespace Partner\Controller;
use Partner\Service\LoginService;
use Partner\Service\PartnerService;
use Think\Controller;

class LoginController extends Controller
{
    public function index(){
//        header("Content-Type: text/html;charset=utf-8");
//        echo "<h2>味来外卖新域名正式上线啦,~~<a href='http://www.weilai517.com/index.php/Partner/Login/index.html'>商户登录点击这里，进去看看吧</a><h2>";exit;
        $this->display();
    }
    
    public function check(){
        $username = I('username');
        $pwd = I('password');
        $need = I('needcode');
        if($need == 1){
            $v = new \Think\Verify();
            if(!$v->check(I('verifycode'))){
                echo "验证码错误";exit;
            }
        }
        $ser = new LoginService();
        $data = $ser->check($username,$pwd);
        if($data['err']){
            echo $data['err'];exit;
        }else if(empty($data['type'])){
            echo "success";
        }else{
            echo $data['type'];
        }
    }

    public function logout()
    {
        session('partner_id',null);
        session('partner_name',null);
        session('partner_type',null);
        redirect(U("Login/index"));
    }

    public function verifycode(){
        $v = new \Think\Verify();
        $v->entry();
    }

    public function chrome()
    {
        $this->display();
    }
}