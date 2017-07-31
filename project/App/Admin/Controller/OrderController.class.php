<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-05-25
 * Time: 10:29
 */

namespace Partner\Controller;

use Api\Service\SendService;
use Partner\Service\OrderService;
use Partner\Service\PartnerService;
use Think\Controller;
use Base\Service\WechatHelper;

class OrderController extends BaseController
{
    public function index(){
    	$p = I('p');
    	$shop_id = I('shop_id',0);
        $partner_id = session('partner_id');
    	$search_key = I('search_key');
        $pick_id = I('pick_id',0);
    	$service = new OrderService();

        //有预定的时候能够接到该参数reserve_time
        $is_reserve = $service->partnerReserveStatus($partner_id,$shop_id);
        $reserve_time = I('reserve_time',date('Y-m-d',strtotime('today')));
        $list = $service->getOrderList($p,$partner_id,$shop_id,$search_key,$reserve_time,$is_reserve,$pick_id);
        $shop_list = $service->getShopList();
        $partner_service = new PartnerService();
        $isset_week = $partner_service->isSetWeek($partner_id);
        $is_pcprint = $partner_service->isPcPrint($partner_id);
        $this->assign('is_pcprint',$is_pcprint);
        $this->assign('isset_week',$isset_week);
        $this->assign('list',$list);
        $this->assign('shop_list',$shop_list);
        $this->assign('shop_id',$shop_id);
        $this->assign('search_key',$search_key);
        $this->assign('pick_id',$pick_id);
        //是否开启第三方配送
        $sendser = new SendService();
        $isOpenThird = $sendser->isOpenThird($partner_id);
        $this->assign('isOpenThird',$isOpenThird);
        //是否预定
        // $is_reserve = $service->partnerReserveStatus($partner_id,$shop_id);
        $this->assign('is_reserve',$is_reserve);
        if($is_reserve){
            $days = $service->getReserveDays($partner_id,$shop_id);
            $order_count = $service->getOrderCount($partner_id,$shop_id);
            $this->assign('today',date('Y-m-d',strtotime('today')));
            $this->assign('reserve_time',$reserve_time);
            $this->assign('days',$days);
            $this->assign('order_count',$order_count);
            $this->display('reserveOrder');
        }else{
            $this->display('reserveOrder');
            // $this->display();
        }
    }

    public function bakOrder(){
        $p = I('p');
        $shop_id = I('shop_id',0);
        $search_key = I('search_key');
        $start_time = I('start_time');
        $end_time = I('end_time');
        $status = I('status');
        $partner_id = session('partner_id');
        $service = new OrderService();
        $list = $service->getBakOrderList($p,$shop_id,$search_key,$start_time,$end_time,$status);

        $shop_list = $service->getShopList();


        $partner_service = new PartnerService();
        $isset_week = $partner_service->isSetWeek($partner_id);
        $this->assign('isset_week',$isset_week);

        $this->assign('list',$list);
        $this->assign('shop_list',$shop_list);
        $this->assign('shop_id',$shop_id);
        $this->assign('search_key',$search_key);
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        $this->assign('status',$status);
        $this->display('bakOrderTable');
        // $this->display();
    }

    public function changeOrderStatus(){
        $status = I('status');
        $order_id = I('order_id');
        $sendtype = I('sendtype');
        $sid = I('shop_id');
        $pid = $this->partner_id;
        $service = new OrderService();

        // $res = $service->changeOrderStatus($order_id,$status);
        if($status == 4){
            $refund_res = $service->refund($this->partner_id,$order_id);
            if($refund_res['result_code'] == 'SUCCESS'){
                $res = $service->changeOrderStatus($order_id,$status);
            }
            echo json_encode($refund_res);
        }else{
            $res = $service->changeOrderStatus($order_id,$status);
        }
        if(!empty($sendtype)){
            $res = $service->changeSendType($pid,$sid,$order_id,$sendtype);
            echo $res;exit;
        }
    }

    public function giveUpSender()
    {
        $id = I('id');
        $ser = new SendService();
        $res = $ser->giveUpOrder($id);
        if($res['code'] == 0){
            $oser = new OrderService();
            $oser->giveUpSend($id);
            echo "操作成功";
        }else{
            echo $res['msg'];
        }
        exit;
    }

    public function changeBakOrderStatus(){
        $status = I('status');
        $order_id = I('order_id');
        $service = new OrderService();
        // $res = $service->changeOrderStatus($order_id,$status,'order_bak');
        if($status == 4){
            $refund_res = $service->refund($this->partner_id,$order_id,'order_bak');
            if($refund_res['result_code'] == 'SUCCESS'){
                $res = $service->changeOrderStatus($order_id,$status,'order_bak');
            }
            echo json_encode($refund_res);
        }else{
            $res = $service->changeOrderStatus($order_id,$status,'order_bak');
        }
    }

    public function getSendList()
    {
        $partner_id = session('partner_id');
        $shop_id = I('shop_id');
        $order_id = I('order_id');
        $ser = new SendService();
//        $list = $ser->getList($shop_id);
        $list = $ser->getAllSendPrice($partner_id,$shop_id,$order_id);
        echo json_encode($list);exit;
    }

    public function refundList(){
        $p = I('p');
        $partner_id = session('partner_id');
        $service = new OrderService();
        $res = $service->getRefundList($p,$partner_id);
        $this->assign('data',$res);
        $this->display();
    }

    public function userOrder(){
        $p = I('p');
        $uid = I('uid');
        $service = new OrderService();
        $list = $service->getOrderListByUid($p,$uid);
        $this->assign('list',$list);
        $this->display();
    }

    public function shoppayOrder()
    {
        $p = I('p',1);
        $shop_id = I('shop_id',0);
        $this->assign('shop_id',$shop_id);
        $partner_id = session('partner_id');
        $start_time = I('start_time');
        $this->assign('start_time',$start_time);
        $end_time = I('end_time');
        $this->assign('end_time',$end_time);
        $service = new OrderService();
        $res = $service->shoppayOrder($partner_id,$p,$shop_id,$start_time,$end_time);
        $shop_list = $service->getShopList();
        $this->assign('shop_list',$shop_list);
        $this->assign('data',$res);
        $this->display();

    }

    public function orderRealTimeCount(){
        $partner_id = session('partner_id');
        $service = new OrderService();
        $res = $service->orderRealTimeCount($partner_id);
        $this->assign('list',$res);
        $this->display();
    }

    public function baseWeekOrder(){
        $partner_id = session('partner_id');
        $p = I('p');
        $shop_id = I('shop_id',0);
        $search_key = I('search_key');
        $reserve_time = I('reserve_time',date('Y-m-d',strtotime('today')));

        $service = new OrderService();
        $shop_list = $service->getShopList();
        $list = $service->getBaseWeekOrderList($p,$partner_id,$shop_id,$search_key,$reserve_time);
        $this->assign('shop_list',$shop_list);
        $this->assign('list',$list);
        $this->assign('shop_id',$shop_id);
        $this->assign('search_key',$search_key);
        $this->display();
    }

    public function setRemark()
    {
        $id = I('id');
        $remark = I('remark');
        $ser = new OrderService();
        $ser->setRemark($id,$remark);
    }

    public function pcPrint()
    {
        $id = I('id');
        $ser = new OrderService();
        $sid = $ser->getLineKeyById('order',$id,'shop_id');
        $list = S("UnReadList_".$sid);
        if(empty($list)){
            $list = array();
        }
        $list[] = $id;
        S("UnReadList_".$sid,$list);
    }


    public function bakBaseWeekOrder(){
        /*$partner_id = session('partner_id');
        $p = I('p');
        $shop_id = I('shop_id',0);
        $search_key = I('search_key');
        $reserve_time = I('reserve_time',date('Y-m-d',strtotime('today')));

        $service = new OrderService();
        $shop_list = $service->getShopList();
        $list = $service->getBakBaseWeekOrderList($p,$partner_id,$shop_id,$search_key,$reserve_time);
        $this->assign('shop_list',$shop_list);
        $this->assign('list',$list);
        $this->assign('shop_id',$shop_id);
        $this->assign('search_key',$search_key);
        $this->display();
*/

        $p = I('p');
        $shop_id = I('shop_id',0);
        $search_key = I('search_key');
        $start_time = I('start_time');
        $end_time = I('end_time');
        $status = I('status');
        $service = new OrderService();
        $list = $service->getBakBaseWeekOrderList($p,$shop_id,$search_key,$start_time,$end_time,$status);

        $shop_list = $service->getShopList();
        $this->assign('list',$list);
        $this->assign('shop_list',$shop_list);
        $this->assign('shop_id',$shop_id);
        $this->assign('search_key',$search_key);
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        $this->assign('status',$status);
        $this->display();
    }

    public function exportOrder(){
        $p = I('p');
        $shop_id = I('shop_id',0);
        $partner_id = session('partner_id');
        $search_key = I('search_key');
        $service = new OrderService();

        //有预定的时候能够接到该参数reserve_time
        $reserve_time = I('reserve_time',date('Y-m-d',strtotime('today')));
        $list = $service->getAllOrderList($partner_id,$shop_id,$search_key,$reserve_time);
        $order_status = array('未接单','已接单','配送中','完成','取消');
        // var_dump($list[2]);die;
        Vendor("Classes.PHPExcel");

        // Create new PHPExcel object
        $objPHPExcel = new \PHPExcel();

        // set width
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(15);
     

        // 设置行高度
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(30);

        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);

        // 字体和样式
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);
        $objPHPExcel->getActiveSheet()->getStyle('A2:L2')->getFont()->setBold(true);

        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);

        $objPHPExcel->getActiveSheet()->getStyle('A2:L2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A2:L2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        $objPHPExcel->getActiveSheet()->getStyle('A3:L3')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        



        // 设置水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('J')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('K')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('L')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        // $objPHPExcel->getActiveSheet()->getStyle('M')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //设置换行
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setWrapText(true);
        $objPHPExcel->getActiveSheet()->getStyle('H')->getAlignment()->setWrapText(true);

        //设置数字格式
        $objPHPExcel->getActiveSheet()->getStyle('G3')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        $objPHPExcel->getActiveSheet()->getStyle('G')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

        //  合并
        $objPHPExcel->getActiveSheet()->mergeCells('A1:L1');


        // 表头
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '今日订单'.$reserve_time)
            ->setCellValue('A2', '订单号')
            ->setCellValue('B2', '店名')
            ->setCellValue('C2', '下单时间')
            ->setCellValue('D2', '订单详情')
            ->setCellValue('E2', '送达时间')
            ->setCellValue('F2', '备注')
            ->setCellValue('G2', '订单金额')
            ->setCellValue('H2', '参与活动')
            ->setCellValue('I2', '姓名')
            ->setCellValue('J2', '电话')
            ->setCellValue('K2', '地址')
            // ->setCellValue('L2', '下单次数')
            ->setCellValue('L2', '状态');
            

        //渲染数据
        for($i = 0; $i < count($list); $i++){
            $objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 3), $list[$i]['sqnum']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 3), $list[$i]['shop_name']);
            $create_time = date('Y-m-d H:i',$list[$i]['create_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 3), ' '.$create_time);

            $detail_str = '';
            foreach($list[$i]['detail'] as $detail){
                $detail_str .= $detail['goods_name'].'  x  '.$detail['num'].'   '.$detail['price']."\n";
            }
            $objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 3), $detail_str);
            $reserve_time = date('Y-m-d H:i',$list[$i]['reserve_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 3), ' '.$reserve_time);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F' . ($i + 3), $list[$i]['user_remark']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G' . ($i + 3), $list[$i]['pay_money']);

            $activity_str = '';
            foreach($list[$i]['activity'] as $activity){
                $activity_str .= $activity['name']."\n"; 
            }

            $objPHPExcel->getActiveSheet(0)->setCellValue('H' . ($i + 3), $activity_str);
            $objPHPExcel->getActiveSheet(0)->setCellValue('I' . ($i + 3), $list[$i]['user_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('J' . ($i + 3), $list[$i]['user_tel']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K' . ($i + 3), $list[$i]['user_address']);
            // $objPHPExcel->getActiveSheet(0)->setCellValue('L' . ($i + 3), $list[$i]['user_buytimes']);


            $objPHPExcel->getActiveSheet(0)->setCellValue('L' . ($i + 3), $order_status[$list[$i]['status']]);
            $objPHPExcel->getActiveSheet()->getStyle('A' . ($i + 3) . ':L' . ($i + 3))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A' . ($i + 3) . ':L' . ($i + 3))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $objPHPExcel->getActiveSheet()->getRowDimension($i + 3)->setRowHeight(16);
        }

        // Rename sheet
        $objPHPExcel->getActiveSheet()->setTitle('今日订单');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);


        $name = '今日订单'.$reserve_time;
        // 输出
        header('pragma:public');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$name.'.xls"');
        header("Content-Disposition:attachment;filename=$name.xls");//attachment新窗口打印inline本窗口打印
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');

    }
}