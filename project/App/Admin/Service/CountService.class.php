<?php
namespace Partner\Service;
use Partner\Model\CountModel;

class CountService extends BaseService{
	public function goodsSalesPercent($shop_id,$count_type,$start_time,$end_time){
        $partner_id = session('partner_id');
        $res = S('goodsSalesPercent_'.$partner_id.'_'.$shop_id.'_'.$count_type.'_'.$start_time.'_'.$end_time);
        $res = '';
        if(empty($res)){
            $model = new CountModel();
            $res = $model->goodsSalesPercent($shop_id,$count_type,$start_time,$end_time);
            S('goodsSalesPercent_'.$partner_id.'_'.$shop_id.'_'.$count_type.'_'.$start_time.'_'.$end_time,$res,TIME_ONE_HOUR);
        }
		
		return json_encode($res);
    }

    //店铺回头客
    public function partnerCompare($shop_id,$start_time,$end_time){
        $model = new CountModel();
        
        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
        //全部订单数据
        $allOrderCount = $model->allOrderCount($shop_id,$start_time,$end_time);
        // var_dump($allOrderCount);die;
        //新用户订单数量
        $newOrderCount = $model->newOrderCount($shop_id,$start_time,$end_time);
        // var_dump($newOrderCount);die;
        foreach($allOrderCount as $key=>$total){
            //老用户订单率
            foreach($newOrderCount as $k=>$new){
                if($new['shop_id'] == $total['shop_id']){
                    $allOrderCount[$key]['old_count'] = $total['order_count'] - $new['order_count'];
                    $allOrderCount[$key]['old_percent'] = round(($allOrderCount[$key]['old_count']/$total['order_count'])*100,2)."%";
                    break;
                }else{
                    $allOrderCount[$key]['old_count'] = $total['order_count'];
                    $allOrderCount[$key]['old_percent'] = '100%';
                }
            }
            /*//整体回头率
            $csql = "select (count(nullif(t.times,1))/count(t.times))*100 as totalback  from (SELECT count(*) as times FROM `tm_order_bak` o WHERE o.`partner_id` =".$new['partner_id']." GROUP BY o.`uid` ) t";
            $cdate = M()->query($csql);
            $allOrderCount[$key]['total_back'] = $cdate[0]['totalback'];*/

            //2周内老用户订单
            //$day为循环的天数
            $day = ($end_time-$start_time)/86400;
            $start_time2 = $start_time;
            $end_time2 = $start_time+86400;
            $two_week_order = 0;//2周内回头订单数量
            while($day>0){
                $counts = $model->twoWeekOrder($start_time2,$end_time2,$total['shop_id']);
                $start_time2 += 86400;
                $end_time2 += 86400;
                $day--;
                $two_week_order += $counts;
            }
            $allOrderCount[$key]['two_week_order'] = $two_week_order;
            $allOrderCount[$key]['two_week_order_percent'] = round($two_week_order/$total['order_count']*100,2)."%";
        }
        return $allOrderCount;
    }

    //商品销量排行
    public function goodsSalesSort($partner_id,$shop_id,$start_time,$end_time,$sort){
        $res = S('goodsSalesSort_'.$partner_id.'_'.$shop_id.'_'.$start_time.'_'.$end_time.'_'.$sort);
        if(empty($res)){
            $model = new CountModel();
            $res = $model->goodsSalesSort($partner_id,$shop_id,$start_time,$end_time,$sort);
            S('goodsSalesSort_'.$partner_id.'_'.$shop_id.'_'.$start_time.'_'.$end_time.'_'.$sort,$res,TIME_ONE_HOUR);
        }
        
        return $res;
    }


    //订单分析
    public function orderAnalysis($shop_id,$start_time,$end_time){
        $partner_id = session('partner_id');
        $data = S('orderAnalysis_'.$partner_id.'_'.$shop_id.'_'.$start_time.'_'.$end_time);
        if(empty($data)){
            $model = new CountModel();
            if($shop_id == 0){
                $res = $model->orderAnalysisByPartner($start_time,$end_time);
            }else{
                $res = $model->orderAnalysisByShop($shop_id,$start_time,$end_time);
            }

            $data['total_money'] = array();
            $data['total_num'] = array();
            $data['avg_money'] = array();
            $data['old_money'] = array();
            $data['datepos'] = array();
            foreach ($res as $key => $value) {
                $data['total_money'][] = $value['total_money'];
                $data['total_num'][] = $value['total_num'];
                $data['avg_money'][] = $value['avg_money'];
                $data['old_money'][] = $value['old_money'];
                $data['datepos'][] = $value['datepos'];
            }
            $data['total_money'] = json_encode($data['total_money']);
            $data['total_num'] = json_encode($data['total_num']);
            $data['avg_money'] = json_encode($data['avg_money']);
            $data['old_money'] = json_encode($data['old_money']);
            $data['datepos'] = json_encode($data['datepos']);
            S('orderAnalysis_'.$partner_id.'_'.$shop_id.'_'.$start_time.'_'.$end_time,$data,TIME_ONE_HOUR);
        }
        // var_dump($res);die;
        return $data;
    }

    public function userNum($pid,$start_time,$end_time)
    {
        $key = 'countUserNum'.$pid.'_'.$start_time.'_'.$end_time;
        $data = S($key);
        if(empty($res)){
            $model = new CountModel();
            $res = $model->userNum($pid,$start_time,$end_time);


            $data['inc_num'] = array();
            $data['total_num'] = array();
            $data['dec_num'] = array();
            $data['datepos'] = array();
            foreach ($res as $key => $value) {
                $data['inc_num'][] = $value['inc_num'];
                $data['total_num'][] = $value['total_num'];
                $data['dec_num'][] = $value['dec_num'];
                $data['datepos'][] = $value['datepos'];
            }
            $data['inc_num'] = json_encode($data['inc_num']);
            $data['total_num'] = json_encode($data['total_num']);
            $data['dec_num'] = json_encode($data['dec_num']);
            $data['datepos'] = json_encode($data['datepos']);
            S($key,$data,TIME_ONE_HOUR);
        }

        return $data;

    }

    public function userFunnel($partner_id){
        $res = S('userFunnel_'.$partner_id);
        $list = $res;
        if(empty($res)){
            $model = new CountModel();
            $res = $list = $model->userFunnel($partner_id);
            S('userFunnel_'.$partner_id,$res,TIME_ONE_HOUR);
        }
        
        $total = $res[0]['value']; 
        $label = array(
                '关注用户',
                '访问用户',
                '下单用户',
                '多次下单用户',
            );
        foreach($res as $key=>$value){
            $res[$key]['value'] = intval(($value['value']/$total)*100);
            $res[$key]['name'] = $label[$value['name']-1];
        }

        $data['label'] = json_encode($label);
        $data['data'] = json_encode($res);
        $data['list'] = $list;
        return $data;
    }

    public function billCount($p,$partner_id,$shop_id,$start_date,$end_date,$commission_rate){
        if(strtotime($start_date) == strtotime('today') && strtotime($end_date) == strtotime('today')){
            $order_type = 1;
        }else{
            $order_type = 2;
            if(strtotime($end_date) == strtotime('today')){
                $end_date = date("Y-m-d",strtotime($end_date)-86400);
            }
        }

        $date_res = self::getMaxDate($start_date,$end_date);
        $start_time = strtotime($date_res['start_date']);
        $end_time = strtotime($date_res['end_date']);

        $date_list = self::getDateList($start_time,$end_time);

        $model = new CountModel();
        $online = $model->o2oData($p,$partner_id,$shop_id,$start_time,$end_time,1,$order_type);
        $offline = $model->o2oData($p,$partner_id,$shop_id,$start_time,$end_time,2,$order_type);
        //储值支付
        $accountpay = $model->o2oData($p,$partner_id,$shop_id,$start_time,$end_time,3,$order_type);

        //充值
        $recharge = $model->getRecharg($partner_id,$shop_id,$start_time,$end_time);
        //订单充值
        $order_recharge = $model->getOrderRecharg($partner_id,$shop_id,$start_time,$end_time);

        //话费扣款
        $phone_money = $model->getPhoneMoney($partner_id,$shop_id,$start_time,$end_time);

        //用户储值
        $user_recharge = $model->getUserRecharge($partner_id,$start_time,$end_time);

        $arr = array();
        foreach($date_list as $key=>$date){
            $online_money = empty($online[$date]['pay_money']) ? 0 : $online[$date]['pay_money']; 
            $offline_money = empty($offline[$date]['pay_money']) ? 0 : $offline[$date]['pay_money'];
            $accountpay_money = empty($accountpay[$date]['pay_money']) ? 0 : $accountpay[$date]['pay_money'];
            $online_send = empty($online[$date]['send_money']) ? 0 : $online[$date]['send_money'];
            $offline_send = empty($offline[$date]['send_money']) ? 0 : $offline[$date]['send_money'];
            $accountpay_send = empty($accountpay[$date]['send_money']) ? 0 : $accountpay[$date]['send_money'];
            $online_total = empty($online[$date]['total_money']) ? 0 : $online[$date]['total_money'];
            $offlie_total = empty($offline[$date]['total_money']) ? 0 : $offline[$date]['total_money'];
            $accountpay_total = empty($accountpay[$date]['total_money']) ? 0 : $accountpay[$date]['total_money'];

            $user_recharge = empty($user_recharge[$date]['user_recharge']) ? 0 : $user_recharge[$date]['user_recharge'];

            //营业总额，订单实际支付的钱(包含运费)
            $pay_money = $online_money + $offline_money + $accountpay_money;
            //订单原价(包含运费)
            $total_money = $online_total + $offlie_total + $accountpay_total;
             //营销成本
            $action_money = $total_money - $pay_money;

            $send_money = $online_send + $offline_send + $accountpay_send; 
            //商品原价
            $original_money = $total_money - $send_money;

            $arr[$key]['date'] = $date; 
            $arr[$key]['online_money'] = $online_money;
            $arr[$key]['offline_money'] = $offline_money;
            $arr[$key]['accountpay_money'] = $accountpay_money;
            $arr[$key]['pay_money'] = $pay_money;
            $arr[$key]['send_money'] = $send_money;
            $arr[$key]['action_money'] = $action_money;
            $arr[$key]['original_money'] = $original_money;
            $arr[$key]['weilai'] = ($pay_money - $send_money)*$commission_rate;

            $arr[$key]['order_recharge'] = empty($order_recharge[$date]['order_recharge']) ? 0 : $order_recharge[$date]['order_recharge'];

            $arr[$key]['weixin'] = ($online_money-$arr[$key]['order_recharge']+$user_recharge) * 0.006; 

            $arr[$key]['bank'] = $online_money+$user_recharge - $arr[$key]['weixin'] - $arr[$key]['order_recharge'];

            $arr[$key]['real_money'] = $pay_money - $send_money - $arr[$key]['weilai'] - $arr[$key]['weixin'];
            $arr[$key]['recharge'] = empty($recharge[$date]['recharge']) ? 0 : $recharge[$date]['recharge'];
            $arr[$key]['phone_money'] = empty($phone_money[$date]['phone_money']) ? 0 : $phone_money[$date]['phone_money'];
            $arr[$key]['user_recharge'] = $user_recharge;

        }
        $arr = self::getO2OTotal($arr);
        // var_dump($arr);die;
        return $arr;
    }

    private function getDateList($start_time,$end_time){
        $arr = array();
        $time = $end_time;
        while($time >= $start_time){
            $arr[] = date('Y-m-d',$time);
            $time -= 86400;
        }
        return $arr;
    }

    private function getMaxDate($start_date,$end_date){
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);

        if($start_time + (86400*30) < $end_time){
            $end_date = date('Y-m-d',$start_time + 86400*30);
        }

        $res['start_date'] = $start_date;
        $res['end_date'] = $end_date;
        return $res;
    }

    private function getO2OTotal($arr){
        $tmp = array();
        $tmp['date'] = '总计';
        foreach($arr as $key=>$value){
            $tmp['online_money'] += $value['online_money']; 
            $tmp['offline_money'] += $value['offline_money']; 
            $tmp['accountpay_money'] += $value['accountpay_money'];
            $tmp['pay_money'] += $value['pay_money']; 
            $tmp['send_money'] += $value['send_money']; 
            $tmp['action_money'] += $value['action_money']; 
            $tmp['original_money'] += $value['original_money']; 
            $tmp['weilai'] += $value['weilai']; 
            $tmp['weixin'] += $value['weixin']; 
            $tmp['bank'] += $value['bank']; 
            $tmp['real_money'] += $value['real_money']; 
            $tmp['recharge'] += $value['recharge']; 
            $tmp['order_recharge'] += $value['order_recharge']; 
            $tmp['phone_money'] += $value['phone_money']; 
            $tmp['user_recharge'] += $value['user_recharge'];
        }
        array_unshift($arr,$tmp);
        return $arr;
    }

    public function getOrderAnalysisData($shop_id,$start_time,$end_time){
        $model = new CountModel();
        if($shop_id == 0){
            $res = $model->orderAnalysisByPartner($start_time,$end_time);
        }else{
            $res = $model->orderAnalysisByShop($shop_id,$start_time,$end_time);
        }
        return $res;
    }


    //excel导出模板
    //表头 $header = array('name'=>'姓名');
    //name文件名，title表格a1单元格名称
    //$list 表格数据
    public function exportExcelTpl($name,$title,$header=array(),$list=array()){
        $alpha = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $header_count = count($header);


        Vendor("Classes.PHPExcel");

        // Create new PHPExcel object
        $objPHPExcel = new \PHPExcel();

        // set width
        for($i=0;$i<$header_count;$i++){
            $objPHPExcel->getActiveSheet()->getColumnDimension($alpha[$i])->setWidth(20);
        }
     

        // 设置行高度
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(30);
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);

        // 字体和样式
        $end_col = $alpha[$header_count-1];

        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);

        $objPHPExcel->getActiveSheet()->getStyle('A2:'.$end_col.'2')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);

        $objPHPExcel->getActiveSheet()->getStyle('A2:'.$end_col.'2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A2:'.$end_col.'2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);


        // 设置水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        for($i=0;$i<$header_count;$i++){
            $objPHPExcel->getActiveSheet()->getStyle($alpha[$i])->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }

        //  合并
        $objPHPExcel->getActiveSheet()->mergeCells('A1:'.$end_col.'1');

        // 表头
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1',$title);

        $i = 0;
        foreach($header as $key=>$value){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($alpha[$i].'2',$value);
            $i++;
        }
            
        //渲染数据
        for($i = 0; $i < count($list); $i++){
            $j = 0;
            foreach($header as $key=>$value){
                $objPHPExcel->getActiveSheet(0)->setCellValue($alpha[$j].($i + 3), $list[$i][$key]);
                $j++;
            }

            $objPHPExcel->getActiveSheet()->getStyle('A' . ($i + 3) . ':'.$end_col . ($i + 3))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A' . ($i + 3) . ':'.$end_col . ($i + 3))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $objPHPExcel->getActiveSheet()->getRowDimension($i + 3)->setRowHeight(16);
        }

        // Rename sheet
        $objPHPExcel->getActiveSheet()->setTitle('1');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

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