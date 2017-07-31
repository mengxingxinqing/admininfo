<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-30
 * Time: 11:21
 */

namespace Partner\Controller;
use Partner\Service\CategoryService;
use Partner\Service\SettingService;

class SettingController extends BaseController
{
    public function changeInfo()
    {
        if(IS_POST){
        	$oldtel = I('oldtel');
        	$oldpwd = I('oldpwd');
        	$newpwd = I('newpwd');
        	$repwd = I('repwd');
            $service = new SettingService();
            $res = $service->changePwd($oldtel,$oldpwd,$newpwd,$repwd);
            if($res['status'] == 'success'){
            	echojson($res['msg'],U('Setting/authorityManage'),$res['status']);
            }else{
            	echojson($res['msg'],'',$res['status']);
            }
            
        }else{
            $this->display();
        }
    }

    public function changeBaseInfo(){
        $partner_id = session('partner_id');
        $service = new SettingService();
        if(IS_POST){
            $data = I();
            if(!empty($_FILES['logo']['name'])) {
                $data['logo'] = upload('logo','partner');
                $name = "logo".$partner_id.'.jpg';
                $data['logo'] = C('OSS_PATH').ossUploadImg($name,C('BASE_PATH').$data['logo'],2);
            }
            if(!empty($_FILES['select_img']['name'])) {
                $data['select_img'] = upload('select_img','partner');
                $name = "select_img".$partner_id.'.jpg';
                $data['select_img'] = C('OSS_PATH').ossUploadImg($name,C('BASE_PATH').$data['select_img'],2);
            }
            $service->changeBaseInfo($partner_id,$data);
            $this->success('修改成功', 'changeBaseInfo');
        }else{
            $info = $service->getPartnerInfo($partner_id);
            $charge_list = $service->getSendCharge($partner_id);
            // var_dump($charge_list);die;
            $reserve_list  = $service->getReserveList($partner_id);
            $account_list = $service->getAccountList($partner_id);
            $this->assign('info',$info);
            $this->assign('charge_list',$charge_list);
            $this->assign('reserve_list',$reserve_list);
            $this->assign('account_list',$account_list);
            $this->display();
        }
    }

    public function getPartnerInfo(){
        $partner_id = session('partner_id');
        $service = new SettingService();
        $res = $service->getPartnerInfo($partner_id);
        $res['pay_type'] = explode(',',$res['pay_type']);
        echo json_encode($res);
    }

    public function tplType(){
        $partner_id = session('partner_id');
        $service = new SettingService();
        if(IS_POST){
            $tpl_type = I('tpl_type');
            $res = $service->changeTplType($partner_id,$tpl_type);
            $back = I('back');
            if(!empty($back)){
                $this->success('修改成功',U($back));
                die;
            }else{
                $this->success('修改成功', 'tplType');
            }
        }else{
            $back = I('back','');
            $type = $service->getTplType($partner_id);
            $this->assign('back',$back);
            $this->assign('tpl_type',$type);
            $this->display();
        }
    }
    private function getKeyVal($info){
        $res = explode('-',$info['key']);
        $res[] = '/Public/preindex/'.$info['savepath'].$info['savename'];
        return $res;
    }

    public function preIndexSetting()
    {
        if(IS_POST){
            $partner_id = session('partner_id');
            $typearr = array('banner'=>0,'small'=>1,'big'=>2);
            //上传图片
            $info = uploadMany("preindex");
            echo '<pre>';
            print_r($info);
            foreach ($info as $k=>$v){
                $res = self::getKeyVal($v);
                print_r($res);
                $data['type'] = $typearr[$res[0]];
                $data['key'] = $res[1];
                $data['partner_id'] = $partner_id;
                $old = M('preindex_img')->where($data)->find();
                if(empty($old)){
                    $data['img'] = $res[2];
                    $data['url'] = I($res[0].'url-'.$data['key']);
                    M('preindex_img')->add($data);
                }else{
                    $ndata['img'] = $res[2];
                    $ndata['url'] = I($res[0].'url-'.$data['key']);
                    M('preindex_img')->where($data)->save($ndata);
                }
                echo M()->getLastSql();
            }
            $info['title'] = I('title');
            $info['remark'] = I('remark');
            $info['is_big'] = I('isbig');
            $res = M('preindex_setting')->where(array('partner_id'=>$partner_id))->find();

            if(empty($res)){
                $info['partner_id'] = $partner_id;
                M('preindex_setting')->add($info);
            }else{
                M('preindex_setting')->where(array('partner_id'=>$partner_id))->save($info);
            }
            echo M()->getLastSql();exit;
            $this->success("操作成功");
        }else{
            $partner_id = session('partner_id');
            $catSer = new CategoryService();
            $urls = $catSer->getCatUrls($partner_id);
            $setSer = new SettingService();
            $info = $setSer->getSetting($partner_id);
            $banner = $setSer->getImg($partner_id,0,3);
            $small = $setSer->getImg($partner_id,1,12);

            $big = $setSer->getImg($partner_id,2,6);
            $this->assign('banner',$banner);
            $this->assign('smalllist',$small);
            $this->assign('biglist',$big);
            $this->assign('info',$info);
            $this->assign('urls',$urls);
            $this->display();
        }
    }

    public function delimg()
    {
        $partner_id = session('partner_id');
        $id = I('id');
        M('preindex_img')->where(array('partner_id'=>$partner_id,'id'=>$id))->delete();
    }

    public function addAccount(){
        $partner_id = session('partner_id');
        if(IS_POST){
            $data = I();
            $service = new SettingService();
            $is_repeat = $service->checkAccountRepeat($partner_id,$data['tel']);
            if($is_repeat == 1){
                echo echojson('该手机号已经注册','','error');
            }else{
                $service->addAccount($partner_id,$data);
                echo echojson('添加成功',U('Setting/changeBaseInfo'));
            }
        }else{
            $this->display();
        }
    }

    public function editAccount(){
        $partner_id = session('partner_id');
        $service = new SettingService();
        $data = I();
        if(IS_POST){
            $service->editAccount($data['account_id'],$data['pwd']);
            echo echojson('设置成功',U('Setting/changeBaseInfo'));
        }else{
            $info = $service->getAccountById($data['id']);
            $this->assign('info',$info);
            $this->display();
        }
    }

    public function deleteAccount(){
        $partner_id = session('partner_id');
        $id = I('id');
        $service = new SettingService();
        $service->deleteAccount($partner_id,$id);
        echojson('删除成功','refresh');
    }

    public function authorityManage(){
        $partner_id = session('partner_id');
        $service = new SettingService();
        $list = $service->getAuthorityRoleList($partner_id);
        $this->assign('list',$list);
        $this->display();
    }

    public function addAuthority(){
        $partner_id = session('partner_id');
        $service = new SettingService();
        if(IS_POST){
            $data = I();
            $is_repeat = $service->checkAccountRepeat($partner_id,$data['tel']);
            if($is_repeat == 1){
                echo echojson('该手机号已经注册','','error');
            }else{
                $service->addAuthority($partner_id,$data);
                echo echojson('添加成功',U('Setting/authorityManage'));
            } 
        }else{ 
            $menus_list = $service->getMenus($partner_id);
            $this->assign('menus_list',$menus_list);
            $this->display();
        }
        
    }

    public function editAuthority(){
        $partner_id = session('partner_id');
        $role_id = I('role_id');
        $service = new SettingService();
        if(IS_POST){
            $data = I();
            $service->editAuthority($partner_id,$data);
            echo echojson('设置成功',U('Setting/authorityManage'));
        }else{
            $menus_list = $service->getMenus($partner_id);
            $this->assign('menus_list',$menus_list);
            $this->assign('role_id',$role_id);
            $this->display();
        }
        
    }

    public function getAuthorityRoleById(){
        $partner_id = session('partner_id');
        $role_id = I('role_id');
        $service = new SettingService();
        $res = $service->getAuthorityRoleById($partner_id,$role_id);
        echo json_encode($res);
    }

    public function deleteAuthority(){
        $partner_id = session('partner_id');
        $role_id = I('role_id');
        $service = new SettingService($partner_id);
        $service->deleteAuthority($partner_id,$role_id);
        echojson('删除成功','refresh');
    }
}