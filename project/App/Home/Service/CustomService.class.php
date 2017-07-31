<?php
namespace Home\Service;


use Base\Service\WechatHelper;
use Home\Model\GoodsModel;
use Home\Model\ShopModel;
use Home\Model\UserinfoModel;
use Home\Model\OrderModel;

class CustomService
{
    public function fangxinzaocan($order_id){
        $order_model = new OrderModel();
        $order_info = $order_model->getOrderInfo($order_id);
        $order = $order_info['base_info'];
        $order_detail = $order_info['list'];
        // var_dump($order_info);die;
        if($order['pick_id']>0){
            $user_tel = $order['user_tel'];
            $reserve_time = date('Ymd',$order['reserve_time']);
            $create_time = date('YmdHis',$order['create_time']);
            $category_num = count($order_detail);
            $category_num = sprintf('%03d',$category_num);
            $total_money = $order['total_money']*100;
            $total_money = sprintf('%012d',$total_money);
            $pay_money = $order['pay_money']*100;
            $pay_money = sprintf('%012d',$pay_money);
            $order_id = $order['sqnum'];
            foreach($order_detail as $key=>$detail){
                $detail_money = $detail['total_money']*100;
                $detail_money = sprintf('%012d',$detail_money);
                $goods_num = $detail['num'];
                $goods_num = sprintf('%02d',$goods_num);
                // $goods_id = $detail['goods_id'];
                $goods_name = $detail['goods_name'];
                $active_type = '000';
                $active_name = '无活动';
                $active_discount = '000000000000';
                $goods_id = M('base_goods')->where(array('id'=>$detail['basegoods_id']))->getField("attrs");
                if($key > 0){
                    $tmp = '_';
                }else{
                    $tmp = '';
                }
                $detail_str .= $tmp.$goods_num.'_'.$goods_id.'_'.$goods_name.'_'.$detail_money.'_'.$detail_money.'_'.$active_type.'_'.$active_name.'_'.$active_discount.'_'.$active_discount;
            }

            $yan = rand(900000,999999);

            $pick_info = M('pick_building')->where(array('id'=>$order['pick_id']))->find();
            $shanghu_num = $pick_info['param1'];
            $zhongduan_num = $pick_info['param2'];

            //$str = 'WXDD_'.$create_time.'_'.$user_tel.'_'.$category_num.'_'.$total_money.'_'.$pay_money.'_'.$detail_str.'_'.$reserve_time.'_'.$order_id.'_'.$yan.'_898610193990344_00000356';
            $str = 'WXDD_'.$create_time.'_'.$user_tel.'_'.$category_num.'_'.$total_money.'_'.$pay_money.'_'.$detail_str.'_'.$reserve_time.'_'.$order_id.'_'.$yan.'_'.$shanghu_num.'_'.$zhongduan_num;
            $words = iconv("UTF-8","GBK//IGNORE",$str);
            // echo $words;die;
            //本地IP  
            $address = '113.200.189.170';
            //设置用111端口进行通信  
            $port = '16666';

            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            $con=socket_connect($socket,$address,$port);
            if(!$con){socket_close($socket);exit;}

            socket_write($socket,$words);
            socket_shutdown($socket);
            socket_close($socket);
            // echo 'okssss';
        }
    }
}