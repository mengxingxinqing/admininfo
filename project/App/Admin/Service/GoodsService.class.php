<?php
namespace Partner\Service;
use Partner\Model\GoodsModel;
use Partner\Model\CategoryModel;

class GoodsService extends BaseService{
	public function addGoods($data,$partner_id,$shop_ids,$single_shop=0){
		$category_model = new CategoryModel();
		$category = $category_model->getCategoryById($data['category_id']);
		$data['category_name'] = $category['name'];
		$data['create_time'] = time();
		//添加sm_base_goods表
		$model = new GoodsModel();
		// $data['sort'] = $model->getAddGoodSort($partner_id,$data['category_id']);

		//是否多规格
		if(count($data['price']) > 1){
			$data['is_multi'] = 1;
		}
		//商品详情
		if(!empty($data['goods_detail'])){
			$data['has_detail'] = 1;
		}

		$f_name_arr = $data['f_name']; 
		$price_arr = $data['price'];
		$discount_price_arr = $data['discount_price'];
		$lunchbox_num_arr = $data['lunchbox_num'];
		$lunchbox_price_arr = $data['lunchbox_price'];
		//base_goods表存第一个规格
		$data['f_name'] = $data['f_name'][0];
		$data['price'] = $data['price'][0];
		$data['discount_price'] = $data['discount_price'][0];
		$data['lunchbox_num'] = $data['lunchbox_num'][0];
		$data['lunchbox_price'] = $data['lunchbox_price'][0];
		$goods_id = $model->addGoods($data);

		//多规格
		if($data['is_multi'] == 1){
			foreach ($f_name_arr as $key => $value) {
				if($key == 0)continue;
				$format['goods_id'] = $goods_id;
				$format['f_name'] = $value;
				$format['f_price'] = $price_arr[$key];
				$format['discount_price'] = $discount_price_arr[$key];
				$format['lunchbox_price'] = $lunchbox_price_arr[$key];
				$format['lunchbox_num'] = $lunchbox_num_arr[$key];
				M('base_goods_format')->add($format);
			}
		}
		//商品属性
		if(isset($data['goods_attr_name'])){
			foreach($data['goods_attr_name'] as $key=>$value){
				$attr['goods_id'] = $goods_id;
				$attr['name'] = $value;
				$attr['content'] = $data['goods_attr_content'][$key];
				M('base_goods_attr')->add($attr);
			}
		}

		//添加sm_shop_goods表
		$category_id = $data['category_id'];

		if($single_shop == 1){
			$shop_list = M('shop')->where(array('id'=>$shop_ids[0]))->select();
		}else{
			$shop_list = $category_model->getShopListByPartner($partner_id);
		}

		foreach($shop_list as $key=>$shop){
			$data['shop_id'] = $shop['id'];
			$data['basegoods_id'] = $goods_id;
			$data['partner_id'] = $partner_id;

			//获得sm_shpop_category的id
			$shop_category_id = $model->getShopCategoryId($category_id,$shop['id']);
			$data['category_id'] = $shop_category_id;

			if(in_array($shop['id'], $shop_ids)){
				$data['is_display'] = 1;
			}else{
				$data['is_display'] = 0;
			}
			$shop_goods_id = $model->addShopGoods($data);

			//多规格
			if($data['is_multi'] == 1){
				foreach ($f_name_arr as $key => $value) {
					if($key == 0)continue;
					$format['goods_id'] = $shop_goods_id;
					$format['f_name'] = $value;
					$format['f_price'] = $price_arr[$key];
					$format['discount_price'] = $discount_price_arr[$key];
					$format['lunchbox_price'] = $lunchbox_price_arr[$key];
					$format['lunchbox_num'] = $lunchbox_num_arr[$key];
					M('shop_goods_format')->add($format);
				}
			}

			//商品属性
			if(isset($data['goods_attr_name'])){
				foreach($data['goods_attr_name'] as $key=>$value){
					$attr['goods_id'] = $shop_goods_id;
					$attr['name'] = $value;
					$attr['content'] = $data['goods_attr_content'][$key];
					M('shop_goods_attr')->add($attr);
				}
			}

		}

		return $goods_id;
	}

	public function editGoods($data,$partner_id,$shop_ids){
		//类别有可能变化，获得类别的名称
		$category_model = new CategoryModel();
		$category = $category_model->getCategoryById($data['category_id']);
		$data['category_name'] = $category['name'];

		//更新sm_base_goods表
		$model = new GoodsModel();
		$a = $model->getAddGoodSort($partner_id,$data['category_id']);
		$max_sort = ($model->getAddGoodSort($partner_id,$data['category_id']))-1;

		if($data['sort'] > $max_sort){
			$data['sort'] = $max_sort;
		}
		if($data['sort'] < 1){
			$data['sort'] = 1;
		}
		$old_sort = $model->getOldSort($data['id']);

		if($old_sort != $data['sort']){
			if($old_sort > $data['sort']){
				$pre_sort = $data['sort']-1;
				$next_sort = $data['sort'];
			}else{
				$pre_sort = $data['sort'];
				$next_sort = $data['sort']+1;
			}
			
			$model->setGoodsSort($old_sort,$pre_sort,$next_sort,$partner_id,$data['category_id'],$data['id']);
		}



		//是否多规格
		if(count($data['price']) > 1){
			$data['is_multi'] = 1;
		}else{
			$data['is_multi'] = 0;
		}
		//商品详情
		if(!empty($data['goods_detail'])){
			$data['has_detail'] = 1;
		}else{
			$data['has_detail'] = 0;
		}

		$f_name_arr = $data['f_name']; 
		$price_arr = $data['price'];
		$discount_price_arr = $data['discount_price'];
		$lunchbox_num_arr = $data['lunchbox_num'];
		$lunchbox_price_arr = $data['lunchbox_price'];
		$format_id_arr = $data['format_id'];
		//base_goods表存第一个规格
		$data['f_name'] = $data['f_name'][0];
		$data['price'] = $data['price'][0];
		$data['discount_price'] = $data['discount_price'][0];
		$data['lunchbox_num'] = $data['lunchbox_num'][0];
		$data['lunchbox_price'] = $data['lunchbox_price'][0];


		$res = $model->editGoods($data);


		//多规格
		if($data['is_multi'] == 1){
			foreach ($f_name_arr as $key => $value) {
				if($key == 0)continue;
				$format['f_name'] = $value;
				$format['f_price'] = $price_arr[$key];
				$format['discount_price'] = $discount_price_arr[$key];
				$format['lunchbox_price'] = $lunchbox_price_arr[$key];
				$format['lunchbox_num'] = $lunchbox_num_arr[$key];

				$format_id = $format_id_arr[$key];
				if($format_id == 0){
					$format['goods_id'] = $data['id'];
					M('base_goods_format')->add($format);
				}else{
					M('base_goods_format')->where(array('id'=>$format_id))->save($format);
				}
			}
		}

		//商品属性
		if(isset($data['goods_attr_name'])){
			foreach($data['goods_attr_name'] as $key=>$value){
				$attr['name'] = $value;
				$attr['content'] = $data['goods_attr_content'][$key];

				$attr_id = $data['attr_id'][$key];
				if($attr_id == 0){
					$attr['goods_id'] = $data['id'];
					M('base_goods_attr')->add($attr);
				}else{
					M('base_goods_attr')->where(array('id'=>$attr_id))->save($attr);
				}
			}
		}



		$basegoods_id = $data['id'];
		M('shop_goods')->where(array('basegoods_id'=>$basegoods_id))->save(array('name'=>$data['name']));
		/*$basegoods_id = $data['id'];	
		unset($data['id']);
		$category_id = $data['category_id'];

		//更新sm_shop_goods表
		foreach($shop_ids as $key=>$shop_id){
			$data['is_off'] = 0;
			//获得sm_shpop_category的id
			$shop_category_id = $model->getShopCategoryId($category_id,$shop_id);
			$data['category_id'] = $shop_category_id;
			$is_set = $model->isSetShopGoods($basegoods_id,$shop_id);
			if($is_set){
				$model->editShopGoods($data,$basegoods_id,$shop_id);
			}else{
				$data['shop_id'] = $shop_id;
				$data['basegoods_id'] = $basegoods_id;
				$data['partner_id'] = $partner_id;
				$model->addShopGoods($data);
			}
		}*/

		return true;
	}

	public function deleteGoods($id){
		$model = new GoodsModel();
		$model->deleteGoods($id);
		$model->deleteShopGoods($id);
		$model->setSortByDelete($id);
	}

	public function deleteShopGoods($id){
		$model = new GoodsModel();
		$model->deleteShopGoodsById($id);
	}

	public function setGoodsSort($this_sort,$pre_sort,$next_sort,$partner_id,$category_id,$this_goods_id){
		$model = new GoodsModel();
		$model->setGoodsSort($this_sort,$pre_sort,$next_sort,$partner_id,$category_id,$this_goods_id);
	}

	public function getDayNewGoodsSet($shop_id){
		$model = new GoodsModel();
		$res = $model->getDayNewGoodsSet($shop_id);
		if(empty($res)){
			$res = array(
				array('day_name'=>'1','status'=>0),
				array('day_name'=>'2','status'=>0),
				array('day_name'=>'3','status'=>0),
				array('day_name'=>'4','status'=>0),
				array('day_name'=>'5','status'=>0),
				array('day_name'=>'6','status'=>0),
				array('day_name'=>'7','status'=>0),
			);
		}	
		return $res;
	}

	public function getDisCountList($shop_id){
		$model = new GoodsModel();
		$res = $model->getDisCountList($shop_id);
		if(empty($res)){
			$res = array(
				array('days'=>'1','discount_money'=>0),
				array('days'=>'2','discount_money'=>0),
				array('days'=>'3','discount_money'=>0),
				array('days'=>'4','discount_money'=>0),
				array('days'=>'5','discount_money'=>0),
				array('days'=>'6','discount_money'=>0),
			);
		}
		return $res;
	}

	public function dayNewGoodsSet($data){
		$model = new GoodsModel();
		$model->updateDayNewGoodsSet($data['partner_id'],$data['shop_id'],$data['day']);
		$model->updateDayDiscount($data['partner_id'],$data['shop_id'],$data['advance_day'],$data['discount']);
		$data1['close_today_dayset'] = $data['close_today_dayset'];
		$data1['dayset_announce'] = $data['dayset_announce'];
		$model->updateShopConf($data['shop_id'],$data1);
	}

	public function getLabelDate($shop_id,$close_today_dayset){
		$day_list = getWeekDateList();
		$model = new GoodsModel();
		$dayset_list = self::getDayNewGoodsSet($shop_id);
		$open = array();
		foreach($dayset_list as $key=>$dayset){
			if($dayset['status'] == 1){
				$open[] = $dayset['day_name'];
			}
		}

		foreach($day_list as $key=>$day){
			if(in_array($day['number_week'], $open)){
				$day_list[$key]['is_open'] = 1;
			}else{
				$day_list[$key]['is_open'] = 0;
			}
		}

		if($close_today_dayset == 1){
			unset($day_list[0]);
		}
		return $day_list;

		
	}

	public function addDayGoods($data){
		$model = new GoodsModel();
		$is_set = $model->isSetDayNewGoodsSet($data['shop_id']);
		if($is_set != 0){
			$res = $model->addDayGoods($data);
			return $res;
		}else{
			return array('status'=>'no_set','msg'=>'该店铺未设置“每日新品设置”,请前往设置');
		}
	}

    public function addPreGoods($data)
    {
        $model = new GoodsModel();
        $res = $model->addPreGoods($data);
        return $res;
	}

    public function addGroupGoods($data)
    {
        $model = new GoodsModel();
        $res = $model->addGroupGoods($data);
        return $res;
	}


	public function editShopGoods($data,$partner_id,$shop_ids){
		//类别有可能变化，获得类别的名称
		/*$category_model = new CategoryModel();
		$category = $category_model->getCategoryById($data['category_id']);
		$data['category_name'] = $category['name'];*/
		$model = new GoodsModel();
		//是否多规格
		if(count($data['price']) > 1){
			$data['is_multi'] = 1;
		}else{
			$data['is_multi'] = 0;
		}
		//商品详情
		if(!empty($data['goods_detail'])){
			$data['has_detail'] = 1;
		}else{
			$data['has_detail'] = 0;
		}

		$f_name_arr = $data['f_name']; 
		$price_arr = $data['price'];
		$discount_price_arr = $data['discount_price'];
		$lunchbox_num_arr = $data['lunchbox_num'];
		$lunchbox_price_arr = $data['lunchbox_price'];
		$format_id_arr = $data['format_id'];
		//base_goods表存第一个规格
		$data['f_name'] = $data['f_name'][0];
		$data['price'] = $data['price'][0];
		$data['discount_price'] = $data['discount_price'][0];
		$data['lunchbox_num'] = $data['lunchbox_num'][0];
		$data['lunchbox_price'] = $data['lunchbox_price'][0];


		$res = $model->editShopGoodsById($data);


		//多规格
		if($data['is_multi'] == 1){
			foreach ($f_name_arr as $key => $value) {
				if($key == 0)continue;
				$format['f_name'] = $value;
				$format['f_price'] = $price_arr[$key];
				$format['discount_price'] = $discount_price_arr[$key];
				$format['lunchbox_price'] = $lunchbox_price_arr[$key];
				$format['lunchbox_num'] = $lunchbox_num_arr[$key];

				$format_id = $format_id_arr[$key];
				if($format_id == 0){
					$format['goods_id'] = $data['id'];
					M('shop_goods_format')->add($format);
				}else{
					M('shop_goods_format')->where(array('id'=>$format_id))->save($format);
				}
			}
		}

		//商品属性
		if(isset($data['goods_attr_name'])){
			foreach($data['goods_attr_name'] as $key=>$value){
				$attr['name'] = $value;
				$attr['content'] = $data['goods_attr_content'][$key];

				$attr_id = $data['attr_id'][$key];
				if($attr_id == 0){
					$attr['goods_id'] = $data['id'];
					M('shop_goods_attr')->add($attr);
				}else{
					M('shop_goods_attr')->where(array('id'=>$attr_id))->save($attr);
				}
			}
		}
		return true;
	}

	public function ossGoodsDetailImg($goods_id,$file){
		if($file['detail_img1']){
			$extension = get_extension($file['detail_img1']);
	        $file_name = $goods_id.'_detail_img1.'.$extension;
	        $ossName['detail_img1'] = ossUploadImg($file_name,C('BASE_PATH').$file['detail_img1']);
		}

		if($file['detail_img2']){
			$extension = get_extension($file['detail_img2']);
	        $file_name = $goods_id.'_detail_img2.'.$extension;
	        $ossName['detail_img2'] = ossUploadImg($file_name,C('BASE_PATH').$file['detail_img2']);
		}

		if($file['detail_img3']){
			$extension = get_extension($file['detail_img3']);
	        $file_name = $goods_id.'_detail_img3.'.$extension;
	        $ossName['detail_img3'] = ossUploadImg($file_name,C('BASE_PATH').$file['detail_img3']);
		}
		
		$new_file = '';
		foreach($ossName as $key=> $oss){	
			if(!empty($oss)){
				$new_file .= $oss.',';
			}
		}
		$new_file = trim($new_file,',');
		$model = new GoodsModel();
		$model->updateGoodsDeatilImg($new_file,$goods_id);
	}

	//$file 存储新上传的图片，$data 存储 未变化的图片
	public function editOssGoodsDetailImg($data,$file){
		$goods_id = $data['id'];
		if($file['detail_img1']){
			$extension = get_extension($file['detail_img1']);
	        $file_name = $goods_id.'_detail_img1.'.$extension;
	        $ossName['detail_img1'] = ossUploadImg($file_name,C('BASE_PATH').$file['detail_img1']);
		}else{
			$ossName['detail_img1'] = $data['detail_img1'];
		}

		if($file['detail_img2']){
			$extension = get_extension($file['detail_img2']);
	        $file_name = $goods_id.'_detail_img2.'.$extension;
	        $ossName['detail_img2'] = ossUploadImg($file_name,C('BASE_PATH').$file['detail_img2']);
		}else{
			$ossName['detail_img2'] = $data['detail_img2'];
		}

		if($file['detail_img3']){
			$extension = get_extension($file['detail_img3']);
	        $file_name = $goods_id.'_detail_img3.'.$extension;
	        $ossName['detail_img3'] = ossUploadImg($file_name,C('BASE_PATH').$file['detail_img3']);
		}else{
			$ossName['detail_img3'] = $data['detail_img3'];
		}
		$new_file = '';
		foreach($ossName as $key=> $oss){	
			if(!empty($oss)){
				$new_file .= $oss.',';
			}
		}
		$new_file = trim($new_file,',');
		$model = new GoodsModel();
		$model->updateGoodsDeatilImg($new_file,$data['id']);
	}
}