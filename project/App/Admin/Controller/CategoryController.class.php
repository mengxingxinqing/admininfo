<?php
namespace Partner\Controller;
use Partner\Service\CategoryService;
use Partner\Service\OrderService;
use Think\Controller;

class CategoryController extends BaseController {
    public function index(){
        $p = I('p');

        $service = new OrderService();
        $shop_list = $service->getShopList();
        $shop_id = I('shop_id');
    	$service = new CategoryService();
        if($shop_id){
            $data = $service->getCategoryListByShop($p,$shop_id);
        }else{
            $data = $service->getCategoryList($p);
        }
        $this->assign('data',$data);
        $this->assign('shop_list',$shop_list);
        $this->assign('shop_id',$shop_id);
    	$this->display();
    }

    public function addCategory(){
        $service = new OrderService();
        $shop_list = $service->getShopList();
        $this->assign('shop_list',$shop_list);
    	$this->display();
    }

    public function addCategoryAction(){
    	$data = I();
        $partner_id = session('partner_id');
        $shop_ids = $data['shop_id'];
        unset($data['shop_id']);
    	$service = new CategoryService();
    	$category_id = $service->addCategory($data,$partner_id,$shop_ids);
        clearCatCacheByBaseCatId($category_id);
    	echojson('添加成功',U('category/index'));
    }

    public function editCategory(){
        $id = I('id');
        $service = new OrderService();
        $shop_list = $service->getShopList();
        $service = new CategoryService();
        $data = $service->getCategoryById($id);
        $this->assign('data',$data);
        $this->assign('shop_list',$shop_list);
        $this->display();
    }

    public function editCategoryAction(){
        $data = I();
        $shop_ids = $data['shop_id'];
        unset($data['shop_id']);

        $service = new CategoryService();
        $service->editCategory($data,$shop_ids);

        clearCatCacheByBaseCatId($data['id']);
        echojson('修改成功',U('category/index'));
    }

    public function deleteCategory(){
        $id = $_GET['id'];
        $service = new CategoryService();
        $service->deleteCategory($id);
        clearCatCacheByBaseCatId($id);
        echojson('删除成功','refresh');
    }

    public function changeStatus(){
        $id = I('id');
        $now_status = I('now_status');
        $service = new CategoryService();
        $service->changeStatus($id,$now_status);
        clearCatCacheByShopCatId($id);
        echo json_encode(array('status'=>'ok'));
    }
}