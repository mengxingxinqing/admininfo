<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-15
 * Time: 11:07
 */

namespace Partner\Controller;


use Partner\Service\OrderService;
use Think\Controller;

class TestController extends Controller
{

    public function index()
    {
        $t1 = microtime(true);
        $url = "http://admin:admin@182.92.116.155:8161/api/message?destination=queue://tong1";
        $data = "body=message".time();
        self::http_post($url,$data);
        $t2 = microtime(true);
        echo '耗时'.round($t2-$t1,3).'秒';
    }


    public function exportOrder(){
        set_time_limit(0);
        $shop_id = I('shop_id',0);
        $partner_id = session('partner_id');
        $day = I('day');

        $where = '';
        if($shop_id != 0){
            $where = ' AND o.shop_id='.$shop_id;
        }

        if(!empty($day)){
            $where .= ' and FROM_UNIXTIME(o.reserve_time,"%Y-%m-%d")>="'.$day.'"';
        }



        $where .= ' AND o.is_base=0 and o.status=3 ';
        $sql = "SELECT o.id,o.sqnum,o.shop_name,o.create_time,o.reserve_time,o.total_money,o.pay_money,o.user_address,o.user_name,o.user_tel,o.sender_name,o.sender_tel,o.status,o.pay_type,o.bonus_money,o.user_remark,o.send_money,o.is_pick,o.pick_address,o.user_buytimes,o.is_base,o.base_id,we.nickname FROM sm_order_bak o LEFT JOIN sm_wechat_user we ON o.uid=we.id WHERE o.partner_id=".$partner_id.' AND o.is_pay=1 '.$where." ORDER BY o.id desc";
        $data['data'] = M()->query($sql);

        foreach($data['data'] as $key=>$order){
            $sql = "SELECT od.goods_name,od.num,od.price FROM sm_order_detail_bak od WHERE od.order_id=".$order['id'];
            $res = M()->query($sql);
            $data['data'][$key]['detail'] = $res;
        }
        $res = $data;

        foreach($res['data'] as $key=>$order){
            $activity = self::getActivity($order['id']);
            $res['data'][$key]['activity'] = $activity;
        }

        $list = $res['data'];

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
            ->setCellValue('A1', '订单')
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


        $name = '订单';
        // 输出
        header('pragma:public');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$name.'.xls"');
        header("Content-Disposition:attachment;filename=$name.xls");//attachment新窗口打印inline本窗口打印
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');

    }


    public function getActivity($order_id){
        $res = M('order_activity')->where(array('oid'=>$order_id))->select();
        foreach($res as $key=>$activity){
            if(!empty($activity['money'])){
                $res[$key]['money'] = -$activity['money'];
            }
        }
        return $res;
    }

    public function yunba(){
        $url = "http://rest.yunba.io:8080";
        $data['method'] = "publish";
        $data['appkey'] = "576b92bd58192f4f2779370d";
        $data['seckey'] = "sec-S2uenbbp8fy9yolWBfqDoMM1tJdyrY34YS7AL8KdcOgRKe8m";
        $data['topic'] = "order";
        $data['msg'] = "101";
        $data_string = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $sContent = curl_exec($ch);
        curl_close($ch);
        var_dump($sContent);
    }


    /**
     * POST 请求
     * @param string $url
     * @param array $param
     * @param boolean $post_file 是否文件上传
     * @return string content
     */
    private function http_post($url,$param,$post_file=false){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param) || $post_file) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach($param as $key=>$val){
                $aPOST[] = $key."=".urlencode($val);
            }
            $strPOST =  join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($oCurl, CURLOPT_POST,true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }

    /**
     * GET 请求
     * @param string $url
     */
    private function http_get($url){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }
}