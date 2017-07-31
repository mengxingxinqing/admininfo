<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-05-31
 * Time: 14:35
 */

namespace Partner\Model;


class LoginModel extends BaseModel
{
    public function check($username,$pwd){
        $info = M('partner')->where(array('tel'=>$username,'pwd'=>$pwd))->field('id,name,status')->find();
        if(!empty($info)){
            if($info['status'] == 0){
                $data['err'] = "账号异常，请联系管理员";
                return $data;
            }
            if($info['status'] == 2){
                $data['err'] = "账号被禁用，请联系管理员";
                return $data;
            }
            session('partner_id',$info['id']);
            $data['msg'] = "登录成功";
            $data['id'] = $info['id'];
            session('partner_name',$info['name']);
            return $data;
        }
        $info = M('partner_admin')->where(array('tel'=>$username,'pwd'=>$pwd))->field('id,partner_id,type_id,status')->find();
        if(!empty($info)){
            session('partner_id',$info['partner_id']);
            $name = M('partner')->where(array('id'=>$info['partner_id']))->getField('name');
            session('partner_name',$name);
            session('partner_type',$info['type_id']);
            $data['msg'] = "登录成功";
            $data['type'] = $info['type_id'];
            $data['id'] = $info['partner_id'];
            return $data;
        }
        $data['err'] = "账号或密码错误，请重新登录";
        return $data;
    }

    public function getStatus($id)
    {
        $status = M('partner')->where(array('id'=>$id))->getField('status');
        return $status;
    }
}