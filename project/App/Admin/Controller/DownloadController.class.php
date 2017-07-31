<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-08-13
 * Time: 18:08
 */

namespace Partner\Controller;


use Think\Controller;

class DownloadController extends Controller
{
    public function index()
    {
        $res = M('apk')->order('id desc')->find();
        $res['url'] = C("BASE_URL").$res['url'];
        $this->assign('res',$res);
        $this->display();
    }
}