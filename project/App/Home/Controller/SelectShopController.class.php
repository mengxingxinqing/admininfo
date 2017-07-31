<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/7/29 0029
 * Time: 上午 12:53
 */

namespace Home\Controller;
use Base\Service\WechatHelper;
use Home\Service\ShopService;
use Home\Service\UserService;
use Think\Controller;


class SelectShopController extends Controller
{

    public function index(){
        $partner_id =  session('partner_id');
        $uid = session('uid');
        // $list = M('shop')->where(array('partner_id'=>$partner_id,'is_del'=>0))->select();
        $sql = "SELECT s.*,sc.is_pick_point FROM sm_shop s LEFT JOIN sm_shop_conf sc ON s.id=sc.shop_id WHERE s.partner_id=".$partner_id." AND s.is_del=0 AND sc.is_pick_point!=1 ".$limit;
        $list = M()->query($sql);
        if(IS_POST){
            $data['lat'] = I('lat');
            $data['lng'] = I('lng');
            if(empty($data['lat'])) return;
            $list = self::processList($list,$data['lng'],$data['lat']);
            if($partner_id == 10){
                $list = array_slice($list,0,5);
            }

            echo json_encode($list);exit;
        }else{
            $no = I('no',0);
            $this->assign('no',$no);
            if(count($list) == 0){
                $sql = "SELECT s.*,sc.is_pick_point FROM sm_shop s LEFT JOIN sm_shop_conf sc ON s.id=sc.shop_id WHERE s.partner_id=".$partner_id." AND s.is_del=0";
                $list = M()->query($sql);
            }
            if(count($list)==1){
                $shop_id = $list[0]['id'];
                session('shop',$shop_id);
                $user = new UserService();
                $user->selectShop($partner_id,$uid,$shop_id);
                redirect(U("Index/index"));
            }
            if($partner_id == 10){
                $list = array_slice($list,0,5);
            }
            $bgImg = M('partner')->where(['id'=>$partner_id])->getField('select_img');
            $this->assign('bgimg',$bgImg);
            $this->assign('list',$list);
            $this->display();
        }
    }

    private function processList($list,$lng,$lat)
    {
        for ($k=0; $k <count( $list); $k++) {
            $tmp_dis = getDistance($lng,$lat,$list[$k]['lng'],$list[$k]['lat']);
            if($tmp_dis>=1000){
                $distance = round($tmp_dis / 1000);
                $unit = "km";
            } else{
                $distance = $tmp_dis;
                $unit = "m";
            }
            $list[$k]['distance_raw']= $tmp_dis;
            $list[$k]['distance']=$distance.$unit;
            $new[] = $list[$k];
        }

        $list = self::bubble_sort($new,'distance_raw','asc');
        return $list;
    }

    public function bubble_sort($array,$key,$sort) {
        for($i = 0; $i < count($array) - 1; $i++) {    //$i为已经排过序的元素个数
            for($j = 0; $j < count($array) - 1 - $i; $j++) {    //$j为需要排序的元素个数,用总长减去$i
                if($sort == 'asc'){
                    if((float)$array[$j][$key] > (float)$array[$j + 1][$key]) {    //按升序排序
                        $temp = $array[$j];
                        $array[$j] = $array[$j + 1];
                        $array[$j + 1] = $temp;
                    }
                }else{
                    if((float)$array[$j][$key] <= (float)$array[$j + 1][$key]) {    //按升序排序
                        $temp = $array[$j];
                        $array[$j] = $array[$j + 1];
                        $array[$j + 1] = $temp;
                    }
                }

            }
        }
        return $array;
    }



    public function select(){
        $shop_id=I('id');
        $uid=session('uid');
        $pid = session('partner_id');
        session('shop',$shop_id);
        $ser = new UserService();
        $ser->selectShop($pid,$uid,$shop_id);
        echo json_encode(array('status'=>'ok'));
    }

    public function errorpage(){
        $this->display();
    }
}
?>