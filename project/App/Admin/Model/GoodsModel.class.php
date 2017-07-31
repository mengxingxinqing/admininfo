<?php
namespace Partner\Model;
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-05-17
 * Time: 16:32
 */
class GoodsModel extends BaseModel
{
    public function addGoods($data){
        $data['partner_id'] = session('partner_id');
        $sort = $this->getAddGoodSort($data['partner_id'],$data['category_id']);
        $data['sort'] = $sort;
        return M('base_goods')->add($data);
    }

    public function addShopGoods($data){
        $sort = $this->getAddShopGoodSort($data['shop_id'],$data['category_id']);
        $data['sort'] = $sort;
        $res = M('shop_goods')->add($data);
        return $res;
    }

    public function getGoodsList($p,$category_id){

        $where = ' partner_id='.session('partner_id');
        if($category_id){
            $where .= ' AND category_id='.$category_id;
        }
        $where .= ' AND is_del=0';

    	$sql = "SELECT * FROM sm_base_goods WHERE ".$where." ORDER BY sort";
    	return $this->getPage($sql,$p);
    }

    public function getGoodsListByShop($p,$shop_id,$category_id){
        $where = ' sg.shop_id='.$shop_id;
        if($category_id){
            $where .= ' AND sg.category_id='.$category_id;
        }
        $where .= ' AND sg.is_del=0';
        $where .= ' AND sc.is_off=0';
        $sql = "SELECT sg.* FROM sm_shop_goods sg LEFT JOIN sm_shop_category sc ON sg.category_id=sc.id WHERE ".$where." ORDER BY sg.sort";
        return $this->getPage($sql,$p);
    }

    public function getGoodsById($id){
    	$res = M('base_goods')->where(array('id'=>$id))->find();
        $detail_img = $res['detail_img'];
        $detail_img_arr = explode(',',$detail_img);
        $res['detail_img1'] = '';
        $res['detail_img2'] = '';
        $res['detail_img3'] = '';
        foreach($detail_img_arr as $key=>$img){
            $pos = strpos($img, 'img1');
            if($pos !== false){
                $res['detail_img1'] = $img;
                continue;
            }
            $pos = strpos($img, 'img2');
            if($pos !== false){
                $res['detail_img2'] = $img;
                continue;
            }
            $pos = strpos($img, 'img3');
            if($pos !== false){
                $res['detail_img3'] = $img;
                continue;
            }
        }
        return $res;
    }

    public function editGoods($data){
    	 return M('base_goods')->where(array('id'=>$data['id']))->save($data);
    }

    public function editShopGoods($data,$basegoods_id,$shop_id){       
        $res = M('shop_goods')->where(array('basegoods_id'=>$basegoods_id,'shop_id'=>$shop_id))->save($data);
        return $res;
    }

    public function deleteGoods($id){
    	return M('base_goods')->where(array('id'=>$id))->save(array('is_del'=>1));
    }

    public function deleteShopGoods($basegoods_id){
        return M('shop_goods')->where(array('basegoods_id'=>$basegoods_id))->save(array('is_del'=>1));
    }

    public function deleteShopGoodsById($id){
        return M('shop_goods')->where(array('id'=>$id))->save(array('is_del'=>1));
    }

    public function changeStatus($id,$now_status){
        $new_status = $now_status == 0 ? 1:0;
        M('shop_goods')->where(array('id'=>$id))->save(array('is_off'=>$new_status));
    }

    public function getCategory($partner_id){
        $res = M('base_category')->where(array('partner_id'=>$partner_id,'is_del'=>0))->order('sort')->select();
        return $res;
    }

    public function getCategoryByShop($partner_id,$shop_id){
        $where['partner_id'] = $partner_id;
        if($shop_id){
            $where['shop_id'] = $shop_id;
        }
        $where['is_del'] = 0;
        $where['is_off'] = 0;
        $res = M('shop_category')->where($where)->order('sort')->select();
        return $res;
    }

    public function editGroupMeal($pid,$sid,$data)
    {
        $num = M('groupmeal_setting')->where(array('partner_id'=>$pid,'shop_id'=>$sid))->count();
        if($num>0){
            M('groupmeal_setting')->where(array('partner_id'=>$pid,'shop_id'=>$sid))->save($data);
        }else{
            $data['partner_id'] = $pid;
            $data['shop_id'] = $sid;
            M('groupmeal_setting')->add($data);
        }
    }

    public function getSelectedShop($id){
        return M('shop_goods')->where(array('basegoods_id'=>$id))->getField('shop_id',true);
    }


    public function getShopCategoryId($base_id,$shop_id){
        $id = M('shop_category')->where(array('base_id'=>$base_id,'shop_id'=>$shop_id))->getField('id');
        return $id;

    }

    public function isSetShopGoods($basegoods_id,$shop_id){
        $res = M('shop_goods')->where(array('basegoods_id'=>$basegoods_id,'shop_id'=>$shop_id))->count();
        return $res;
    }

    //商品是否在前台显示
    public function goodsDisply($goods_id,$now_status){
       $new_status = $now_status == 0 ? 1:0; 
       $res = M('shop_goods')->where(array('id'=>$goods_id))->save(array('is_display'=>$new_status));
       return $res;
    }

    //获得新增商品的排序
    public function getAddGoodSort($partner_id,$category_id){
        $sql = "SELECT MAX(sort) AS max_sort FROM sm_base_goods WHERE partner_id=$partner_id AND category_id=$category_id AND is_del=0";
        $res = M()->query($sql);
        if(empty($res[0]['max_sort'])){
            $res[0]['max_sort'] = 0;
        }
        return $res[0]['max_sort']+1;
    }

    public function getOldSort($goods_id){
        $sort = M('base_goods')->where(array('id'=>$goods_id))->getField('sort');
        return $sort;
    }

    public function setGoodsSort($this_sort,$pre_sort,$next_sort,$partner_id,$category_id,$this_goods_id){
        $category_name = M('base_category')->where(array('id'=>$category_id))->getField('name');
        if($this_sort > $pre_sort){
            //上移
            $sql = "UPDATE sm_base_goods SET sort=sort+1 WHERE category_id=$category_id AND partner_id=$partner_id AND sort>=$next_sort AND sort<$this_sort";
            M()->execute($sql);
            $sql = "UPDATE sm_shop_goods SET sort=sort+1 WHERE category_name='".$category_name."' AND partner_id=$partner_id AND sort>=$next_sort AND sort<$this_sort";
            M()->execute($sql);
            M('base_goods')->where(array('id'=>$this_goods_id))->save(array('sort'=>$next_sort));
            M('shop_goods')->where(array('basegoods_id'=>$this_goods_id))->save(array('sort'=>$next_sort));

        }else{

            //下移
            $sql = "UPDATE sm_base_goods SET sort=sort-1 WHERE category_id=$category_id AND partner_id=$partner_id AND sort>$this_sort AND sort<=$pre_sort";
            M()->execute($sql);
            $sql = "UPDATE sm_shop_goods SET sort=sort-1 WHERE category_name='".$category_name."' AND partner_id=$partner_id AND sort>$this_sort AND sort<=$pre_sort";
            M()->execute($sql);
            M('base_goods')->where(array('id'=>$this_goods_id))->save(array('sort'=>$pre_sort));
            M('shop_goods')->where(array('basegoods_id'=>$this_goods_id))->save(array('sort'=>$pre_sort));
        }

    }

    public function setSortByDelete($goods_id){
        $good_info = M('base_goods')->where(array('id'=>$goods_id))->find();
        $sql = "UPDATE sm_base_goods SET sort=sort-1 WHERE sort>".$good_info['sort']." AND category_id=".$good_info['category_id'];
        M()->execute($sql);
        $sql = "UPDATE sm_shop_goods SET sort=sort-1 WHERE sort>".$good_info['sort']." AND category_name='".$good_info['category_name']."' AND partner_id=".$good_info['partner_id'];
        M()->execute($sql);
    }

    public function updateGoodsImg($ossName,$goods_id){
        M('base_goods')->where(array('id'=>$goods_id))->save(array('img'=>$ossName));
        M('shop_goods')->where(array('basegoods_id'=>$goods_id))->save(array('img'=>$ossName));
    }

    public function getDayNewGoodsSet($shop_id){
        $res = M('day_set')->where(array('shop_id'=>$shop_id))->select();
        return $res;
    }

    public function getDisCountList($shop_id){
        $res = M('day_discount')->where(array('shop_id'=>$shop_id))->select();
        return $res;
    }

    //修改每日新品日期设置
    public function updateDayNewGoodsSet($partner_id,$shop_id,$data){
        $res = M('day_set')->where(array('shop_id'=>$shop_id))->select();
        if(empty($res)){
            //添加
            for($i=1;$i<=7;$i++){
                M('day_set')->add(array('partner_id'=>$partner_id,'shop_id'=>$shop_id,'day_name'=>$i));
            }

        }else{
            //更新
            M('day_set')->where(array('shop_id'=>$shop_id))->save(array('status'=>0));
        }

        if(!empty($data)){
            foreach($data as $key=>$value){
                M('day_set')->where(array('shop_id'=>$shop_id,'day_name'=>$value))->save(array('status'=>1));
            }
        }
    }

    //修改每日新品优惠设置
    public function updateDayDiscount($partner_id,$shop_id,$advance_day,$discount){
        $res = M('day_discount')->where(array('shop_id'=>$shop_id))->select();
        if(empty($res)){
            //添加
            foreach($advance_day as $key=>$value){
                $data['partner_id'] = $partner_id;
                $data['shop_id'] = $shop_id;
                $data['days'] = $value;
                $data['discount_money'] = $discount[$key];
                M('day_discount')->add($data);
            }
        }else{
            //更新
            foreach($advance_day as $key=>$value){
                $data['discount_money'] = $discount[$key];
                M('day_discount')->where(array('shop_id'=>$shop_id,'days'=>$value))->save($data);
            }
        }
    }

    public function loadShopGoodsList($category_id){
        $res = M('shop_goods')->where(array('category_id'=>$category_id,'is_del'=>0))->select();
        return $res;
    }

    public function getDayId($shop_id,$day_number){
        $id = M('day_set')->where(array('shop_id'=>$shop_id,'day_name'=>$day_number))->getField('id');
        return $id;
    }

    //添加每日新品
    /**
    $data['partner_id'],$data['shop_id'],$data['selected_day'],$data['category_id'],$data['goods_id']
    */
    public function addDayGoods($data){
        $day_id = M('day_set')->where(array('day_name'=>$data['selected_day'],'shop_id'=>$data['shop_id']))->getField('id');
        $data1['partner_id'] = $data['partner_id'];
        $data1['shop_id'] = $data['shop_id'];
        $data1['category_id'] = $data['category_id'];
        $data1['goods_id'] = $data['goods_id'];
        $data1['day_id'] = $day_id;
        $count = M('day_goods')->where(array($data1))->count();
        if($count == 0){
            $id = M('day_goods')->add($data1);
            M('day_goods')->where(array('id'=>$id))->save(array('sort'=>$id));
            $res['status'] = 'success';
        }else{
            $res['status']  = 'error';
            $res['msg'] = '已经添加该商品';
        } 
        return $res;
    }

    public function addPreGoods($data)
    {
        $data1['partner_id'] = $data['partner_id'];
        $data1['shop_id'] = $data['shop_id'];
        $data1['category_id'] = $data['category_id'];
        $data1['goods_id'] = $data['goods_id'];
        $data1['type'] = $data['selected_day'];
        $count = M('pre_goods')->where(array($data1))->count();
        if($count == 0){
            $id = M('pre_goods')->add($data1);
            M('pre_goods')->where(array('id'=>$id))->save(array('sort'=>$id));
            $res['status'] = 'success';
        }else{
            $res['status']  = 'error';
            $res['msg'] = '已经添加该商品';
        }
        return $res;
    }

    public function addGroupGoods($data)
    {
        $data1['partner_id'] = $data['partner_id'];
        $data1['shop_id'] = $data['shop_id'];
        $data1['category_id'] = $data['category_id'];
        $data1['goods_id'] = $data['goods_id'];
        $data1['price'] = M('shop_goods')->where(array('id'=>$data['goods_id']))->getField('price');
        $count = M('groupmeal_goods')->where(array($data1))->count();
        if($count == 0){
            $id = M('groupmeal_goods')->add($data1);
            M('groupmeal_goods')->where(array('id'=>$id))->save(array('sort'=>$id));
            $res['status'] = 'success';
        }else{
            $res['status']  = 'error';
            $res['msg'] = '已经添加该商品';
        }
        return $res;
    }

    public function changeTableKey($info)
    {
        M($info['table'])->where(array('id'=>$info['id']))->save(array($info['key']=>$info['val']));
    }

    public function dayNewGoodsList($partner_id,$shop_id,$selected_day){
        $day_id = M('day_set')->where(array('day_name'=>$selected_day,'shop_id'=>$shop_id))->getField('id');
        $sql = "SELECT dg.id AS day_goods_id,dg.status AS day_goods_status,dg.store AS day_store,sg.* FROM sm_day_goods dg LEFT JOIN sm_shop_category sc ON dg.category_id=sc.id LEFT JOIN sm_shop_goods sg ON dg.goods_id=sg.id WHERE dg.partner_id=".$partner_id." AND dg.shop_id=".$shop_id." AND dg.day_id=".$day_id." ORDER BY dg.sort";
        $res = M()->query($sql); 
        return $res;
    }

    public function preGoodsList($partner_id, $shop_id, $selected_day)
    {
        $sql = "SELECT dg.id AS day_goods_id,dg.status AS day_goods_status,dg.store AS day_store,sg.* FROM sm_pre_goods dg LEFT JOIN sm_shop_category sc ON dg.category_id=sc.id LEFT JOIN sm_shop_goods sg ON dg.goods_id=sg.id WHERE dg.partner_id=".$partner_id." AND dg.shop_id=".$shop_id." AND dg.type=".$selected_day." ORDER BY dg.sort";
        $res = M()->query($sql);
        return $res;
    }

    public function groupGoodsList($partner_id, $shop_id)
    {
        $sql = "SELECT dg.id AS day_goods_id,dg.status AS day_goods_status,dg.num as day_num,dg.price as day_price,sg.* FROM sm_groupmeal_goods dg LEFT JOIN sm_shop_category sc ON dg.category_id=sc.id LEFT JOIN sm_shop_goods sg ON dg.goods_id=sg.id WHERE dg.partner_id=".$partner_id." AND dg.shop_id=".$shop_id." ORDER BY dg.sort";
//        echo $sql;die;
        $res = M()->query($sql);
        return $res;
    }

    //修改每日新品排序
    /**
        $data['id'],$data['direction'],$data['selected_day'],$data['shop_id']
    */
    public function changeDayGoodsSort($data){
        $table = $data['table']?$data['table']:"day_goods";
        if($table == 'day_goods'){
            $day_id = self::getDaySetId($data['shop_id'],$data['selected_day']);
            $where['day_id'] = $day_id;
        }else{
            $where['type'] = $data['selected_day'];
        }


        $now_sort = M($table)->where(array('id'=>$data['id']))->getField('sort');
        if($data['direction'] == 'up'){
            //上移
            $where['shop_id'] = $data['shop_id'];

            $where['sort'] = array('lt',$now_sort);
            $up_res = M($table)->where($where)->order('sort desc')->find();
            if(!empty($up_res)){
                $up_sort = $up_res['sort'];
                //修改自己
                M($table)->where(array('id'=>$data['id']))->save(array('sort'=>$up_sort));
                //修改上方
                M($table)->where(array('id'=>$up_res['id']))->save(array('sort'=>$now_sort));
            }else{
               //已经是第一个 
            }
        }else{
            //下移动
            $where['shop_id'] = $data['shop_id'];
            $where['sort'] = array('gt',$now_sort);
            $down_res = M($table)->where($where)->order('sort')->find();
            if(!empty($down_res)){
                $down_sort = $down_res['sort'];
                //修改自己
                M($table)->where(array('id'=>$data['id']))->save(array('sort'=>$down_sort));
                //修改上方
                M($table)->where(array('id'=>$down_res['id']))->save(array('sort'=>$now_sort));
            }else{
               //已经是最后一个 
            }

        }
    }

    public function getDaySetId($shop_id,$day){
        $day_id = M('day_set')->where(array('day_name'=>$day,'shop_id'=>$shop_id))->getField('id');
        return $day_id;
    }

    public function deleteDayGoods($table,$shop_id,$id){
        M($table)->where(array('shop_id'=>$shop_id,'id'=>$id))->delete();
    }

    //检测店铺是否设置了每日新品设置
    public function isSetDayNewGoodsSet($shop_id){
        $res = M('day_set')->where(array('shop_id'=>$shop_id))->count();
        if($res > 0){
            $status = 1;
        }else{
            $status = 0;
        }
        return $status;
    }

    public function updateShopConf($shop_id,$data){
        M('shop_conf')->where(array('shop_id'=>$shop_id))->save($data);
    }

    //每日新品上下架，与商品自身上下架无关
    public function dayGoodsDisply($table,$id,$now_status){
       $new_status = $now_status == 0 ? 1:0; 
       $res = M($table)->where(array('id'=>$id))->save(array('status'=>$new_status));
       return $res;
    }

    public function getBarcodeList($partner_id){
        $sql = "SELECT * FROM sm_barcode WHERE partner_id=".$partner_id;
        return $this->getPage($sql,$p);
    }

    public function addBarcode($data){
        $res = M('barcode')->where(array('barcode'=>$data['barcode'],'partner_id'=>$data['partner_id']))->find();
        if(empty($res)){
            M('barcode')->add($data);
            return 'ok';
        }else{
            return 'repeat';
        }
        
    }

    public function getBarcodeInfo($barcode_id){
        $res = M('barcode')->where(array('id'=>$barcode_id))->find();
        return $res;
    }

    public function getChildBarcodeList($barcode_id){
        $res = M('barcode_child')->where(array('barcode_id'=>$barcode_id))->select();
        return $res;
    }

    public function  editBarcodeInfo($data){
        $where['barcode'] = $data['barcode'];
        $wehre['partner_id'] = $data['partner_id'];
        $where['id'] = array('neq',$data['barcode_id']);
        $res = M('barcode')->where($where)->find();
        if(empty($res)){
            M('barcode')->where(array('id'=>$data['barcode_id']))->save(array('barcode'=>$data['barcode'],'name'=>$data['name']));
            $res = 'ok';
        }else{
            $res = 'repeat';
        }
        return $res;
    }

    public function addChildBarcode($data){
        $res = M('barcode_child')->where(array('barcode'=>$data['barcode'],'partner_id'=>$data['partner_id']))->find();
        if(empty($res)){
            M('barcode_child')->add($data);
            return 'ok';
        }else{
            return 'repeat';
        }
        
    }

    public function deleteChildBarcode($data){
        M('barcode_child')->where(array('id'=>$data['id'],'partner_id'=>$data['partner_id']))->delete();
    }

    public function deleteBarcode($data){
       M('barcode_child')->where(array('barcode_id'=>$data['id'],'partner_id'=>$data['partner_id']))->delete();
       M('barcode')->where(array('id'=>$data['id'],'partner_id'=>$data['partner_id']))->delete();
    }

    public function partnerHasBarcode($partner_id){
        $res = M('partner_conf')->where(array('partner_id'=>$partner_id))->getField('has_barcode');
        return $res;
    }

    public function changeDayStore($table,$id,$store){
        if($store === ''){
            $store = null;
        }
        M($table)->where(array('id'=>$id))->save(array('store'=>$store));
    }

    public function getBaseGoodsFormat($goods_id){
        $res = M('base_goods_format')->where(array('goods_id'=>$goods_id))->select();
        return $res;
    }

    public function getShopGoodsFormat($goods_id){
        $res = M('shop_goods_format')->where(array('goods_id'=>$goods_id))->select();
        return $res;
    }

    public function getBaseGoodsAttr($goods_id){
        $res = M('base_goods_attr')->where(array('goods_id'=>$goods_id))->select();
        return $res;
    }

    public function getShopGoodsAttr($goods_id){
        $res = M('shop_goods_attr')->where(array('goods_id'=>$goods_id))->select();
        return $res;
    }

    public function deleteGoodsFormat($id){
        M('base_goods_format')->where(array('id'=>$id))->delete();
    }

    public function deleteGoodsAttr($id){
        M('base_goods_attr')->where(array('id'=>$id))->delete();
    }

    public function getShopGoodsById($id){
        $res = M('shop_goods')->where(array('id'=>$id))->find();
        $detail_img = $res['detail_img'];
        $detail_img_arr = explode(',',$detail_img);
        $res['detail_img1'] = '';
        $res['detail_img2'] = '';
        $res['detail_img3'] = '';
        foreach($detail_img_arr as $key=>$img){
            $pos = strpos($img, 'img1');
            if($pos !== false){
                $res['detail_img1'] = $img;
                continue;
            }
            $pos = strpos($img, 'img2');
            if($pos !== false){
                $res['detail_img2'] = $img;
                continue;
            }
            $pos = strpos($img, 'img3');
            if($pos !== false){
                $res['detail_img3'] = $img;
                continue;
            }
        }
        return $res;

    }

    public function editShopGoodsById($data){
         return M('shop_goods')->where(array('id'=>$data['id']))->save($data);
    }

    public function deleteShopGoodsFormat($id){
        M('shop_goods_format')->where(array('id'=>$id))->delete();
    }

    public function deleteShopGoodsAttr($id){
        M('shop_goods_attr')->where(array('id'=>$id))->delete();
    }

    //获得shop新增商品的排序
    public function getAddShopGoodSort($shop_id,$category_id){
        $sql = "SELECT MAX(sort) AS max_sort FROM sm_shop_goods WHERE shop_id=$shop_id AND category_id=$category_id AND is_del=0";
        $res = M()->query($sql);
        if(empty($res[0]['max_sort'])){
            $res[0]['max_sort'] = 0;
        }
        return $res[0]['max_sort']+1;
    }

    //修改base餐品排序
    /**
        $data['id'],$data['direction']
    */
    public function changeGoodsSort($data){        
        $goods_info = M('base_goods')->where(array('id'=>$data['id']))->find();
        $now_sort = $goods_info['sort'];
        if($data['direction'] == 'up'){
            //上移
            $where['partner_id'] = $goods_info['partner_id'];
            $where['category_id'] = $goods_info['category_id'];
            $where['is_del'] = 0;
            $where['sort'] = array('lt',$now_sort);
            $up_res = M('base_goods')->where($where)->order('sort desc')->find();
            if(!empty($up_res)){
                $up_sort = $up_res['sort'];
                //修改自己
                M('base_goods')->where(array('id'=>$data['id']))->save(array('sort'=>$up_sort));
                //修改上方
                M('base_goods')->where(array('id'=>$up_res['id']))->save(array('sort'=>$now_sort));
            }else{
               //已经是第一个 
            }
        }else{
            //下移动
            $where['partner_id'] = $goods_info['partner_id'];
            $where['category_id'] = $goods_info['category_id'];
            $where['is_del'] = 0;
            $where['sort'] = array('gt',$now_sort);
            $down_res = M('base_goods')->where($where)->order('sort')->find();
            if(!empty($down_res)){
                $down_sort = $down_res['sort'];
                //修改自己
                M('base_goods')->where(array('id'=>$data['id']))->save(array('sort'=>$down_sort));
                //修改上方
                M('base_goods')->where(array('id'=>$down_res['id']))->save(array('sort'=>$now_sort));
            }else{
               //已经是最后一个 
            }

        }
    }

    //修改base餐品排序
    /**
        $data['id'],$data['direction']
    */
    public function changeShopGoodsSort($data){        
        $goods_info = M('shop_goods')->where(array('id'=>$data['id']))->find();
        $now_sort = $goods_info['sort'];
        if($data['direction'] == 'up'){
            //上移
            $where['shop_id'] = $goods_info['shop_id'];
            $where['category_id'] = $goods_info['category_id'];
            $where['is_del'] = 0;
            $where['sort'] = array('lt',$now_sort);
            $up_res = M('shop_goods')->where($where)->order('sort desc')->find();
            if(!empty($up_res)){
                $up_sort = $up_res['sort'];
                //修改自己
                M('shop_goods')->where(array('id'=>$data['id']))->save(array('sort'=>$up_sort));
                //修改上方
                M('shop_goods')->where(array('id'=>$up_res['id']))->save(array('sort'=>$now_sort));
            }else{
               //已经是第一个 
            }
        }else{
            //下移动
            $where['shop_id'] = $goods_info['shop_id'];
            $where['category_id'] = $goods_info['category_id'];
            $where['is_del'] = 0;
            $where['sort'] = array('gt',$now_sort);
            $down_res = M('shop_goods')->where($where)->order('sort')->find();
            if(!empty($down_res)){
                $down_sort = $down_res['sort'];
                //修改自己
                M('shop_goods')->where(array('id'=>$data['id']))->save(array('sort'=>$down_sort));
                //修改上方
                M('shop_goods')->where(array('id'=>$down_res['id']))->save(array('sort'=>$now_sort));
            }else{
               //已经是最后一个 
            }

        }
    }

    public function updateGoodsDeatilImg($ossName,$goods_id){
        M('base_goods')->where(array('id'=>$goods_id))->save(array('detail_img'=>$ossName));
        M('shop_goods')->where(array('basegoods_id'=>$goods_id))->save(array('detail_img'=>$ossName));
    }

    public function getGroupSetting($pid, $sid)
    {
        $res = M('groupmeal_setting')->where(array('partner_id'=>$pid,'shop_id'=>$sid))->find();
//        echo M()->getLastSql();die;
        return $res;
    }
}