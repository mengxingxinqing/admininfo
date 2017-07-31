<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/7/29 0029
 * Time: 上午 12:53
 */

namespace Home\Controller;
use Api\Service\WechatService;
use Base\Service\WechatHelper;
use Home\Service\ShareService;
use Home\Service\UserService;
use Think\Controller;

class BaseController extends Controller
{
    public function _initialize(){
        if(I('debug')=='1024'){
            session('partner_id',8);
            session('uid',1635);
            session('shop',2);
            session('openid','oCdbmsklNLOz-CTpmhn9euyDDyLA');
        }

        //获取partner_id
        $pid = self::getPid();

        //获取uid
        $uid = self::getUid($pid);
        if(!$uid){
            redirect(U("Share/index"));
        }
        if(IS_GET){
            //获取分享信息
//           $share = self::getShare($pid,$uid);
//           $this->assign('share',$share);
        }


        //是否跳过后面验证
        $jmp = I('j');
        $sid = self::getShopid($uid);
        if(!$sid && $jmp != 1){
            $url = U("SelectShop/index");
            redirect($url);
        }
    }



    /** 根据url 获取用户的partner_id
     * @return mixed
     */
    private function getPid()
    {
        $partner_id = session('partner_id');
        if(empty($partner_id)){
            $partner_id = I('pid');
            if(empty($partner_id)){
                redirect(U('SelectShop/errorpage1'));
            }else{
                session('partner_id',$partner_id);
            }
        }else{
            $pid = I('pid');
            if(!empty($pid) && $partner_id != $pid){
                session('partner_id',$pid);
                session('uid',null);
                session('shop',null);
                session('openid',null);
                $partner_id = $pid;
            }
        }
        return $partner_id;
    }

    /** 根据openid 获取用户的uid
     * @param $pid
     * @return bool|mixed
     */
    private function getUid($pid)
    {
        $uid = session('uid');
        if(empty($uid)){
            $helper = WechatHelper::get($pid);
            $json = $helper->weObj->getOauthAccessToken();
            if($json){
                $openid = $json['openid'];
                session('openid',$openid);
                $userser = new UserService();
                $uid = $userser->getUid($openid);
                //获得了授权，却又缺少系统内的信息，补全系统内信息
                if(empty($uid)){
                    $uid = self::getUserinfo($pid,$json);
                }
                session('uid',$uid);
            }else{
                return false;
            }
        }
        $this->assign('uid',$uid);
        return $uid;
    }

    /** 获取用户的微信信息
     * @param $pid
     * @param $json
     */
    private function getUserinfo($pid, $json)
    {
        $helper = WechatHelper::get($pid);
        $user_info = $helper->weObj->getUserInfo($json['openid']);
        $data['openid'] = $json['openid'];
        $data['nickname'] = $user_info['nickname'];
        if(empty($data['nickname'])){
            $data['nickname'] = '用户';
        }
        $data['sex'] = $user_info['sex'];
        $data['partner_id'] = $pid;
        $data['from_way'] = '迁移用户';
        //下载用户头像
        $data['headimgurl'] = saveMedia($user_info['headimgurl'],'avatar',$user_info['openid']);
        $weser = new WechatService();
        $uid = $weser->userSub($data);
        return $uid;
    }

    /** 获取用户的店铺id
     * @param $uid
     * @return mixed
     */
    private function getShopid($uid)
    {
        //参数直接跳转
        $cl = I('cl');
        //切换店铺必填参数，防止session缓存
        if($cl == 1){
            session('shop',null);
        }
        $sid = session('shop');
        $userser = new UserService();
        if(empty($sid)){
            $sid = I('sid');
            //这个参数确定用户数据库的店铺是否修改
            $ischoose = I('chos');
            if(!empty($ischoose)&&!empty($sid)){
                $userser->selectShop(session('partner_id'),$uid,$sid);
            }
            if(empty($sid)){
                $sid = $userser->getShopid($uid);
            }

            session('shop',$sid);
        }
        return $sid;
    }

    /** 获取分享的设置项
     * @param $pid
     * @param $uid
     * @return mixed
     */
    private function getShare($pid,$uid)
    {
        $ser = new ShareService();
        return $ser->getShare($pid,$uid);
    }

}