<?php
namespace Partner\Model;
use Base\Service\WechatHelper;

/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-05-17
 * Time: 16:32
 */
class CategoryModel extends BaseModel
{
    public function addCategory($data){
        $data['partner_id'] = session('partner_id');
        $data['create_time'] = time();
        return M('base_category')->add($data);
    }

    public function addShopCategory($data){
        return M('shop_category')->add($data);
    }

    public function getCategoryList($p){
    	$sql = "SELECT * FROM sm_base_category where partner_id=".session('partner_id')." AND is_del=0 ORDER BY sort";
    	return $this->getPage($sql,$p);
    }

    public function getCategoryListByShop($p,$shop_id){
        $sql = "SELECT * FROM sm_shop_category where partner_id is not null and shop_id=".$shop_id." AND is_del=0 ORDER BY sort";
        $res = $this->getPage($sql,$p);
        return $res;
    }

    public function getCategoryById($id){
    	return M('base_category')->where(array('id'=>$id))->find();
    }

    public function editCategory($data){
    	return M('base_category')->where(array('id'=>$data['id']))->save($data);
    }

    public function isSetShopCategory($base_id,$shop_id){
        return M('shop_category')->where(array('base_id'=>$base_id,'shop_id'=>$shop_id))->count();
    }

    public function editShopCategory($data,$base_id,$shop_id){
        return M('shop_category')->where(array('base_id'=>$base_id,'shop_id'=>$shop_id))->save($data);
    }

    public function deleteShopGoods($category_name,$partner_id){
        M('shop_goods')->where(array('category_name'=>$category_name,'partner_id'=>$partner_id))->save(array('is_del'=>1));
    }
    
    public function deleteGoods($category_name,$partner_id){
        M('base_goods')->where(array('category_name'=>$category_name,'partner_id'=>$partner_id))->save(array('is_del'=>1));
    }

    public function deleteShopCategory($category_name,$partner_id){
        M('shop_category')->where(array('name'=>$category_name,'partner_id'=>$partner_id))->save(array('is_del'=>1));
    }

    public function deleteCategory($id){
        M('base_category')->where(array('id'=>$id))->save(array('is_del'=>1));
    }

    public function changeStatus($id,$now_status){
        $new_status = $now_status == 0 ? 1:0;
        M('shop_category')->where(array('id'=>$id))->save(array('is_off'=>$new_status));
    }

    public function getShopListByPartner($partner_id){
        $res = M('shop')->where(array('partner_id'=>$partner_id))->select();
        return $res;
    }

    public function getBaseCatList($partner_id)
    {
        $key = 'getBaseCatList_'.$partner_id;
        $list = S($key);
        if(empty($list)){
            $sql = "SELECT * FROM sm_base_category where partner_id=".$partner_id." AND is_del=0 ORDER BY sort";
            $list = M()->query($sql);
            S($key,$list,TIME_TEN_MINUTE);
        }
        return $list;
    }

    public function getCatUrls($partner_id)
    {
        $list = self::getBaseCatList($partner_id);

        $helper = WechatHelper::get($partner_id);
        foreach ($list as $k=>$v){
            $tmp['name'] = $v['name'];
            $tmp['url'] = $helper->getUrl(C('BASE_URL').U('Home/Index/index',array('cat'=>$v['id'])));
            $res[] = $tmp;
        }
        $wechat = new WechatModel();
        $site = $wechat->getSiteUrl($partner_id);
        foreach ($site as $k=>$v){
            $tmp['name'] = $v['name'];
            $tmp['url'] = $v['url'];
            $res[] = $tmp;
        }
        return $res;
    }

    public function editGoodsByCategory($category_id,$category_name){
        M('base_goods')->where(array('category_id'=>$category_id))->save(array('category_name'=>$category_name));
    }

    public function editShopGoodsByCategory($basecategory_id,$shop_id,$category_name){
        $id = M('shop_category')->where(array('base_id'=>$basecategory_id,'shop_id'=>$shop_id))->getField('id');
        M('shop_goods')->where(array('category_id'=>$id))->save(array('category_name'=>$category_name));
    }
}