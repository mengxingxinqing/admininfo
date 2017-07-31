<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-08
 * Time: 15:54
 */

namespace Partner\Model;


use Base\Service\WechatHelper;

class MarketModel extends BaseModel
{

    public function isOpen($pid){
        $is_open = M('market_setting')->where(array('partner_id'=>$pid))->getField("is_open");
        if(empty($is_open) || $is_open == 0){
            return 0;
        }
        return 1;
    }

    public function getMarket($pid)
    {
        $res = M('market_setting')->where(array('partner_id'=>$pid))->find();
        return $res;
    }

    public function open($data)
    {
        $data['is_open'] = 1;
        $data['open_time'] = time();
        $num = M('market_setting')->where(array('partner_id'=>$data['partner_id']))->count();
        if($num>0){
            M('market_setting')->where(array('partner_id'=>$data['partner_id']))->save($data);
        }else{
            M('market_setting')->add($data);
        }
    }

    public function stop($pid)
    {
        M('market_setting')->where(array('partner_id'=>$pid))->save(array('is_open'=>0,"end_time"=>time()));
        
    }

    public function getMarketResult($info)
    {
        if(empty($info)) return;
        $pid = $info['partner_id'];

        $umod = new UserModel();
        if($info['is_open']){
            $data['num1'] = M('market_user')->where(array('partner_id'=>$pid))->count();
            $res = $umod->getUserNum($pid);
        }else{
            $data['num1'] = M('market_log')->where(array('partner_id'=>$pid))->order('id desc')->limit(1)->getField('send_num');
            $res = M('market_log')->where(array('partner_id'=>$pid))->order('id desc')->limit(1)->getField('user_num');
        }
        $data['user_num'] = $res;
        $data['num2'] = ($data['num1']/$res)*100;
        $data['num3'] = $umod->getInviteUserNumInTime($pid,$info['open_time'],time(),2);
        $data['num5'] = $data['num3'];
        // $data['num4'] = ($data['num3']/$data['num4'])*100;
        $data['num6'] = $umod->getInviteUserNumInTime($pid,$info['open_time'],time(),1);
        $data['num4'] = ($data['num5']/$data['num6'])*100;
        $data['num7'] = $data['num3'];
        $data['num8'] = $data['num3']*$info['money'];
        $bmod = new BonusModel();
        $res = $bmod->getInviteNum($pid,$info['open_time'],time());
        $data['num9'] = $res['num'];
        $data['num10'] = $res['total_money'];
        return $data;
    }

    public function getMarketSetInfo($pid){
        $res = M('market_setting')->where(array('partner_id'=>$pid))->find();
        return $res;
    }


    //获得发起邀请的人数
    public function getSendNum($pid){
        $count = M('market_user')->where(array('partner_id'=>$pid))->count();
        return $count;
    }

    public function clearMarketUser($pid){
        M('market_user')->where(array('partner_id'=>$pid))->delete();
    }

    public function getMarketLog($partner_id,$page){
        $res = M('market_log')->where(array('partner_id'=>$partner_id))->select();
        $sql = "SELECT * FROM sm_market_log WHERE partner_id=".$partner_id.' ORDER BY id DESC';
        $data = $this->getPage($sql,$page);
        return $data;
    }

    public function getMarketLogData($partner_id,$id){
        $res = M('market_log')->where(array('id'=>$id))->find();
        return $res;
    }

    public function getInviteInfo($partner_id,$start_time,$end_time){ 
        $where = ' b.partner_id='.$partner_id;
        $where .= ' AND b.type=2';
        $where .= ' AND b.create_time>='.$start_time.' AND b.create_time<'.$end_time;
        $sql = "SELECT DISTINCT(b.uid),u.nickname FROM sm_bonus b LEFT JOIN sm_wechat_user u ON b.uid=u.id WHERE".$where;
        $invite_list = M()->query($sql);
        foreach($invite_list as $key=>$invite){
            $invited_list =  M('wechat_user')->where(array('partner_id'=>$partner_id,'inviter_id'=>$invite['uid']))->select();
            
            //被邀请人数
            $invited_num = count($invited_list);
            $invite_list[$key]['invited_list'] = $invited_list;
            $invite_list[$key]['invited_num'] = $invited_num;
            $invited_str = '';
            foreach($invited_list as $k=>$invited){
                $invited_str .= $invited['nickname'].'/'.$invited['id'].',';
            }
            if(!empty($invited_str)){
                $invited_str = substr($invited_str, 0, -1);
            }
            $invite_list[$key]['invited_str'] = $invited_str;

            $where1 = 'type=2 AND partner_id='.$partner_id.' AND uid='.$invite["uid"];
            $where1 .= ' AND create_time>='.$start_time.' AND create_time<'.$end_time;
            //邀请红包数
            $invite_bonus_num = M('bonus')->where($where1)->count();
            $invite_list[$key]['invite_bonus_num'] = $invite_bonus_num;

            $where1 .= ' AND status=1';
            //使用邀请红包数
            $invite_bonus_used_num = M('bonus')->where($where1)->count();
            $invite_list[$key]['invite_bonus_used_num'] = $invite_bonus_used_num;
        }
        return $invite_list;
    }

    public function getBonusActivity($pid,$p)
    {
//        M('bonus_activity')->where(['partner_id'=>$pid])->order('id desc')->select();
        $sql = "select * from sm_bonus_activity where partner_id=$pid order by id desc";
        $data = $this->getPage($sql,$p);
        $helper = WechatHelper::get($pid);
        foreach ($data['data'] as $k=>$v){
            $url = C("BASE_URL").U('Home/Index/bonusActivity',['id'=>$v['id'],'pid'=>$pid]);
            $url = $helper->getUrl($url);
            $data['data'][$k]['url'] = $url;
        }
        return $data;
    }
}