<?php
namespace Partner\Controller;
use O2OSender\UuPtSdk;
use Partner\Service\PartnerService;
use Partner\Service\ShopService;
use Partner\Service\SettingService;
use Partner\Service\OrderService;
use Think\Controller;

class ShopController extends BaseController {
    public function index(){
        $p = I('p');
    	$service = new ShopService();
        $list = $service->getShopList($p);
        $this->assign('list',$list);
        $this->display();
    }

    public function addShop(){
        $service = new ShopService();
        $city = $service->getCity($this->partner_id);
        $this->assign($city);
    	$this->display();
    }

    public function addShopAction(){

        $data = I();
        $service = new ShopService();
        $is_repeat = $service->checkAccountRepeat($data['account']);
        if($is_repeat){
            echojson('该帐号已经注册','','error');
        }else{
            if($service->checkShopNum()){
                $shop_id = $service->addShop($data);
                echojson('添加成功',U('Shop/index'));
            }else{
                echojson('店铺数量达到上限啦，联系味来吧',U('Shop/index'),"error");
            }

            // echojson('店铺添加成功',U('Shop/certsUpload',array('shop_id'=>$shop_id)));
        }
        
    }

    public function editShop(){
        $id = I('id');
        $service = new ShopService();
        $data = $service->getShopById($id);
        $pick_address = $service->getPickAddress($id);
        $pick_time = $service->getPickTime($id);
        $data['print_times'] =  $service->getPrintTimes($id);
        $this->assign('data',$data);
        $this->assign('pick_address',$pick_address);
        $this->assign('pick_time',$pick_time);
        $this->display();
    }

    public function editShopAction(){
        $data = I();
        $service = new ShopService();
        $is_repeat = $service->checkAccountRepeat($data['account'],$data['id']);
        if($is_repeat){
            echojson('该帐号已经注册','','error');
        }else{
            $service->editShop($data);
            echojson('修改成功',U('Shop/index'));
        }
        
    }

    public function deleteFoods(){
        $id = $_GET['id'];
        $service = new GoodsService();
        $service->deleteFoods($id);
        echojson('删除成功','refresh');
    }

    public function changeStatus(){
        $id = I('id');
        $now_status = I('now_status');
        $service = new GoodsService();
        $service->changeStatus($id,$now_status);
        echo json_encode(array('status'=>'ok'));
    }

    public function addChildGoods(){
        $res = M('base_goods')->where(array('id'=>1))->find();
        var_dump($res);die;
        // unset($res['id']);
        // M('shop_goods')->add($res);
        // echo 123;die;
        // var_dump($res);die;
    }

    public function deleteShop(){
        $shop_id = I('id');
        if(empty($shop_id)){
            echo json_encode(array('status'=>'fail'));
        }else{
            $service = new ShopService();
            $service->deleteShop($shop_id);
            echo json_encode(array('status'=>'ok'));
        }
        
    }

    /*图片上传*/
    public function uploads(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     C('UPLOAD_MAX_SIZE') ;// 设置附件上传大小
        $upload->exts      =     C('UPLOAD_EXTS');// 设置附件上传类型
        $upload->rootPath  =      C('UPLOAD_PATH'); // 设置附件上传目录
//        $upload->autoSub   =     false;
        //$upload->saveName  =     '';
        // 上传文件
        $info   =   $upload->uploadOne($_FILES['img']);
        $img = $info['savepath'].$info['savename'];
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }
        return substr(C('UPLOAD_PATH').$img,1);
    }

    public function certsUpload(){
        $shop_id = I('shop_id');
        $this->assign('shop_id',$shop_id);
        $this->display();
    }

    public function certsUploadding(){
        $img = upload('files','certs');
        $data['img'] = $img;
        $data['partner_id'] = session('partner_id');
        $data['shop_id'] = I('shop_id');
        $data['name'] = $_FILES['files']['name'][0];
        $id = M('shop_certs')->add($data);
        $name = "certs_".$data['partner_id'].'_'.$data['shop_id'].'_'.$id.'.jpg';
        $img = C('OSS_PATH').ossUploadImg($name,C('BASE_PATH').$data['img'],2);
        M('shop_certs')->where(array('id'=>$id))->save(array('img'=>$img));

        $res['files'][0] = array('thumbnailUrl'=>$img,'url'=>333,'name'=>$data['name'],'deleteUrl'=>U('Shop/deleteCerts',array('id'=>$id)),'deleteType'=>'delete','size'=>1230);
        // $res['files'][0] = array('thumbnailUrl'=>$img,'url'=>333,'name'=>$data['name'],'deleteUrl'=>U('Shop/deleteCerts'),'deleteType'=>'DELETE','size'=>1230);
        echo json_encode($res);
        
    }

    public function deleteCerts(){
        $id = I('id');
        M('shop_certs')->where(array('id'=>$id))->delete();
        echo 123;
    }

    public function getCerts(){
        $shop_id = I('shop_id');
        $cets_list = M('shop_certs')->where(array('shop_id'=>$shop_id))->select();
        foreach($cets_list as $certs){
            $res['files'][] = array('thumbnailUrl'=>$certs['img'],'url'=>333,'name'=>$certs['name'],'deleteUrl'=>U('Shop/deleteCerts',array('id'=>$certs['id'])),'deleteType'=>'delete','size'=>1230);
        }       
        echo json_encode($res);
    }

    public function notice()
    {
        if(IS_POST){
            $sid = I('shop_id');
            $content = I('post.content','',false);
            $ser = new ShopService();
            $ser->saveNotice($sid,$content);
            $this->success("发布成功");
        }else{
            $sid = I('shop_id');
            $ser = new ShopService();
            $content = $ser->getNotice($sid);
            $this->assign('sid',$sid);
            $this->assign('content',$content);
            $this->display();
        }
    }

    public function shoppay()
    {
        $id = I('shop_id');
        $ser = new ShopService();
        $path = $ser->getShopPayImg($id);
        $path = C('BASE_PATH').$path;
//        echo $path;exit;
        getFile($path);
    }

    public function setBuilding(){
        $service = new ShopService();
        if(IS_POST){
            $partner_id = session('partner_id');
            $data = I();       
            $shop_id = $data['shop_id'];
            $service->setBuilding($partner_id,$shop_id,$data);
            echo json_encode(array('msg'=>'ok'));
        }else{
            $shop_id = I('shop_id');
            $building_list = $service->getBuildingList($shop_id);
            $this->assign('building_list',$building_list);
            $this->assign('shop_id',$shop_id);
            $this->display();
        }
    }

    public function setSend(){
        $partner_id = session('partner_id');
        $service = new ShopService();
        if(IS_POST){
            $data = I();
            $service->setSend($partner_id,$data);
            echojson('修改成功',U('Shop/index'));
        }else{
            $shop_id = I('shop_id');
            $info = $service->getShopById($shop_id);
            // var_dump($info);die;
            $setting_service = new SettingService();
            $charge_list = $setting_service->getSendCharge($partner_id,$shop_id);
            $reserve_list  = $setting_service->getReserveList($partner_id,$shop_id);
            $shopser = new ShopService();
            $part_list = $shopser->getPolygonSetting($shop_id);
            $send_list = $shopser->getSendConf($shop_id);
            $this->assign('send_list',$send_list);

            $this->assign('info',$info);
            $this->assign('charge_list',$charge_list);
            $this->assign('reserve_list',$reserve_list);
            $this->assign('partlist',$part_list);
            $this->assign('shop_id',$shop_id);
            $this->display();
        }
    }

    public function changeSendStatus()
    {
        $shop_id = I('shop_id');
        $key = I('key');
        $status = I('status');
        $params = I('params');
        $partner_id = session('partner_id');
        if(!empty($params)){
            $params = explode(',',$params);
        }
        $ser = new ShopService();
        $ser->updateSendStatus($partner_id,$shop_id,$key,$status,$params);
    }

    public function choosePartSend()
    {
        $shop_id = I('shop_id');
        $ser = new ShopService();
        echo $ser->choosePartSend($shop_id);exit;
    }

    public function saveAddress()
    {
        $data['shop_id'] = I('shop_id');
        $data['lat'] = I('lat');
        $data['lng'] = I('lng');
        if(empty($data['shop_id']) || empty($data['lat'])){
            echo 1;exit;
        }else{
            $service = new ShopService();
            $service->setAddress($data);
            echo 0;exit;
        }

    }

    public function polygonSend()
    {
        $shop_id = I('shop_id');
        $ser = new ShopService();
        $list = $ser->getPolygonSetting($shop_id);
        $point = $ser->getShopPos($shop_id);
        if(empty($point)){
            redirect(U('Shop/setSend',array('shop_id'=>$shop_id)));
        }
        $this->assign('point',$point);
        $this->assign('num',count($list)+1);
        $this->assign('list',$list);
        $this->assign('shop_id',$shop_id);
        $this->display();
    }

    public function addPolygon()
    {
        $shop_id = I('shop_id');
        $name = I('name');
        $part = I('part');
        $price = I('price');
        $partner_id = session('partner_id');
        $data['partner_id'] = $partner_id;
        $data['shop_id'] = $shop_id;
        $data['name'] = $name;
        $data['part'] = $part;
        $data['price'] = $price;
        $data['create_time'] = time();
        $ser = new ShopService();
        $res = $ser->savePolygonSetting($data);
        echo $res;exit;
    }

    public function delPolygon()
    {
        $id = I('id');
        $shop_id = I('shop_id');
        $ser = new ShopService();
        $ser->delPolygon($shop_id,$id);
    }

    public function addPickPoint(){
        if(IS_POST){
            $partner_id = session('partner_id');
            $data = I();
            $data['partner_id'] = $partner_id;
            $service = new ShopService();
            $res = $service->addPickPoint($data);
            if($res){
                echojson('添加成功',U('Shop/pickPointList'));
            }
        }else{
            $order_service = new  OrderService();
            $shop_list = $order_service->getShopList();
            $this->assign('shop_list',$shop_list);
            $this->display();
        }
        
    }

    public function editPickPoint(){
        if(IS_POST){
            $partner_id = session('partner_id');
            $data = I();
            $data['partner_id'] = $partner_id;
            $service = new ShopService();
            $res = $service->editPickPoint($data);
            
            echojson('修改成功',U('Shop/pickPointList'));
        }else{
            $id= I('id');
            $order_service = new  OrderService();
            $shop_list = $order_service->getShopList();
            $shop_service = new ShopService();
            $info = $shop_service->getPickPointById($id);
            $this->assign('id',$id);
            $this->assign('info',$info['info']);
            $this->assign('pick_time',$info['pick_time']);
            $this->assign('shop_list',$shop_list);
            $this->display();
        }
        
    }

    public function pickPointList(){
        $p = I('p');
        $shop_id = I('shop_id');
        $search_key = I('search_key');
        $order_by = I('order_by');
        $order_service = new OrderService();
        $shop_list = $order_service->getShopList();
        $shop_service = new ShopService();
        $partner_id = session('partner_id');
        $list = $shop_service->getPickPointList($partner_id,$p,$shop_id,$search_key);
        $this->assign('list',$list);
        $this->assign('shop_list',$shop_list);
        $this->assign('shop_id',$shop_id);
        $this->assign('search_key',$search_key);
        $this->assign('partner_id',$partner_id);
        $this->display();
    }

    public function deletePickPoint(){
        $id = I('id');
        $service = new ShopService();
        $res  = $service->deletePickPoint($id);
        if($res){
            echojson('删除成功','refresh');
        }
    }


    public function getPickQrcode(){
        $partner_id = session('partner_id');
        $id = I('id');
        $shop_id = I('shop_id');
        $number = I('number');
        $service = new ShopService();
        $file = $service->getPickQrcode($partner_id,$shop_id,$id,$number);
        $file = C('BASE_PATH').$file;

        $fileName = basename($file);  //获取文件名
        header("Content-Type:application/octet-stream");//告诉浏览器文档类型(mime类型); octet-stream指的是二进制文件类型;下载任何类型的文件都可以这么指定
        header("Content-Disposition:attachment;filename=".$fileName);//告诉浏览器以附件方式对待文件(即下载文件);并设置下载后的文件名
        header("Accept-ranges:bytes");//告诉浏览器文件大小的单位
        header("Accept-Length:".filesize($file));                                                    //告诉浏览器文件的大小
        $h = fopen($file, 'r');//打开文件
        echo fread($h, filesize($file));   
    }

    public function editPick(){
        $service = new ShopService();
        if(IS_POST){
            $partner_id = session('partner_id');
            $data = I();
            $service->addPickAddress($partner_id,$data['shop_id'],$data['pick_address']);
            $service->addPickTime($partner_id,$data['shop_id'],$data['pick_start_time'],$data['pick_end_time']);
            echojson('设置成功',U('shop/index'));
        }else{
            $partner_id = session('partner_id');
            $shop_id = I('shop_id');
            $data = $service->getShopById($shop_id);
            $pick_address = $service->getPickAddress($shop_id);
            $pick_time = $service->getPickTime($shop_id);
            $this->assign('pick_address',$pick_address);
            $this->assign('pick_time',$pick_time);
            $this->assign('shop_id',$shop_id);
            $this->assign('data',$data);
            $this->assign('partner_id',$partner_id);
            $this->display();
        }
        
    }

    public function setPickPoint(){
        $data = I();
        $service = new ShopService();
        $service->setPickPoint($data['shop_id'],$data['is_pick_point']);
        echo json_encode(array('msg'=>'ok'));
    }

    public function pickPointQrcodePay(){
        $id = I('id');
        $p = I('p');
        $service = new ShopService();
        $res = $service->getPickPointQrcodePayList($p,$id);
        $this->assign('list',$res);
        $this->display();
    }

    public function importExcel(){
        if(IS_POST){
            $res = $_FILES['excel'];
            $file = upload('excel','goodsImg');
            Vendor("Classes.PHPExcel");
           

            $reader = \PHPExcel_IOFactory::createReader('Excel5'); //设置以Excel5格式(Excel97-2003工作簿)
            $PHPExcel = $reader->load("./".$file); // 载入excel文件
            $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
            $highestRow = $sheet->getHighestRow(); // 取得总行数
            $highestColumm = $sheet->getHighestColumn(); // 取得总列数
            $highestColumm= \PHPExcel_Cell::columnIndexFromString($highestColumm); //字母列转换为数字列 如:AA变为27

            // 循环读取每个单元格的数据 
            for ($row = 2; $row <= $highestRow; $row++){//行数是以第1行开始
                for ($column = 0; $column < $highestColumm; $column++) {//列数是以第0列开始
                    $columnName = \PHPExcel_Cell::stringFromColumnIndex($column);
                    echo $columnName.$row.":".$sheet->getCellByColumnAndRow($column, $row)->getValue()."<br />";

                }
            }
            // var_dump($highestColumm);die;
        }else{
            $this->display();
        }
    }


    public function exportPickPointQrcodePay(){
        $p = I('p');
        $shop_id = I('shop_id',0);
        $partner_id = session('partner_id');
        $search_key = I('search_key');
        $service = new ShopService();

        //有预定的时候能够接到该参数reserve_time
        $reserve_time = I('reserve_time',date('Y-m-d',strtotime('today')));
        $list = $service->getAllPickPointQrcodePay($partner_id);

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
     

        // 设置行高度
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(30);

        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);

        // 字体和样式
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);
        $objPHPExcel->getActiveSheet()->getStyle('A2:E2')->getFont()->setBold(true);

        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);

        $objPHPExcel->getActiveSheet()->getStyle('A2:E2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A2:E2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        $objPHPExcel->getActiveSheet()->getStyle('A3:E3')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        



        // 设置水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //设置换行
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setWrapText(true);
        

        //设置数字格式
        /*$objPHPExcel->getActiveSheet()->getStyle('G3')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        $objPHPExcel->getActiveSheet()->getStyle('G')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);*/

        //  合并
        $objPHPExcel->getActiveSheet()->mergeCells('A1:E1');

        //设置数字格式
        $objPHPExcel->getActiveSheet()->getStyle('D')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        $objPHPExcel->getActiveSheet()->getStyle('D3')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);


        // 表头
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '今日线下储值付'.$reserve_time)
            ->setCellValue('A2', '自提点编号')
            ->setCellValue('B2', '自提点地址')
            ->setCellValue('C2', '姓名')
            ->setCellValue('D2', '消费金额')
            ->setCellValue('E2', '消费时间');
            

        //渲染数据
        for($i = 0; $i < count($list); $i++){
            $objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 3), $list[$i]['pick_number']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 3), $list[$i]['pick_address']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 3), $list[$i]['user_name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 3), $list[$i]['money']);
            $create_time = date('Y-m-d H:i',$list[$i]['create_time']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 3), ' '.$create_time);

            $objPHPExcel->getActiveSheet()->getStyle('A' . ($i + 3) . ':E' . ($i + 3))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A' . ($i + 3) . ':E' . ($i + 3))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $objPHPExcel->getActiveSheet()->getRowDimension($i + 3)->setRowHeight(16);
        }

        // Rename sheet
        $objPHPExcel->getActiveSheet()->setTitle('今日线下储值付');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);


        $name = '今日线下储值付'.$reserve_time;
        // 输出
        header('pragma:public');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$name.'.xls"');
        header("Content-Disposition:attachment;filename=$name.xls");//attachment新窗口打印inline本窗口打印
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');

    }

    public function getUuPtOpenid()
    {
        $sid = I('id');
        $send = M('sendtype')->where(array('keystr'=>'uu'))->find();
        $uusdk = new UuPtSdk($send['appid'],$send['appsec']);
        $this->display();
    }

}