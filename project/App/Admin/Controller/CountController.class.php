<?php
namespace Partner\Controller;

use Partner\Service\CountService;
use Partner\Service\OrderService;
use Partner\Service\PartnerService;
use Partner\Service\ShopService;
use Think\Controller;

class CountController extends BaseController
{
    //餐品销售占比图
    public function goodsSalesPercent(){
        $service = new OrderService();
        $shop_list = $service->getShopList();
        $shop_id = I('shop_id');
        $partner_id = session('partner_id');
        $count_type = I('count_type',1);
        $start_time = I('start_time');
        $end_time = I('end_time');
        /*if(empty($start_time) || empty($end_time)){
            $start_time = date('m/d/Y',strtotime('today')-7*86400);
            $end_time = date('m/d/Y',strtotime('yesterday'));
        }*/
        $service = new CountService();
        $time = $service->getInitTime($partner_id,$start_time,$end_time);
        $start_time = $time['start_time'];
        $end_time = $time['end_time'];
        $data = $service->goodsSalesPercent($shop_id,$count_type,$start_time,$end_time);
        $this->assign('data',$data);
        $this->assign('start_time',$start_time);        
        $this->assign('end_time',$end_time);
        $this->assign('shop_id',$shop_id);
        $this->assign('count_type',$count_type);
        $this->assign('shop_list',$shop_list);
        $this->display();
    }

    //餐品排行
    public function goodsSalesSort(){
        $shop_id = I('shop_id',0);
        $start_time = I('start_time');
        $end_time = I('end_time');
        $partner_id = session('partner_id');
        $sort = I('sort',1);
        $service = new OrderService();
        $shop_list = $service->getShopList();
        $service = new CountService(); 
        $time = $service->getInitTime($partner_id,$start_time,$end_time);
        $start_time = $time['start_time'];
        $end_time = $time['end_time'];
        $data = $service->goodsSalesSort($partner_id,$shop_id,$start_time,$end_time,$sort);
        $this->assign('data',$data);
        $this->assign('shop_list',$shop_list);
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        $this->assign('shop_id',$shop_id);
        $this->assign('sort',$sort);
        $this->display();
    }

    //订单曲线图
    public function orderAnalysis(){
        $shop_id = I('shop_id');
        $start_time = I('start_time');
        $end_time = I('end_time');
        $partner_id = session('partner_id');

        $order_service = new OrderService();
        $shop_list = $order_service->getShopList();

        $count_service = new CountService();
        $time = $count_service->getInitTime($partner_id,$start_time,$end_time);
        $start_time = $time['start_time'];
        $end_time = $time['end_time'];

        $data = $count_service->orderAnalysis($shop_id,$start_time,$end_time);
        // var_dump($data);die;
        $this->assign('data',$data);
        $this->assign('shop_list',$shop_list);
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        $this->assign('shop_id',$shop_id);
        $this->display();
    }

    //用户数量曲线图
    public function userNum()
    {
        $start_time = I('start_time');
        $end_time = I('end_time');
        $partner_id = session('partner_id');
        $count_service = new CountService();
        $time = $count_service->getInitTime($partner_id,$start_time,$end_time);
        $start_time = $time['start_time'];
        $end_time = $time['end_time'];
        $data = $count_service->userNum($partner_id,$start_time,$end_time);
        // var_dump($data);die;
        $this->assign('data',$data);
        $this->display();
    }

    //用户分析漏斗
    public function userFunnel(){
        $partner_id = session('partner_id');
        $service = new CountService();
        $data = $service->userFunnel($partner_id);
        $this->assign('data',$data);
        $this->display();
    }

    public function billCount(){
        $p = I('p');
        if(!empty(I('date'))){
            $date = I('date');
            if($date == 'today'){
                $start_time = date('Y-m-d',strtotime('today'));
                $end_time = date('Y-m-d',strtotime('today'));
            }elseif($date == 'week'){
                $start_time = date('Y-m-d',strtotime('today')-7*86400);
                $end_time = date('Y-m-d',strtotime('today'));
            }else{
                $start_time = date('Y-m-d',strtotime('today')-30*86400);
                $end_time = date('Y-m-d',strtotime('today'));
            }
        }else{
            $start_time = I('start_time',date('Y-m-d',strtotime('today')));
            $end_time = I('end_time',date('Y-m-d',strtotime('today')));
        }
        
        $partner_id = session('partner_id');
        $shop_id = I('shop_id',0);

        $order_service = new OrderService();
        $shop_list = $order_service->getShopList();

        $history_date = $order_service->getHistoryList(); 

        $partner_id = session('partner_id');
        /*$partner_service = new PartnerService();
        $commission_rate = $partner_service->getCommissionRate($partner_id);*/
        $shop_service = new ShopService();
        $commission_rate = $shop_service->getCommissionRate($partner_id,$shop_id);
        // var_dump($commission_rate);die;

        $count_service = new CountService();

        $list = $count_service->billCount($p,$partner_id,$shop_id,$start_time,$end_time,$commission_rate);
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        $this->assign('shop_id',$shop_id);
        $this->assign('list',$list);
        $this->assign('shop_list',$shop_list);
        $this->assign('history_date',$history_date);
        $this->assign('commission_rate',$commission_rate);
        $this->display();
    }

    public function excelExport(){
        $p = I('p');
        if(!empty(I('history_date'))){
            $date = I('history_date');
            $start_time = date('Y-m-d',strtotime($date));
            $end_time = date('Y-m-d',strtotime(date('Y-m',strtotime($date)+86400*32))-86400);
        }else{
            $start_time = I('start_time',date('Y-m-d',strtotime('today')));
            $end_time = I('end_time',date('Y-m-d',strtotime('today')));
        }
        
        $partner_id = session('partner_id');
        $shop_id = I('shop_id',0);


        $count_service = new CountService();
        $list = $count_service->billCount($p,$partner_id,$shop_id,$start_time,$end_time);


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
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(15);
     

        // 设置行高度
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(30);

        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);

        // 字体和样式
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);
        $objPHPExcel->getActiveSheet()->getStyle('A2:M2')->getFont()->setBold(true);

        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);

        $objPHPExcel->getActiveSheet()->getStyle('A2:M2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A2:M2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        $objPHPExcel->getActiveSheet()->getStyle('A3:M3')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //第三行字体颜色为红色
        $objPHPExcel->getActiveSheet()->getStyle('A3:M3')->getFont()->getColor()->setARGB(\PHPExcel_Style_Color::COLOR_RED);


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
        $objPHPExcel->getActiveSheet()->getStyle('M')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        

        //  合并
        $objPHPExcel->getActiveSheet()->mergeCells('A1:M1');


        $objPHPExcel->getActiveSheet()->getStyle('B:M')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        $objPHPExcel->getActiveSheet()->getStyle('B3:M3')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);


        // 表头
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '商户对账')
            ->setCellValue('A2', '日期')
            ->setCellValue('B2', '营业总额')
            ->setCellValue('C2', '货到付款')
            ->setCellValue('D2', '在线支付')
            ->setCellValue('E2', '餐品原价')
            ->setCellValue('F2', '营销成本')
            ->setCellValue('G2', '配送费')
            ->setCellValue('H2', '味来抽成')
            ->setCellValue('I2', '微信支付抽成')
            ->setCellValue('J2', '银行到账')
            ->setCellValue('K2', '实际总收益')
            ->setCellValue('L2', '充值费用')
            ->setCellValue('M2', '催单话费');
            


        //
        for($i = 0; $i < count($list); $i++){
            $objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 3), $list[$i]['date']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 3), $list[$i]['pay_money']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 3), $list[$i]['offline_money']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 3), $list[$i]['online_money']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 3), $list[$i]['original_money']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F' . ($i + 3), $list[$i]['action_money']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('G' . ($i + 3), $list[$i]['send_money']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('H' . ($i + 3), $list[$i]['weilai']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('I' . ($i + 3), $list[$i]['weixin']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('J' . ($i + 3), $list[$i]['bank']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('K' . ($i + 3), $list[$i]['real_money']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('L' . ($i + 3), $list[$i]['recharge']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('M' . ($i + 3), $list[$i]['phone_money']);
            $objPHPExcel->getActiveSheet()->getStyle('A' . ($i + 3) . ':M' . ($i + 3))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A' . ($i + 3) . ':M' . ($i + 3))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $objPHPExcel->getActiveSheet()->getRowDimension($i + 3)->setRowHeight(16);
        }

        // Rename sheet
        $objPHPExcel->getActiveSheet()->setTitle('商户账单');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);


        $name = $start_time.'-'.$end_time.'商户对账';
        // 输出
        header('pragma:public');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$name.'.xls"');
        header("Content-Disposition:attachment;filename=$name.xls");//attachment新窗口打印inline本窗口打印
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        
    }

    public function exportOrderAnalysisData(){
        $shop_id = I('shop_id',0);
        $start_time = I('start_time');
        $end_time = I('end_time');
        $service = new CountService;
        $list = $service->getOrderAnalysisData($shop_id,$start_time,$end_time);
        // var_dump($list);die; 
        $header = array('datepos'=>'日期','total_money'=>'订单流水','total_num'=>'订单数量');
        $title = $start_time.'-'.$end_time.'营业额统计';
        $service->exportExcelTpl($title,$title,$header,$list);
    }

    public function exportGoodsSalesSortData(){
        $shop_id = I('shop_id',0);
        $start_time = I('start_time');
        $end_time = I('end_time');
        $partner_id = session('partner_id');
        $sort = I('sort',1);
        $service = new CountService();
        $data = $service->goodsSalesSort($partner_id,$shop_id,$start_time,$end_time,$sort);
        $header = array('goods_name'=>'商品名','sales_money'=>'流水','sales_num'=>'销售量');
        $title = $start_time.'-'.$end_time.'商品销量统计';
        $service->exportExcelTpl($title,$title,$header,$data);
    }

}