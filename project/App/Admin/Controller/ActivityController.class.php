<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-03
 * Time: 10:47
 */

namespace Partner\Controller;


use Partner\Model\ShopModel;
use Partner\Service\ActivityService;
use Partner\Service\ShopService;
use Partner\Service\PartnerService;
use Base\Service\WechatHelper;

class ActivityController extends BaseController
{
    public function index()
    {
        $acser = new ActivityService();
        $list = $acser->getlist($this->partner_id);
        $this->assign("list",$list);
        $p = I('p');
        $oldlist = $acser->getOldList($p,$this->partner_id);
        $this->assign("oldlist",$oldlist);

        $partner_service = new PartnerService();
        $isset_week = $partner_service->isSetWeek($this->partner_id);
        $this->assign('isset_week',$isset_week);

        $this->display();
    }

    public function modify()
    {
        $id = I('id');
        if(empty($id)){
            $type = I('type');
        }else{
            $info = $this->service->getById($id);
            $this->assign('info',$info);
            $type = $info['type'];
        }
        $shopser = new ShopService();
        $shop_list = $shopser->getShopInfoList();
        $this->assign('shop_list',$shop_list);
        $this->display('modify'.$type);
    }

    /**
     * 首单送红包
     */
    public function modifyActivity()
    {
        if(IS_POST){
            $data = I();
            if(empty($data['shop_names'])){
                echojson('请选择一家门店','','error');
                die;
            }
            $data['type'] = $this->service->getFunType(I('m'));
            $method = I('m');
            $data['partner_id'] = $this->partner_id;
            if(method_exists($this->service,$method)){
                $res = $this->service->$method($data);
            }else{
                echojson("系统错误","","error");
            }
            if($res){
                echojson("操作成功","refresh");
            }else{
                echojson("店铺同类型活动已存在","","error");
            }
        }else{
            $view = I('m');
            $smod = new ShopModel();
            $list = $smod->getBaseList();
            $this->assign('list',$list);
            $this->assign('m',$view);
            $this->display($view);
        }
    }

    public function stop()
    {

        $id = I('id');
        $this->service->stopActivity($id);
        echojson("操作成功","refresh");
    }

    public function setUserRecharge(){
        $service = new ActivityService();
        $partner_id = session('partner_id');
        if(IS_POST){
            $data = I();
            $service->setUserRecharge($partner_id,$data);
            echojson('修改成功');
        }else{
            $is_user_recharge = $service->isUserRecharge($partner_id);
            $list = $service->getUserRechargeList($partner_id);
            $this->assign('list',$list);
            $this->assign('is_user_recharge',$is_user_recharge);
            $this->display();
        }

    }

    public function userRechargeLog(){
        $partner_id = session('partner_id');
        $p = I('p',0);
        $start_time = I('start_time');
        $end_time = I('end_time');
        $recommand_id = I('recommand_id');
        $uid = I('uid');
        $service = new ActivityService();
        $list = $service->getUserRechargeLog($partner_id,$p,$start_time,$end_time,$recommand_id,$uid);
        $this->assign('data',$list);
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        $this->assign('recommand_id',$recommand_id);
        $this->assign('uid',$uid);
        $this->assign('partner_id',$partner_id);
        $this->display();
    }

    public function userAccountMoneyList(){
        $partner_id = session('partner_id');
        $p = I('p');
        $service = new ActivityService();
        $res = $service->getUserAccountMoneyList($p,$partner_id);
        $this->assign('data',$res);
        $this->display();
    }

    /**
     * 充值二维码设置
     */
    public function rechargeQrcode(){
        $p = I('p',1);
        $list = $this->service->getRechargeQrcodePage($this->partner_id,$p);
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 修改充值二维码
     */
    public function recharegeQrcodeEdit(){
        if(IS_POST){
            $data = I();
            $data['partner_id'] = $this->partner_id;
            //判断是否更换图片
            if ($_FILES['avatar']['name']) {
                $data['avatar'] = upload('avatar','avatar');
            }

            $res = $this->service->rechargeQrcodeSave($data);
            if($res !== false){
                // echojson("操作成功",U('Activity/rechargeQrcode'));
                $this->success('设置成功', 'rechargeQrcode'); 
            }else{
                $this->error('设置失败', 'rechargeQrcode'); 
            }
        }else{
            $id = I('id');
            $this->assign('id',$id);
            $info = $this->service->getRechargeQrcodeById($id);
            $this->assign('info',$info);
            $this->display();
        }
    }

    /**
     * 下载二维码
     */
    public function rechargeQrcodeDown()
    {
        $id = I('id');
        $info = $this->service->getRechargeQrcodeById($id);
        $path = C('BASE_PATH').$info['url'];
        getFile($path);
    }

    /**
     * 删除渠道二维码
     */
    public function rechargeQrcodeDel(){
        $id = I('id');
        $res = $this->service->qrcodeDel($id);
        if($res !== false){
            echojson("删除成功","refresh");
        }else{
            echojson("删除失败","refresh","error");
        }
    }

    public function goodsDiscount(){
        $partner_id = session('partner_id');
        if(IS_POST){
            $data = I();
            $service = new ActivityService();
            $service->addGoodsDiscount($partner_id,$data);
            echo json_encode(array('msg'=>'ok'));
        }else{
            $smod = new ShopModel();
            $list = $smod->getBaseList();
            $this->assign('list',$list);

            $partner_service = new PartnerService();
            $isset_week = $partner_service->isSetWeek($this->partner_id);
            $this->assign('isset_week',$isset_week);
            $this->display();
        }
        
    }

    public function getGoodsDiscount(){
        $partner_id = session('partner_id');
        $service = new ActivityService();
        $goods_discount = $service->getGoodsDiscount($partner_id);
        echo json_encode($goods_discount);
    }

    public function changeDiscountStatus(){
        $partner_id = session('partner_id');
        $now_status = I('now_status');
        $service = new ActivityService($partner_id);
        $service->changeDiscountStatus($partner_id,$now_status);
        echo json_encode(array('msg'=>'ok'));
    }
    
}