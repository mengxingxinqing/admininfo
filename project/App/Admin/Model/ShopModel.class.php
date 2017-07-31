<?php
namespace Partner\Model;
use Base\Service\WechatHelper;

/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-05-17
 * Time: 16:32
 */
class ShopModel extends BaseModel
{
    public function add($table,$data){
        return M($table)->add($data);
    }

    public function addShopGoods($shop_id){
        $partner_id = session('partner_id');
        //获得该商户总库中所有商品
        $res = M('base_goods')->where(array('partner_id'=>$partner_id))->select();
        foreach($res as $key=>$goods){

            $shop_category_id = $this->getShopCategoryId($goods['category_id'],$shop_id);

            $goods['category_id'] = $shop_category_id;
            $goods['basegoods_id'] = $goods['id'];
            $goods['shop_id'] = $shop_id;
            unset($goods['id']);
            $shop_goods_id = M('shop_goods')->add($goods);

            //同步商品规格
            $format_res = M('base_goods_format')->where(array('goods_id'=>$goods['basegoods_id']))->select();
            foreach($format_res as $key=>$format){
                $format['goods_id'] = $shop_goods_id;
                unset($format['id']);
                M('shop_goods_format')->add($format);
            }
            //同步商品属性
            $goods_attr_res = M('base_goods_attr')->where(array('goods_id'=>$goods['basegoods_id']))->select();
            foreach($goods_attr_res as $key=>$goods_attr){
                $goods_attr['goods_id'] = $shop_goods_id;
                unset($goods_attr['id']);
                M('shop_goods_attr')->add($goods_attr);
            }
        }
    }

    public function checkShopNum($pid)
    {
        $max = M('partner_conf')->where(array('partner_id'=>$pid))->getField("max_shop_num");
        $count = M('shop')->where(array('partner_id'=>$pid,'is_del'=>0))->count();
        if($count >= $max){
            return false;
        }
        return true;
    }

    public function addShopCategory($shop_id){
        $partner_id = session('partner_id');
        $res = M('base_category')->where(array('partner_id'=>$partner_id))->select();
        foreach($res as $key=>$category){
            $category['base_id'] = $category['id'];
            $category['shop_id'] = $shop_id;
            unset($category['id']);
            M('shop_category')->add($category);
        }
    }

    public function getShopList($p){
    	$sql = "SELECT s.id,s.name,s.address,s.tel,sc.start_time,sc.end_time,sc.is_pick FROM sm_shop s LEFT JOIN sm_shop_conf sc ON s.id=sc.shop_id WHERE s.partner_id=".session('partner_id')." AND s.is_del=0";
        $shop_list = $this->getPage($sql,$p);
        $today = strtotime('today')+86400;
        foreach($shop_list['data'] as $key=>$shop){
            $shop_id = $shop['id'];
            $sql = "SELECT count(*) AS order_count FROM sm_order WHERE shop_id=$shop_id and reserve_time<$today and (status!=0 AND status!=4)";
            $res = M()->query($sql);
            $shop_list['data'][$key]['order_count'] = $res[0]['order_count']; 
        }
    	return $shop_list;
    }

    public function getShopById($id){
    	$sql = "SELECT s.*,sc.start_time,sc.end_time,sc.sendtime_start,sc.sendtime_end,sc.base_price,sc.send_price,sc.send_interval,sc.send_range,sc.bottom_address,sc.show_bottom_address,sc.is_pick,sc.sendcharge_type,sc.reserve_status,sc.reserve_day,sc.fixed_time_status,sc.is_pick_point FROM sm_shop s LEFT JOIN sm_shop_conf sc ON s.id=sc.shop_id WHERE s.id=".$id;
        $res = M()->query($sql);
        return $res[0];
    }

    public function editShop($data){
        $res = M('shop')->where(array('id'=>$data['id']))->save($data);
        return $res;

    }

    public function editShopConf($data){
        $res = M('shop_conf')->where(array('shop_id'=>$data['shop_id'],'partner_id'=>$data['partner_id']))->save($data);
        return $res;
    }

    public function getShopInfoList()
    {
        $pid = session('partner_id');
        $sql = "select s.id,s.name,s.tel,s.status,s.no_send,s.is_close from sm_shop s where s.partner_id=".$pid." and s.is_del=0";
        $list = M()->query($sql);
        $mod = new OrderModel();
        for ($i=0;$i<count($list);$i++){
            $money = $mod->getYesOrderMoney($list[$i]['id']);
            $list[$i]['yesmoney'] = $money;
            $list[$i]['money'] = $mod->getShopTodayMoney($list[$i]['id']);
            $list[$i]['num'] = $mod->getShopTodayNum($list[$i]['id']);
        }
        return $list;
    }

    public function getBaseList(){
        $pid = session('partner_id');
        $sql = "select * from sm_shop s where s.partner_id=".$pid." and s.is_del=0";
        $list = M()->query($sql);
        return $list;
    }

    private function getShopCategoryId($base_id,$shop_id){
        $id = M('shop_category')->where(array('base_id'=>$base_id,'shop_id'=>$shop_id))->getField('id');
        return $id;

    }

    public function getCity($pid)
    {
        $sql = "select a.name from sm_areas a left join sm_partner p on a.id=p.city_id where p.id=$pid";
        $name = self::getOneKey($sql,'name');
        return $name;
    }

    public function deleteShop($shop_id){
        M('shop_goods')->where(array('shop_id'=>$shop_id))->save(array('is_del'=>1)); 
        M('shop_category')->where(array('shop_id'=>$shop_id))->save(array('is_del'=>1));
        M('shop')->where(array('id'=>$shop_id))->save(array('is_del'=>1));
    }

    public function checkAccountRepeat($account,$shop_id=''){
        if($shop_id != ''){
            $where['id'] = array('neq',$shop_id);
        }
        $where['account'] = $account;
        $count = M('shop')->where($where)->count();
        return $count;
    }

    public function closeShop($shop_id,$now_status){
        $new_status = $now_status == 0 ? 1 : 0;
        M('shop')->where(array('id'=>$shop_id))->save(array('is_close'=>$new_status));
    }

    public function noSend($shop_id,$now_status){
        $new_status = $now_status == 0 ? 1 : 0;
        M('shop')->where(array('id'=>$shop_id))->save(array('no_send'=>$new_status));
    }

    public function getShopName($id)
    {
        $info = M('shop')->where(array('id'=>$id))->getField('name');
        return $info;
    }

    public function getShopPayImg($id)
    {
        $shop = M("shop")->where(array('id'=>$id))->field('name,partner_id,shop_pay_img')->find();
        $partner = M('partner')->where(array('id'=>$shop['partner_id']))->getField('name');
        if(empty($shop['shop_pay_img'])){
            $url = C('BASE_URL').U("Home/Share/selectpay",array('pid'=>$shop['partner_id'],'sid'=>$id));
            $qrcode = self::createImg($url,$partner,$shop['name']);
            M("shop")->where(array('id'=>$id))->save(array('shop_pay_img'=>$qrcode));
            return $qrcode;
        }
        return $shop['shop_pay_img'];
    }

    /** 生成到店支付的二维码图片
     * @param $url
     * @param $partner_name
     * @param $shop_name
     * @return string
     */
    public function createImg($url,$partner_name,$shop_name)
    {
        $q_name = time().".png";
        $qrcode = getQrcode($url,"./Public/shoppay/".$q_name,8);
        $header = "./Public/Back/img/header.png";
        $f_header = imagecreatefrompng($header);
        $h_info = getimagesize($header);
        $f_qrcode = imagecreatefrompng($qrcode);
        $q_info = getimagesize($qrcode);
        $src = imagecreatetruecolor($h_info[0], $h_info[1]);
        $red = imagecolorallocate($src, 255, 255, 255);
        imagefill($src, 0, 0, $red);
        $qr_x = ceil($h_info[0]-$q_info[0])/2;
        $qr_y = 160;
        imagecopy($src, $f_header, 0, 0, 0, 0, $h_info[0], $h_info[1]);
        imagecopy($src, $f_qrcode, $qr_x, $qr_y, 0, 0, $q_info[0], $q_info[1]);
        $black = imagecolorallocate($src, 0, 0, 0);
        $text = $partner_name;
        $font = "./Public/Back/font/yahei.ttf";
        $font_size = 50;
        $fontBox = imagettfbbox($font_size, 0, $font, $text);//文字水平居中实质

        $font_x = ceil(($h_info[0] - $fontBox[2]) / 2);
        $font_y = $q_info[1]+$qr_y+$font_size;

        imagettftext($src,$font_size,0,$font_x,$font_y,$black,$font,$text);
        imagedestroy($f_qrcode);
        $font_size2 = 15;
        $text = $shop_name;
        $fontBox2 = imagettfbbox($font_size2, 0, $font, $text);//文字水平居中实质
        $font_x = ceil(($h_info[0] - $fontBox2[2]) / 2);
        $font_y += $font_size2*3;
        imagettftext($src,$font_size2,0,$font_x,$font_y,$black,$font,$text);
        $savePath = "/Public/shoppay/$q_name";
        imagepng($src,'.'.$savePath);
        return $savePath;
    }

    public function getPrintTimes($id)
    {
        $res = M('shop_conf')->where(array('shop_id'=>$id))->getField("print_times");
        return $res;
    }

    public function setBuilding($data){     
        M('building')->where(array('shop_id'=>$data[0]['shop_id']))->delete();
        foreach($data as $key=>$value){
            M('building')->add($value);
        }
    }

    public function deleteBuilding($shop_id){
        M('building')->where(array('shop_id'=>$shop_id))->delete();
    }

    public function getBuildingList($shop_id){
        $res = M('building')->where(array('shop_id'=>$shop_id))->select();
        return $res;
    }

    public function addPickAddress($partner_id,$shop_id,$pick_address){
        // M('pick_building')->where(array('shop_id'=>$shop_id,'is_pick_point'=>0))->delete();
        $count = M('pick_building')->where(array('shop_id'=>$shop_id,'is_pick_point'=>0))->count();
        if($count > 0){
            M('pick_building')->where(array('shop_id'=>$shop_id,'is_pick_point'=>0))->save(array('address'=>$pick_address));
        }else{
            $data['address'] = $pick_address;
            $data['partner_id'] = $partner_id;
            $data['shop_id'] = $shop_id;
            M('pick_building')->add($data);
        }
        
    }

    public function addPickTime($partner_id,$shop_id,$start_time,$end_time,$pick_point_id = 0){
        $time = time();
        M('pick_time')->where(array('shop_id'=>$shop_id,'pick_point_id'=>$pick_point_id))->delete();
        foreach($start_time as $key=>$value){
            if(empty($value)){
                continue;
            }
            $data['partner_id'] = $partner_id;
            $data['shop_id'] = $shop_id;
            $data['pick_start_time'] = strlen($value) == 5 ? $value : '0'.$value;
            $data['pick_end_time']  = strlen($end_time[$key]) == 5 ? $end_time[$key] : '0'.$end_time[$key];
            $data['create_time'] = $time;
            $data['pick_point_id'] = $pick_point_id;
            M('pick_time')->add($data);
        }
    }


    public function getPickAddress($shop_id){
        
        $address = M('pick_building')->where(array('shop_id'=>$shop_id,'is_pick_point'=>0))->find();
        return $address['address'];
    }

    public function getPickTime($shop_id){
        
        $pick_time = M('pick_time')->where(array('shop_id'=>$shop_id,'pick_point_id'=>0))->select();
        return $pick_time;
    }

    public function setBaseSend($shop_id,$data){
        M('shop')->where(array(array('id'=>$shop_id)))->save(array('address'=>$data['address'],'lat'=>$data['lat'],'lng'=>$data['lng']));
        M('shop_conf')->where(array('shop_id'=>$shop_id))->save(array('base_price'=>$data['base_price'],'send_range'=>$data['send_range'],'sendtime_start'=>$data['sendtime_start'],'sendtime_end'=>$data['sendtime_end']));
    }

    //获得商户的抽成比率
    public function getCommissionRate($partner_id,$shop_id){
        if($shop_id == 0){
            $res = M('shop')->where(array('partner_id'=>$partner_id))->find();
            $shop_id = $res['id'];
        }
        $rate = M('shop_conf')->where(array('shop_id'=>$shop_id))->getField('rate');
        return $rate;
    }

    public function addPickPoint($data){
        $data1['partner_id'] = $data['partner_id'];
        $data1['shop_id'] = $data['shop_id'];
        $data1['name'] = $data['name'];
        $data1['address'] = $data['address'];
        $data1['tel'] = $data['tel'];
        $data1['number'] = $data['number'];
        $data1['user_name'] = $data['user_name'];
        $data1['is_pick_point'] = 1;
        $data1['lat'] = $data['lat'];
        $data1['lng'] = $data['lng'];
        $data1['param1'] = $data['param1'];
        $data1['param2'] = $data['param2'];
        $id = M('pick_building')->add($data1);
        if($id){
            self::addPickTime($data['partner_id'],$data['shop_id'],$data['pick_start_time'],$data['pick_end_time'],$id);

            if($data['partner_id'] == 18){
                //模板坐标列表
                $tmp_list = M('map_range')->select();
                foreach($tmp_list as $k=>$tmp){
                    $dis = getDistance($data['lng'],$data['lat'],$tmp['lng'],$tmp['lat']);

                    if($k == 0){
                        $min_dis = $dis;
                        $min_tmp = $tmp;
                    }
                    //找到离自提点最近的模板未知
                    if($dis < $min_dis){
                        $min_dis = $dis;
                        $min_tmp = $tmp;
                    }

                }

                M('pick_building')->where(array('id'=>$id))->save(array('map_range_id'=>$min_tmp['id']));
            }

            return true;
        }else{
            return false;
        }
    }

    public function editPickPoint($data){
        M('pick_building')->where(array('id'=>$data['id']))->save(array('name'=>$data['name'],'address'=>$data['address'],'shop_id'=>$data['shop_id'],'tel'=>$data['tel'],'number'=>$data['number'],'user_name'=>$data['user_name'],'param1'=>$data['param1'],'param2'=>$data['param2'],'lat'=>$data['lat'],'lng'=>$data['lng']));

        if($data['partner_id'] == 18){
            //模板坐标列表
            $tmp_list = M('map_range')->select();
            foreach($tmp_list as $k=>$tmp){
                $dis = getDistance($data['lng'],$data['lat'],$tmp['lng'],$tmp['lat']);

                if($k == 0){
                    $min_dis = $dis;
                    $min_tmp = $tmp;
                }
                //找到离自提点最近的模板未知
                if($dis < $min_dis){
                    $min_dis = $dis;
                    $min_tmp = $tmp;
                }

            }
            M('pick_building')->where(array('id'=>$data['id']))->save(array('map_range_id'=>$min_tmp['id']));
        }

        M('pick_time')->where(array('pick_point_id'=>$data['id']))->save(array('shop_id'=>$data['shop_id']));
        self::addPickTime($data['partner_id'],$data['shop_id'],$data['pick_start_time'],$data['pick_end_time'],$data['id']);
    }

    public function getPickPointById($id){
        $data['info'] = M('pick_building')->where(array('id'=>$id))->find();
        $data['pick_time'] = M('pick_time')->where(array('pick_point_id'=>$id))->select();
        return $data;
    }

    public function getPickPointList($partner_id,$p,$shop_id,$search_key=''){
        $res = M('pick_building')->where(array('partner_id'=>$partner_id,'is_pick_point'=>1))->select();
        $where .= ' partner_id='.$partner_id;
        if($shop_id != 0){
            $where .= ' AND shop_id='.$shop_id;
        }
        $where .= ' AND is_pick_point=1';
        if($search_key != ''){
            $where .= " AND (number = '$search_key' OR `name` like '%$search_key%' OR address like '%$search_key%')";
        }
        $sql = "SELECT * FROM sm_pick_building WHERE ".$where;
        // echo $sql;die;
        $data = $this->getPage($sql,$p);
        $start_time = strtotime('today');
        $end_time = $start_time+86400;
        foreach($data['data'] as $key=>$value){
            $sql = "SELECT COUNT(*) AS count,SUM(pay_money) AS money FROM sm_order WHERE pick_id=".$value['id']." AND status !=0 AND status != 4 AND reserve_time>=".$start_time." AND reserve_time<".$end_time;
            $res = M()->query($sql);
            $data['data'][$key]['count'] = $res['0']['count']; 
            $data['data'][$key]['money'] = empty($res[0]['money']) ? 0 : $res[0]['money'];

        }
        return $data;
    }

    public function deletePickPoint($id){
        $res = M('pick_building')->where(array('id'=>$id))->delete();
        if($res){
            M('pick_time')->where(array('pick_point_id'=>$id))->delete();
            return true;
        }else{
            return false;
        }
    }

    public function getPickQrcode($partner_id,$shop_id,$id,$number){
        $wechat = WechatHelper::get($partner_id);
        $url = C('BASE_URL').U("Home/Index/pointPay",array('pid'=>$partner_id,'spid'=>$id,'j'=>1));
        $url = $wechat->getUrl($url);

        $shop_name = M('shop')->where(array('id'=>$shop_id))->getField('name');
        $big_name .= $shop_name.'  '.$number;
        $file_name = $partner_id.'_'.$shop_id.'_'.$number.'.png';
        $file = self::createImgs($url,$file_name,$big_name,'');
        return $file;
    }


    //自提点二维码
    public function createImgs($url,$file_name,$big_name='',$small_name='')
    {
        $q_name = $file_name;
        $qrcode = getQrcode($url,"./Public/pickPoint/".$q_name,8);
        $header = "./Public/Back/img/pick_bg.png";
        $f_header = imagecreatefrompng($header);
        $h_info = getimagesize($header);
        $f_qrcode = imagecreatefrompng($qrcode);
        $q_info = getimagesize($qrcode);
        $src = imagecreatetruecolor($h_info[0], $h_info[1]);
        $red = imagecolorallocate($src, 255, 255, 255);
        imagefill($src, 0, 0, $red);
        $qr_x = ceil($h_info[0]-$q_info[0])/2;
        $qr_y = 40;
        imagecopy($src, $f_header, 0, 0, 0, 0, $h_info[0], $h_info[1]);
        imagecopy($src, $f_qrcode, $qr_x, $qr_y, 0, 0, $q_info[0], $q_info[1]);
        $black = imagecolorallocate($src, 0, 0, 0);
        $text = $big_name;
        $font = "./Public/Back/font/yahei.ttf";
        $font_size = 40;
        $fontBox = imagettfbbox($font_size, 0, $font, $text);//文字水平居中实质

        $font_x = ceil(($h_info[0] - $fontBox[2]) / 2);
        $font_y = $q_info[1]+$qr_y+$font_size;

        imagettftext($src,$font_size,0,$font_x,$font_y,$black,$font,$text);
        imagedestroy($f_qrcode);
        $font_size2 = 15;
        $text = $small_name;
        $fontBox2 = imagettfbbox($font_size2, 0, $font, $text);//文字水平居中实质
        $font_x = ceil(($h_info[0] - $fontBox2[2]) / 2);
        $font_y += $font_size2*3;
        imagettftext($src,$font_size2,0,$font_x,$font_y,$black,$font,$text);

        $savePath = "/Public/pickPoint/$q_name";
        imagepng($src,'.'.$savePath);
        return $savePath;
    }

    public function setPickPoint($shop_id,$is_pick_point){
        M('shop_conf')->where(array('shop_id'=>$shop_id))->save(array('is_pick_point'=>$is_pick_point));
    }

    public function getPickPointQrcodePayList($p,$point_id){
        $sql = "SELECT urc.*,ua.name AS user_name FROM sm_user_recharge_declog urc LEFT JOIN sm_user_address ua ON urc.uid=ua.uid WHERE urc.point_id=".$point_id." ORDER BY urc.create_time DESC";
        $list = $this->getPage($sql,$p);
        return $list;
    }

    public function getAllPickPointQrcodePay($partner_id,$shop_id){
        $start_time = strtotime('today');
        $end_time = $start_time+86400;
        $sql = "SELECT urc.*,ua.name AS user_name,pb.address AS pick_address,pb.number AS pick_number FROM sm_user_recharge_declog urc LEFT JOIN sm_user_address ua ON urc.uid=ua.uid LEFT JOIN sm_pick_building pb ON urc.point_id=pb.id WHERE urc.type=3 AND urc.partner_id=".$partner_id." AND urc.create_time>=".$start_time." AND urc.create_time<".$end_time;
        $res = M()->query($sql);
        return $res;
    }
}