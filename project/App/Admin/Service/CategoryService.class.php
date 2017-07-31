<?php
namespace Partner\Service;
use Partner\Model\CategoryModel;

class CategoryService extends BaseService{
	public function addCategory($data,$partner_id,$shop_ids){
		$model = new CategoryModel();
		$base_id = $model->addCategory($data);
		$shop_list = $model->getShopListByPartner($partner_id);
		foreach($shop_list as $key=>$shop){
			$data['shop_id'] = $shop['id'];
			$data['base_id'] = $base_id;
			$data['partner_id'] = $partner_id;
			if(in_array($shop['id'], $shop_ids)){
				$data['is_off'] = 0;
			}else{
				$data['is_off'] = 1;
			}
			$model->addShopCategory($data);
		}
		return $base_id;	
	}

	public function editCategory($data,$shop_ids){
		$model = new CategoryModel();
		$model->editCategory($data);
		$model->editGoodsByCategory($data['id'],$data['name']);

		$base_id = $data['id'];
		unset($data['id']);

		foreach($shop_ids as $key=>$shop_id){
			$data['is_off'] = 0;
			$is_set = $model->isSetShopCategory($base_id,$shop_id);
			if($is_set){
				$model->editShopCategory($data,$base_id,$shop_id);
				$model->editShopGoodsByCategory($base_id,$shop_id,$data['name']);
			}else{
				$data['shop_id'] = $shop_id;
				$data['base_id'] = $base_id;
				$model->addShopCategory($data);
			}
		}
		return true;
	}

	public function deleteCategory($id){
		$model = new CategoryModel();
		$category = $model->getCategoryById($id);
		$category_name = $category['name'];
		$partner_id = session('partner_id');
		$model->deleteShopGoods($category_name,$partner_id);
		$model->deleteGoods($category_name,$partner_id);

		$model->deleteShopCategory($category_name,$partner_id);
		$model->deleteCategory($id);

	}
}