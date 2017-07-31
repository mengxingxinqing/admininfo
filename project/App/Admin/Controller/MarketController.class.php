<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-08
 * Time: 15:53
 */

namespace Partner\Controller;


use Partner\Service\MarketService;
use Partner\Service\PartnerService;
use Partner\Service\CountService;

class MarketController extends BaseController
{
    public function index(){
        $info = $this->service->getMarket($this->partner_id);
        if(empty($info)){
            redirect(U("Market/setting"));
        }
        $pser = new PartnerService();
        $pinfo = $pser->getInfo($this->partner_id);
        $invite = $this->service->getMarketResult($info);
        $p = I('p');
        $market_log = $this->service->getMarketLog($this->partner_id,$p);
        // var_dump($market_log);die;
        $this->assign('invite',$invite);
        $this->assign('info',$info);
        $this->assign('pinfo',$pinfo);
        $this->assign('market_log',$market_log);
        $this->display();
    }

    public function setting(){
        if(IS_POST){
            $data = I();
            for($i=1;$i<=4;$i++){
                $imgname = 'img'.$i;
                if($_FILES[$imgname]['name']){
                    $data[$imgname] = upload($imgname,"Uploads");
//                    $name = 'market_'.$imgname.'_'.$this->partner_id.'.jpg';
//                    $data[$imgname] = C('OSS_PATH').ossUploadImg($name,C('BASE_PATH').$data[$imgname],2);
                }
            }
            $data['content1'] = filteNewLine($data['content1']);
            $data['content3'] = filteNewLine($data['content3']);
            $data['partner_id'] = $this->partner_id;
            $this->service->open($data);
            $this->success("操作成功",U("Market/index"));
        }else{
            $info = $this->service->getMarket($this->partner_id);
            $pser = new PartnerService();
            $pinfo = $pser->getInfo($this->partner_id);
            $this->assign('info',$info);
            $this->assign('pinfo',$pinfo);
            $this->display();
        }
    }



    //上传图片
    public function upload($input){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     C('BASE_PATH').'Public/Uploads/';
        $upload->saveName  =     time().'_'.mt_rand();
        // 上传文件
        $info   =   $upload->uploadOne($_FILES[$input]);
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }else{// 上传成功
            return '/Public/Uploads/'.str_replace('../','/',$info['savepath'].$info['savename']);
        }
    }

    public function stop()
    {
        $this->service->stop($this->partner_id);
        echojson("操作成功","refresh");
    }

    public function exportData(){
        $id = I('id'); 
        $partner_id = session('partner_id');
        $res = $this->service->getMarketLogData($partner_id,$id);

        $set_info = $res['set_info'];
        $invite_info = $res['invite_info'];
        $start_time = date('m月d日',$set_info['start_time']);
        $end_time = date('m月d日',$set_info['end_time']);

        if($set_info['bonus_set_condition'] == 0){
            $bonus_set_condition = '好友关注既得';
        }else{
            $bonus_set_condition = '好友关注并下单得';
        }

        $title = $start_time.'-'.$end_time.' （活动规则：'.$bonus_set_condition.'，'.$set_info['bonus_set_money'].'元邀请红包，有效期'.$set_info['bonus_set_day'].'天）';
        
        $service = new CountService;

        $header = array('invite_user'=>'邀请人昵称/id','invite_bonus_info'=>'得到邀请红包','invite_bonus_used_info'=>'实际使用红包','invited_str'=>'邀请好友昵称/id');
        $name = $start_time.'-'.$end_time.'邀请活动';
        $service->exportExcelTpl($name,$title,$header,$invite_info);


    }

    public function bonusIndex()
    {
        $ser = new MarketService();
        $p = I('p',1);
        $list = $ser->getBonusActivity($this->partner_id,$p);
        $this->assign('pid',$this->partner_id);
        $this->assign('list',$list);
        $this->display();
    }

    public function addBonusActivity()
    {
        if(IS_POST){
            if(empty($_FILES['img']['name'])){
                $this->error("图片必须上传");
            }
            $data = I();
            $info['name'] = $data['name'];
            $info['partner_id'] = $this->partner_id;
            $info['start_time'] = strtotime($data['start_time']);
            $info['end_time'] = strtotime($data['end_time']);
            $info['max_num'] = $data['max_num'];
            $info['create_time'] = time();
            $id = M('bonus_activity')->add($info);
            $money = I('money');
            $param = I('param');
            $active_time = I('active_time');
            $num = I('num');
            for ($i=0;$i<count($money);$i++){
                M('bonus_activity_detail')->add(['activity_id'=>$id,'bonus_money'=>$money[$i],'bonus_day'=>$active_time[$i],'bonus_param'=>$param[$i],'num'=>$num[$i]]);
            }
            if($_FILES['img']['name']){
                $img = upload('img',"Uploads");
//                $name = 'bonus_'.$id.'_'.$this->partner_id.'.jpg';
//                $data['img'] = C('OSS_PATH').ossUploadImg($name,C('BASE_PATH').$data['img'],2);
            }
            M('bonus_activity')->where(['id'=>$id])->save(['img'=>$img]);
            $this->success('操作成功',U('Market/bonusIndex'));
        }else{
            $this->display();
        }
    }

    public function stopBonusActivity()
    {
        $id = I('id');
        M('bonus_activity')->where(['id'=>$id,'partner_id'=>$this->partner_id])->save(['status'=>1]);
        $this->success('操作成功');
    }
    
}