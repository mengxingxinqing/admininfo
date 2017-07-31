<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-08-29
 * Time: 12:12
 */

namespace Home\Controller;


use Think\Controller;

class ArticleController extends Controller
{
    public function index()
    {
        $id = I('get.id');
        $info = M('wechat_article')->find($id);
        if(empty($info)){
            exit;
        }
        $this->assign('info',$info);
        $this->display();
    }
}