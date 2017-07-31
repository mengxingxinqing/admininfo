<?php
namespace Partner\Controller;
use Partner\Service\GoodsService;
use Partner\Service\OrderService;
use Partner\Service\ShopService;
use Think\Controller;

class GoodsController extends BaseController {
    public function index(){
        $p = I('p');
        $category_id = I('category_id',0);
        $shop_id = I('shop_id',0);
        $partner_id = session('partner_id');
    	$goods_service = new GoodsService();
        if($shop_id){
            $data = $goods_service->getGoodsListByShop($p,$shop_id,$category_id);
            $category_list = $goods_service->getCategoryByShop($partner_id,$shop_id);
        }else{
            $data = $goods_service->getGoodsList($p,$category_id);
            $category_list = $goods_service->getCategory($partner_id);
        }
        $order_service = new OrderService();
        $shop_list = $order_service->getShopList();

        $ser = new \Shop\Service\GoodsService();
        $hasStore = $ser->partnerHasStore($partner_id,$shop_id);
        $this->assign('hasStore',$hasStore);

        $this->assign('data',$data);
        $this->assign('shop_list',$shop_list);
        $this->assign('shop_id',$shop_id);
        $this->assign('category_list',$category_list);
        $this->assign('category_id',$category_id);
        $this->assign('partner_id',$partner_id);
        $this->display();
    }

    public function addGoods(){
        $partner_id = session('partner_id');
        $service = new OrderService();
        $shop_list = $service->getShopList();
        $service = new GoodsService();
        $category = $service->getCategory($partner_id);
        $has_barcode = $service->partnerHasBarcode($partner_id);
        $this->assign('shop_list',$shop_list);
        $this->assign('category',$category);
        $this->assign('has_barcode',$has_barcode);
    	$this->display();
    }

    public function addGoodsAction(){

        $data = I();
        $partner_id = session('partner_id');
        $shop_ids = $data['shop_id'];
        //数组中去掉shop_id数组,方便直接插入数据库
        unset($data['shop_id']);

        //判断是否更换图片
        if ($_FILES['img']['name']) {
            // $data['img'] = $this->uploads();
            $data['img'] = upload('img','goodsImg');
        }

        if ($_FILES['detail_img1']['name']) {
            $file['detail_img1'] = upload('detail_img1','goodsImg');
            
        }else{
            $file['detail_img1'] = '';
        }

        if ($_FILES['detail_img2']['name']) {
            $file['detail_img2'] = upload('detail_img2','goodsImg');
            
        }else{
            $file['detail_img2'] = '';
        }

        if ($_FILES['detail_img3']['name']) {
            $file['detail_img3'] = upload('detail_img3','goodsImg');
            
        }else{
            $file['detail_img3'] = '';
        }

        $service = new GoodsService();
        $goods_id = $service->addGoods($data,$partner_id,$shop_ids);

        $extension = get_extension($_FILES['img']['name']);
        $file_name = $goods_id.'.'.$extension;
        $ossName = ossUploadImg($file_name,C('BASE_PATH').$data['img']);

        $service->updateGoodsImg($ossName,$goods_id);

        //三个详情图上传到阿里云上
        $service->ossGoodsDetailImg($goods_id,$file);

        clearGoodsCacheByBaseGoodsId($goods_id);
        $this->success('新增成功', 'index'); 
    }

    public function editGoods(){
        $partner_id = session('partner_id');
        $id = I('id');
        $service = new GoodsService();
        $data = $service->getGoodsById($id);
        $category = $service->getCategory($partner_id);
        $has_barcode = $service->partnerHasBarcode($partner_id);
        $goods_format = $service->getBaseGoodsFormat($id);
        $goods_attr = $service->getBaseGoodsAttr($id);

        $service = new OrderService();
        $shop_list = $service->getShopList();
        $this->assign('data',$data);
        $this->assign('category',$category);
        $this->assign('shop_list',$shop_list);
        $this->assign('has_barcode',$has_barcode);
        $this->assign('goods_format',$goods_format);
        $this->assign('goods_attr',$goods_attr);
        $this->display();
    }

    public function editGoodsAction(){
        $data = I();
        $partner_id = session('partner_id');
        $shop_ids = $data['shop_id'];
        //数组中去掉shop_id数组,方便直接插入数据库
        unset($data['shop_id']);

        //判断是否更换图片
        if ($_FILES['img']['name']) {
            $data['img'] = upload('img','goodsImg');
        }


        if ($_FILES['detail_img1']['name']) {
            $file['detail_img1'] = upload('detail_img1','goodsImg');
            
        }else{
            $file['detail_img1'] = '';
        }

        if ($_FILES['detail_img2']['name']) {
            $file['detail_img2'] = upload('detail_img2','goodsImg');
            
        }else{
            $file['detail_img2'] = '';
        }

        if ($_FILES['detail_img3']['name']) {
            $file['detail_img3'] = upload('detail_img3','goodsImg');
            
        }else{
            $file['detail_img3'] = '';
        }


        $service = new GoodsService();
        $service->editGoods($data,$partner_id,$shop_ids);
        $service->editOssGoodsDetailImg($data,$file);

        if($_FILES['img']['name']){
            $extension = get_extension($_FILES['img']['name']);
            $file_name = $data['id'].'.'.$extension;
            $ossName = ossUploadImg($file_name,C('BASE_PATH').$data['img']);
            $service->updateGoodsImg($ossName,$data['id']);
        }
        clearGoodsCacheByBaseGoodsId($data['id']);
        $this->success('修改成功', 'index'); 
    }

    public function deleteGoods(){
        $id = $_GET['id'];
        if(empty($id)){
            echojson('删除失败','refresh');
        }
        $service = new GoodsService();
        $service->deleteGoods($id);
        clearGoodsCacheByBaseGoodsId($id);
        echojson('删除成功','refresh');
    }

    public function deleteShopGoods(){
        $id = $_GET['id'];
        $service = new GoodsService();
        $service->deleteShopGoods($id);
        echojson('删除成功','refresh');
    }

    public function changeStatus(){
        $id = I('id');
        $now_status = I('now_status');
        $service = new GoodsService();
        $service->changeStatus($id,$now_status);
        echo json_encode(array('status'=>'ok'));
    }

    //商品是否在前台显示，后台对应的操作文本为“上架中，下架中”
    public function goodsDisply(){
        $id = I('id');
        $now_status = I('now_status');
        $service = new GoodsService();
        $service->goodsDisply($id,$now_status);

        clearGoodsCacheByShopGoodsId($id);

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

    public function getSelectedShop(){
        $basegoods_id = I('basegoods_id');
        $service = new GoodsService();
        $res = $service->getSelectedShop($basegoods_id);
        echo json_encode($res);
    }

    /*图片上传*/
    public function uploads(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     C('UPLOAD_MAX_SIZE') ;// 设置附件上传大小
        $upload->exts      =     C('UPLOAD_EXTS');// 设置附件上传类型
        $upload->rootPath  =     C('BASE_PATH').'Public/goodsImg/'; //设置附件上传目录
        //$upload->autoSub   =     false;
        //$upload->saveName  =     '';
        // 上传文件
        $info   =   $upload->uploadOne($_FILES['img']);
        $img = $info['savepath'].$info['savename'];
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }
        return substr($upload->rootPath.$img,1);
    }

    public function setGoodsSort(){
        $this_sort = I('this_sort');
        $pre_sort = I('pre_sort');
        $next_sort = I('next_sort');
        $category_id = I('category_id');
        $partner_id = session('partner_id');
        $this_goods_id = I('this_goods_id');
        $shop_category_id = M('shop_category')->where(array('base_id'=>$category_id))->getField('id');
        $service = new GoodsService();
        $service->setGoodsSort($this_sort,$pre_sort,$next_sort,$partner_id,$category_id,$this_goods_id);
    }


    //每日新品设置
    public function dayNewGoodsSet(){
        $partner_id = session('partner_id');
        if(IS_POST){
            $data = I();
            $data['partner_id'] = $partner_id;
            $goods_service = new GoodsService();
            $goods_service->dayNewGoodsSet($data);
            echojson('保存成功');
        }else{
            $order_service = new OrderService();
            $shop_list = $order_service->getShopList();         
            $default_shop_id = $shop_list[0]['id'];
            $shop_id = I('shop_id',$default_shop_id);

            $goods_service = new GoodsService();
            $dayset_list = $goods_service->getDayNewGoodsSet($shop_id);
            $discount_list = $goods_service->getDisCountList($shop_id);


            $shop_service = new ShopService();
            $shop_info =  $shop_service->getConf($shop_id);

            $this->assign('shop_list',$shop_list);
            $this->assign('dayset_list',$dayset_list);
            $this->assign('discount_list',$discount_list);
            $this->assign('shop_id',$shop_id);
            $this->assign('shop_info',$shop_info);
            $this->display();
        }
    }

    //每日新品列表
    public function dayNewGoodsList(){
        $partner_id = session('partner_id');
        $order_service = new OrderService();
        $shop_list = $order_service->getShopList();         
        $default_shop_id = $shop_list[0]['id'];
        $shop_id = I('shop_id',$default_shop_id);


         //被选的日期
        $shop_service = new ShopService();
        $shop_info = $shop_service->getConf($shop_id);
        if($shop_info['close_today_dayset'] == 1){
            $selected_day = I('selected_day',date('w',time()+86400));
        }else{
            $selected_day = I('selected_day',date('w',time()));
        }
        if($selected_day == 0){
            $selected_day = 7;
        }


        $goods_service = new GoodsService();
        $category_list = $goods_service->getCategoryByShop($partner_id,$shop_id);
        $day_list = $goods_service->getLabelDate($shop_id,$shop_info['close_today_dayset']);

       

        $day_goods_list = $goods_service->dayNewGoodsList($partner_id,$shop_id,$selected_day);
        $this->assign('day_goods_list',$day_goods_list);
        $this->assign('shop_list',$shop_list);
        $this->assign('category_list',$category_list);
        $this->assign('day_list',$day_list);
        $this->assign('selected_day',$selected_day);
        $this->assign('shop_id',$shop_id);
        $this->display();
    }


    public function preGoodsList()
    {
        $partner_id = session('partner_id');
        $order_service = new OrderService();
        $shop_list = $order_service->getShopList();
        $default_shop_id = $shop_list[0]['id'];
        $shop_id = I('shop_id',$default_shop_id);

        $selected_day = I('selected_day',0);
        $goods_service = new GoodsService();
        $category_list = $goods_service->getCategoryByShop($partner_id,$shop_id);
        $day_list = [
            ['str'=>'今天','val'=>'0'],
            ['str'=>'明天','val'=>'1']
        ];
        $day_goods_list = $goods_service->preGoodsList($partner_id,$shop_id,$selected_day);
        $this->assign('day_goods_list',$day_goods_list);
        $this->assign('shop_list',$shop_list);
        $this->assign('category_list',$category_list);
        $this->assign('day_list',$day_list);
        $this->assign('selected_day',$selected_day);
        $this->assign('shop_id',$shop_id);
        $this->display();
    }

    public function groupGoodsList()
    {
        $partner_id = session('partner_id');
        $order_service = new OrderService();
        $shop_list = $order_service->getShopList();
        $default_shop_id = $shop_list[0]['id'];
        $shop_id = I('shop_id',$default_shop_id);

        $selected_day = I('selected_day',0);
        $goods_service = new GoodsService();
        $category_list = $goods_service->getCategoryByShop($partner_id,$shop_id);
        $day_list = [
            ['str'=>'设置','val'=>'0'],
            ['str'=>'选餐','val'=>'1']
        ];
        $day_goods_list = $goods_service->groupGoodsList($partner_id,$shop_id);
        $setting = $goods_service->getGroupSetting($partner_id,$shop_id);
//        var_dump($setting);die;
        $this->assign('day_goods_list',$day_goods_list);
        $this->assign('info',$setting);
        $this->assign('shop_list',$shop_list);
        $this->assign('category_list',$category_list);
        $this->assign('day_list',$day_list);
        $this->assign('selected_day',$selected_day);
        $this->assign('shop_id',$shop_id);
        if($selected_day == 0){
            $this->display('groupSetting');
        }else{
            $this->display();
        }

    }

    public function editGroupAction()
    {
        $info = I();
        $partner_id = session('partner_id');
        $sid = I('sid');
        $ser = new GoodsService();
        $ser->editGroupMeal($partner_id,$sid,$info);
        echojson("操作成功");
    }

    //根据category_id 拉取 goods
    public function loadGoodsList(){
        $category_id = I('category_id');
        $service = new GoodsService();
        $goods_list = $service->loadShopGoodsList($category_id);
        echo json_encode($goods_list);
    }

    public function addDayGoods(){
        $partner_id = session('partner_id');
        $data = I();
        $data['partner_id'] = $partner_id;
        $goods_service = new GoodsService();
        $res = $goods_service->addDayGoods($data);
        echo json_encode($res);
    }

    public function addPreGoods()
    {
        $partner_id = session('partner_id');
        $data = I();
        $data['partner_id'] = $partner_id;
        $goods_service = new GoodsService();
        $res = $goods_service->addPreGoods($data);
        echo json_encode($res);
    }

    public function changeTableKey()
    {
        $info = I();
        $goods_service = new GoodsService();
        $res =$goods_service->changeTableKey($info);
        echo $res;
    }


    public function addGroupGoods()
    {
        $partner_id = session('partner_id');
        $data = I();
        $data['partner_id'] = $partner_id;
        $goods_service = new GoodsService();
        $res = $goods_service->addGroupGoods($data);
        echo json_encode($res);
    }

    public function changeDayGoodsSort(){
        $partner_id = session('partner_id');
        $data = I();
        $service = new GoodsService();
        $res = $service->changeDayGoodsSort($data);
        echo json_encode(array('status'=>'success'));
    }

    public function deleteDayGoods(){
        $id = I('id');
        $shop_id  = I('shop_id');
        $table = I('table','day_goods');
        $service = new GoodsService();
        $res = $service->deleteDayGoods($table,$shop_id,$id);
        echo json_encode(array('status'=>'success'));
    }

    //每日新品上下架
    public function dayGoodsDisply(){
        $id = I('id');
        $now_status = I('now_status');
        $table = I('table','day_goods');
        $service = new GoodsService();
        // var_dump(I());die;
        $service->dayGoodsDisply($table,$id,$now_status);
        echo json_encode(array('status'=>'ok'));
    }


    public function changeStore()
    {
        $id = I('id');
        $val = I('val','');
        $shop_id = I('shop_id');
        if(empty($shop_id)){
            return;
        }
        $service = new \Shop\Service\GoodsService();
        $res = $service->changeStore($id,$val,$shop_id);
    }

    public function barcodeList(){
        $p = I('p');
        $partner_id = session('partner_id');
        $service = new GoodsService();
        $list = $service->getBarcodeList($partner_id,$p);
        $this->assign('list',$list);
        $this->display();
    }

    public function addBarcode(){
        if(IS_POST){
            $service = new GoodsService();
            $data = I();
            $data['partner_id'] = session('partner_id');
            $res = $service->addBarcode($data);
            if($res == 'repeat'){
                echojson('添加失败，该条形码已经存在','','error');
            }else{
                echojson('添加成功',U('Goods/barcodeList'));
            }
        }else{
            $this->display();
        }

    }

    public function editBarcode(){
        $id = I('id');
        $service = new GoodsService();
        $barcode = $service->getBarcodeInfo($id);
        $child_list = $service->getChildBarcodeList($id);
        $this->assign('barcode',$barcode);
        $this->assign('child_list',$child_list);
        $this->display();
    }

    public function editBarcodeInfo(){
        $data = I();
        $data['partner_id'] = session('partner_id');
        $service = new GoodsService();
        $res = $service->editBarcodeInfo($data);
        if($res == 'repeat'){
            echo json_encode(array('msg'=>'repeat'));
        }else{
            echo json_encode(array('msg'=>'ok'));
        }
    }

    public function addChildBarcode(){
        $data = I();
        $data['partner_id'] = session('partner_id');
        $service = new GoodsService();
        $res = $service->addChildBarcode($data);
        if($res == 'repeat'){
            echo json_encode(array('msg'=>'repeat'));
        }else{
            echo json_encode(array('msg'=>'ok'));
        }
        
    }

    public function deleteChildBarcode(){
        $data = I();
        $data['partner_id'] = session('partner_id');
        $service = new GoodsService();
        $service->deleteChildBarcode($data);
        echo json_encode(array('msg'=>'ok'));
    }

    public function deleteBarcode(){
        $data = I();
        $data['partner_id'] = session('partner_id');
        $service = new GoodsService();
        $service->deleteBarcode($data);
        echojson('删除成功','refresh');
    }

    public function changeDayStore(){
        $partner_id = session('partner_id');
        $id = I('id');
        $store = I('store');
        $table = I('table','day_goods');
        $service = new GoodsService();
        $service->changeDayStore($id,$store);
    }

    public function addShopGoods(){
        if(IS_POST){
            $data = I();
            $partner_id = session('partner_id');
            $shop_ids = $data['shop_id'];
            //数组中去掉shop_id数组,方便直接插入数据库
            unset($data['shop_id']);
            //判断是否更换图片
            if ($_FILES['img']['name']) {
                // $data['img'] = $this->uploads();
                $data['img'] = upload('img','goodsImg');
            }

            if ($_FILES['detail_img1']['name']) {
                $file['detail_img1'] = upload('detail_img1','goodsImg');
                
            }else{
                $file['detail_img1'] = '';
            }

            if ($_FILES['detail_img2']['name']) {
                $file['detail_img2'] = upload('detail_img2','goodsImg');
                
            }else{
                $file['detail_img2'] = '';
            }

            if ($_FILES['detail_img3']['name']) {
                $file['detail_img3'] = upload('detail_img3','goodsImg');
                
            }else{
                $file['detail_img3'] = '';
            }

            $service = new GoodsService();
            $goods_id = $service->addGoods($data,$partner_id,$shop_ids,1);

            $extension = get_extension($_FILES['img']['name']);
            $file_name = $goods_id.'.'.$extension;
            $ossName = ossUploadImg($file_name,C('BASE_PATH').$data['img']);

            $service->updateGoodsImg($ossName,$goods_id);

            //三个详情图上传到阿里云上
            $service->ossGoodsDetailImg($goods_id,$file);

            clearGoodsCacheByBaseGoodsId($goods_id);
            $this->success('新增成功', 'index?shop_id='.$shop_ids[0]); 
        }else{
            $partner_id = session('partner_id');
            $goods_service = new GoodsService();
            $category = $goods_service->getCategory($partner_id);
            $shop_id = I('shop_id');
            $order_service = new OrderService();
            $shop_list = $order_service->getShopList();
            $this->assign('shop_id',$shop_id);
            $this->assign('shop_list',$shop_list);
            $this->assign('category',$category);
            $this->display();
        }
        
    }

    public function deleteGoodsFormat(){
        $format_id = I('format_id');
        $service = new GoodsService();
        $service->deleteGoodsFormat($format_id);
    }

    public function deleteGoodsAttr(){
        $attr_id = I('attr_id');
        $service = new GoodsService();
        $service->deleteGoodsAttr($attr_id);
    }

    public function editShopGoods(){
        if(IS_POST){
            $data = I();
            $partner_id = session('partner_id');
            $shop_id = $data['shop_id'];
            $selected_category_id = $data['selected_category_id'];
            //数组中去掉shop_id数组,方便直接插入数据库
            unset($data['shop_id']);

            $service = new GoodsService();
            $service->editShopGoods($data,$partner_id,$shop_id);

            clearGoodsCacheByBaseGoodsId($data['id']);
            $this->success('修改成功', 'index?shop_id='.$shop_id.'&category_id='.$selected_category_id);

        }else{
            $partner_id = session('partner_id');
            $id = I('id');
            $shop_id = I('shop_id');
            $selected_category_id = I('selected_category_id');
            $service = new GoodsService();
            $data = $service->getShopGoodsById($id);
            $category = $service->getCategory($partner_id);
            $has_barcode = $service->partnerHasBarcode($partner_id);
            $goods_format = $service->getShopGoodsFormat($id);
            $goods_attr = $service->getShopGoodsAttr($id);

            $service = new OrderService();
            $shop_list = $service->getShopList();
            $this->assign('data',$data);
            $this->assign('category',$category);
            $this->assign('shop_list',$shop_list);
            $this->assign('has_barcode',$has_barcode);
            $this->assign('goods_format',$goods_format);
            $this->assign('goods_attr',$goods_attr);
            $this->assign('shop_id',$shop_id);
            $this->assign('selected_category_id',$selected_category_id);
            $this->display();
        }
        
    }

    public function deleteShopGoodsFormat(){
        $format_id = I('format_id');
        $service = new GoodsService();
        $service->deleteShopGoodsFormat($format_id);
    }

    public function deleteShopGoodsAttr(){
        $attr_id = I('attr_id');
        $service = new GoodsService();
        $service->deleteShopGoodsAttr($attr_id);
    }

    public function changeGoodsSort(){
        $data = I();    
        $service = new GoodsService();
        $service->changeGoodsSort($data);
        echo json_encode(array('status'=>'success'));
    }

    public function changeShopGoodsSort(){
        $data = I();    
        $service = new GoodsService();
        $service->changeShopGoodsSort($data);
        echo json_encode(array('status'=>'success'));
    }
}