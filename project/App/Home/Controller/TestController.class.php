<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-07-01
 * Time: 10:54
 */

namespace Home\Controller;


use Home\Model\TimeGoodsModel;
use O2OSender\DaDaSdk;
use O2OSender\FenMiaoSdk;
use O2OSender\UuPtSdk;
use Partner\Model\ShopModel;
use Partner\Service\UserService;
use Think\Controller;

class TestController extends Controller
{
    public function index()
    {
        $arr['total_price'] = 100;
        $arr['total_num'] = 200;
        $arr['list'] = array(
            'id_15'=>array(
                'name'=>'zhang',
                'price'=>'5'
            ),
            'id_22'=>array(
                'name'=>'liu',
                'price'=>'10',
                '1_微辣'=>array(
                    'name'=>'weila',
                    'price'=>'22'
                )
            )
        );
        $arr['total_price'] = 100;
        $arr['total_price'] = 100;
        echo json_encode($arr);exit;

//        $id = I('id');
//        $type = I('t');
//        $sdk = new DaDaSdk("dada5e960fe94d5db01","b70ab2c63721bb739ac92eb1e03ed068","73753","11047059");
//        $data['order_id'] = $id;
//        if($type == 'finish'){
//            var_dump($sdk->finishOrder($data));
//        }elseif($type == "accept"){
//            var_dump($sdk->acceptOrder($data));
//        }elseif($type == "fetch"){
//            var_dump($sdk->fetchOrder($data));
//        }elseif($type == "cancel"){
//            var_dump($sdk->cancelOrder($data));
//        }elseif($type == "formalCancel"){
//            var_dump($sdk->formalCancel($data));
//        }
//        $sdk->cancelReasons();
//        var_dump($_SERVER);
//        var_dump($_SERVER['HTTPS']);
//        echo $_SERVER['SERVER_PORT'];
//        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
//        echo $protocol;
//        $sdk = new UuPtSdk("aad111f0b3da49dcba90ab83befd3206","764ed8693f654321b649c7c9d50cfb37");
//        $t = I('t');
//        if($t == 1){
//            $res = $sdk->sendSms("13572420121");
//            var_dump($res);
//        }else{
//            $res = $sdk->getOpenid("13572420121","9064");
//            var_dump($res);
//        }
    }

    public function callback()
    {
        $content = file_get_contents("php://input");
        \Think\Log::write("dada-content=".$content);
    }

    public function getUserById(){
    	$res = M('wechat_user')->where(array('id'=>60))->select();
        echo '<pre>';
    	var_dump($res);die;

        //shop_conf
        /*$sql = "SELECT pc.*,p.sendcharge_type FROM sm_partner p LEFT JOIN sm_partner_conf pc ON p.id=pc.partner_id";
        $res = M()->query($sql);
        foreach($res as $key=>$value){
            $data['reserve_status'] = $value['reserve_status'];
            $data['reserve_day'] = $value['reserve_day'];
            $data['fixed_time_status'] = $value['fixed_time_status'];
            $data['sendcharge_type'] = $value['sendcharge_type'];
            M('shop_conf')->where(array('partner_id'=>$value['partner_id']))->save($data);
        }
        echo 222;die;*/

        //sendcharge

        /*$res = M('sendcharge')->select();
        foreach($res as $key=>$value){
            $shop_list = M('shop')->where(array('partner_id'=>$value['partner_id']))->select();
            foreach($shop_list as $k=>$shop){
                $data['partner_id'] = $value['partner_id'];
                $data['charge_type'] = $value['charge_type'];
                $data['send_price'] = $value['send_price'];
                $data['send_range_start'] = $value['send_range_start'];
                $data['send_range_end'] = $value['send_range_end'];
                $data['send_interval'] = $value['send_interval'];
                $data['shop_id'] = $shop['id'];
                M('sendcharge')->add($data);
            }

        }
        M('sendcharge')->where(array('shop_id'=>0))->delete();
        echo 333;die;*/


        //resrve_conf
        /*$res = M('reserve_conf')->select();
        foreach($res as $key=>$value){
            $shop_list = M('shop')->where(array('partner_id'=>$value['partner_id']))->select();
            foreach($shop_list as $k=>$shop){
                $data['partner_id'] = $value['partner_id'];
                $data['shop_id'] = $shop['id'];
                $data['pre_time'] = $value['pre_time'];
                $data['buy_time'] = $value['buy_time'];
                $data['create_time'] = $value['create_time'];
                M('reserve_conf')->add($data);
            }
        }
        M('reserve_conf')->where('shop_id is null')->delete();
        echo 555;die;*/
    }

     public function update(){
        $id = I('id');
        $sort = I('sort');
        M('activity_type')->where(array('id'=>$id))->save(array('sort'=>$sort));
    }

    public function test(){
        echo getJsTicket(8);
    }

    public function goodsSort(){
        $partner_list = M('partner')->select();
        foreach($partner_list as $partner){
            $category_list = M('base_category')->where(array('partner_id'=>$partner['id']))->select();
            foreach($category_list as $category){
                $goods_list = M('base_goods')->where(array('category_id'=>$category['id'],'partner_id'=>$partner['id'],'is_del'=>0))->select();
                foreach($goods_list as $key=>$goods){
                    M('base_goods')->where(array('id'=>$goods['id']))->save(array('sort'=>$key+1));
                    M('shop_goods')->where(array('basegoods_id'=>$goods['id']))->save(array('sort'=>$key+1));
                }
            }
        }

    }

    public function payType(){
        $sql = "SELECT * FROM sm_shop_conf GROUP BY partner_id";
        $shop_list = M()->query($sql);
        foreach ($shop_list as $shop) {
            M('partner')->where(array('id'=>$shop['partner_id']))->save(array('pay_type'=>$shop['pay_type'],'is_order_auto'=>$shop['is_order_auto']));
        }
    }

    public function uploadImg(){
       /*$sql = "UPDATE sm_base_goods SET simg = img";
       M()->execute($sql);
       $sql = "UPDATE sm_shop_goods SET simg = img";
       M()->execute($sql);*/

        $res = M('base_goods')->select();
        foreach($res as $key=>$value){
            /*$file_name = $value['id'].'.jpg';
            $ossName = ossUploadImg($file_name,C('BASE_PATH').$value['simg']);
            var_dump($ossName);die;
            if($ossName){
                M('base_goods')->where(array('id'=>$value['id']))->save(array('img'=>$ossName));
                M('shop_goods')->where(array('basegoods_id'=>$value['id']))->save(array('img'=>$ossName));
            }*/
            $old = C('BASE_PATH').$value['simg'];
            $new = C('BASE_PATH').dirname($value['simg']).'/goods_'.$value['id'].'.jpg';
            rename($old,$new);
            M('base_goods')->where(array('id'=>$value['id']))->save(array('img'=>'/goods_'.$value['id'].'.jpg'));
            M('shop_goods')->where(array('basegoods_id'=>$value['id']))->save(array('img'=>'goods_'.$value['id'].'.jpg'));
        }

    }

    public function sendbonus()
    {
//        $v = I('v');
//        if($v != '1'){
//            echo 1;exit;
//        }
//        $list = M('wechat_user')->where(array('partner_id'=>13))->field('id')->select();
//        $user = new UserService();
//        foreach ($list as $k=>$v){
//            $user->sendBonus($v['id'],10,0,7);
//            echo M()->getLastSql()."<br>";
//        }
    }

    public function initMonthLog(){
        $v = I('v');
        if($v != 1){
            echo 123;exit;
        }
        $list = M('shop')->where(array('is_del'=>0))->select();
        $marr = array();
        $marr[] = 8;
        $marr[] = 9;

        foreach ($list as $k=>$v){
            $pconf = M('partner_conf')->where(array('partner_id'=>$v['partner_id']))->find();
            foreach ($marr as $m){
                $data['partner_id'] = $v['partner_id'];
                $data['shop_id'] = $v['id'];
                $data['months'] = $m;
                $data['rate'] = $pconf['rate'];
                $data['top_money'] = $pconf['top_money'];
                $data['inc_money'] = self::inc_money($v['id'],$m);
                $data['back_inc_money'] = self::back_inc_money($v['id'],$m);
                $data['dec_phone_money'] = self::dec_phone_money($v['id'],$m);
                $data['dec_money'] = self::dec_money($v['id'],$m);
                M('month_paylog')->add($data);
            }
        }
    }

    private function getMoney($sid,$m){

    }

    private function inc_money($sid,$m)
    {
        $sql = "select sum(money) as money from sm_partner_paylog WHERE from_unixtime(create_time,'%m')=$m and shop_id=$sid and status=0 and type=1";
        echo $sql.'<br>';
        $res = M()->query($sql);
        if(empty($res[0]['money'])){
            $res[0]['money'] = 0;
        }
        return $res[0]['money'];
    }

    private function back_inc_money($sid,$m){
        $sql = "select sum(money) as money from sm_partner_paylog WHERE from_unixtime(create_time,'%m')=$m and shop_id=$sid and status=0 and type=2";
        echo $sql.'<br>';
        $res = M()->query($sql);
        if(empty($res[0]['money'])){
            $res[0]['money'] = 0;
        }
        return $res[0]['money'];
    }

    private function dec_money($sid, $m)
    {
        $sql = "select sum(money) as money from sm_partner_declog WHERE from_unixtime(create_time,'%m')=$m and shop_id=$sid and status=0 and type=0";
        echo $sql.'<br>';
        $res = M()->query($sql);
        if(empty($res[0]['money'])){
            $res[0]['money'] = 0;
        }
        return $res[0]['money'];
    }

    private function dec_phone_money($sid, $m)
    {
        $sql = "select sum(money) as money from sm_partner_declog WHERE from_unixtime(create_time,'%m')=$m and shop_id=$sid and status=0 and type=1";
        echo $sql.'<br>';
        $res = M()->query($sql);
        if(empty($res[0]['money'])){
            $res[0]['money'] = 0;
        }
        return $res[0]['money'];
    }

    public function testRedis()
    {
        $type = I('t');
        $key = I('k');
        $val = I('v');
        $redis =new \Redis();
        $redis->connect('127.0.0.1',6379);
        if($type == 'set'){
            $redis->set($key,$val);
            echo "success";die;
        }else{
            echo $redis->get($key);die;
        }

    }

    public function testFenmiao()
    {
        $key = '6WBP0wOHtlZ10MUitlP41NBy5PTbuoMC';
        $json['customerName'] = "张三";
        $json['customerPhone'] = "13572420121";

        $json['latitude'] = '34.3326192881';
        $json['longitude'] = '108.9425383899';
        $json['address'] = "陕西省西安市未央区未央路";
        $json['addressDetail'] = "xx路XX街道";
        $json['sourceCode'] = 1;
        $json['timeStatus'] = 1;
        $json['productType'] = 4;
        $fenmiao = new FenMiaoSdk();
        $res = $fenmiao->addOrder($key,$json);
        var_dump($res);
    }
    public function testFm(){
        $fenmiao = new FenMiaoSdk();
        $key = '6WBP0wOHtlZ10MUitlP41NBy5PTbuoMC';
        $fenmiao->cancelOrder($key,1);
    }

    public function testpay()
    {

    }

    public function getJsParam()
    {
        vendor('Wxpay.WxPayData');
        vendor('Wxpay.WxPayApi');
        vendor('Wxpay.WxPayConfig');
        vendor('Wxpay.WxPayJs');

        $orderid = "11111100";
        $order_num = "1";
        $money = 2;
        $sub_openid = "";
        $mch_id = "1397166802";
        $sub_mch_id = "1453252302";
        $appid = "wxa6d6c9047a20054c";
        $sub_appid = "wxf456e78b986ce33f";
        if(empty($data['notify_url'])){
            $data['notify_url'] = C('BASE_URL').U("Home/Test/notify",array('id'=>$orderid));
        }
        $input = new \WxPayUnifiedOrderFuWu();
        $input->SetBody("商品付款");
        $input->SetAttach("商品支付");
        $input->SetOut_trade_no($orderid);
        $input->SetTotal_fee($money*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($this->conf['name']."商品支付");
        $input->SetNotify_url($data['notify_url']);
        $input->SetTrade_type("JSAPI");
//        $input->SetOpenid($openid);
        $input->SetAppid($appid);
        $input->SetMch_id($mch_id);
        $input->SetProduct_id($order_num);
        $input->SebSub_mch_id($sub_mch_id);
        $input->SetSub_appid($sub_appid);
        $input->SetSub_openid($sub_openid);

        \Think\Log::write(" input data = ".json_encode($input));
        try{
            $order = \WxPayApi::unifiedOrder($input,$data['sslcert_path'],$data['sslkey_path']);
        }catch (\WxPayException $e){
            \Think\Log::write('wxpay-unifiedOrder '.$e->errorMessage());
        }
        $tools = new \JsApiPay();
        try {
            $jsApiParameters = $tools->GetJsApiParameters($order);
        }catch (\WxPayException $e){
            \Think\Log::write('wxpay-GetJsApiParameters '.$e->errorMessage().' --- '.json_encode($order));
        }
        return $jsApiParameters;
    }


}