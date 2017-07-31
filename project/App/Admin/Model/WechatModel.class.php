<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-05-25
 * Time: 15:50
 */

namespace Partner\Model;


use Base\Service\WechatHelper;

class WechatModel extends BaseModel
{
    public function getMenusListByParent($partner_id,$parent_id){
        return M('wechat_menus')->where(array('partner_id'=>$partner_id,'parent_id'=>$parent_id))->order("sort asc")->select();
    }

    public function getMenusButtonList($partner_id){
        return M('wechat_menus')->where(array('partner_id'=>$partner_id,'type'=>'click'))->order("sort asc")->select();
    }

    public function getMenusById($id){
        return M('wechat_menus')->find($id);
    }

    public function getMenusAll($partner_id){
        $list = self::getMenusListByParent($partner_id,0);
        for ($i=0;$i<count($list);$i++){
            $list[$i]['child'] = self::getMenusListByParent($partner_id,$list[$i]['id']);
        }
        return $list;
    }

    public function getParentMenusNum($partner_id){
        return M('wechat_menus')->where(array('partner_id'=>$partner_id,'parent_id'=>0))->count();
    }

    public function getChildMenusNum($parent_id){
        return M('wechat_menus')->where(array('parent_id'=>$parent_id))->count();
    }



    public function saveMenus($data){
        if($data['id']){
            return M('wechat_menus')->where(array('id'=>$data['id']))->save($data);
        }else{
            return M('wechat_menus')->add($data);
        }

    }

    public function menusDel($id){
        return M('wechat_menus')->where(array('id'=>$id))->delete();
    }


    public function createMenus($partner_id){
        $menu_mod = M('wechat_menus');
        $parents = $menu_mod->where(array('parent_id'=>0,'partner_id'=>$partner_id))->order('sort asc')->limit(3)->select();
        $ms = array();
        foreach($parents as $item){
            $data = self::createbutton($item);
            $child = $menu_mod->where(array('parent_id'=>$item['id']))->order('sort asc')->limit(5)->select();
            if(!empty($child)){
                unset($data['url']);
                unset($data['type']);
                unset($data['key']);
                $data['sub_button'] = array();
                foreach($child as $val){
                    $data['sub_button'][] = self::createbutton($val);
                }
            }
            $ms[] = $data;
        }
        $baseser = WechatHelper::get($partner_id);
        $res = $baseser->setMenus($ms);
        \Think\Log::write(' json = '.json_encode($res));
        return $res;
    }

    //添加按钮，构造菜单数组
    private function createbutton($item){
        if($item['type'] == 'click'){
            $data = array('type'=>'click','name'=>$item['name'],'key'=>$item['key']);
        }else if($item['type'] == 'view'){
            $item['url'] = str_replace('&amp;','&',$item['url']);
            $data = array('type'=>'view','name'=>$item['name'],'url'=>$item['url']);
        }
        return $data;
    }

    public function getQrcodePage($partner_id,$p){
        $sql = "select * from sm_wechat_qrcode where partner_id=$partner_id order by id desc";
        return self::getPage($sql,$p);
    }

    public function qrcodeDel($id){
        return M('wechat_qrcode')->where(array('id'=>$id))->delete();
    }
    
    public function qrcodesave($data){
        if(empty($data['id'])){
            $data['create_time'] = time();
            $id = M('wechat_qrcode')->add($data);
            $wechat = WechatHelper::get(session('partner_id'));
            $res = $wechat->getQyQrcode($id);
            $res = M('wechat_qrcode')->where(array('id'=>$id))->save($res);
        }else{
            $res = M('wechat_qrcode')->where(array('id'=>$data['id']))->save(array('name'=>$data['name']));
        }
        return $res;
    }

    //获取文章分组列表
    public function getarticlegroupList($partner_id,$p){
        $sql = "select * from sm_wechat_article_group where partner_id=$partner_id";
        $list = self::getPage($sql,$p);
        if(empty($list['data'])) return $list;
        for($i=0;$i<count($list['data']);$i++){
            $child = M('wechat_article')->where(array('group_id'=>$list['data'][$i]['id']))->field('id,title,cover')->order('sort asc')->select();
            for($j=0;$j<count($child);$j++){
                $child[$j]['url'] = C("BASE_URL").U('Home/Article/index',array('id'=>$child[$j]['id']));
            }
            $list['data'][$i]['child'] = $child;
            $list['data'][$i]['cover'] = $child[0]['cover'];
        }
        return $list;
    }

    public function articlegroupinfo($id)
    {
        $list = M('wechat_article')->where(array('group_id'=>$id))->field('id,title,cover')->order('sort asc')->select();
        $data['head'] = $list[0];
        unset($list[0]);
        $data['list'] = $list;
        return $data;
    }

    public function asklist($partner_id,$p){
        $sql = "select a.*,u.nickname,u.headimgurl,s.name from sm_wechat_ask a left join sm_wechat_user u on u.id=a.uid left join sm_shop s on u.shop_id=s.id where a.partner_id=$partner_id and a.is_answer=0 order by a.id desc";
        $list = self::getPage($sql,$p);
        return $list;
    }

    public function answer($partner_id,$id,$content){
        $uid = M('wechat_ask')->where(array('id'=>$id))->getField('uid');
        $helper = WechatHelper::get($partner_id);
        $userModel = new UserModel();
        $openid = $userModel->getOpenid($uid);
        $res['partner_id'] = $partner_id;
        $res['is_answer'] = 1;
        $res['parent_id'] = $id;
        $res['uid'] = $uid;
        $res['content'] = $content;
        $res['type'] = \Com\TPWechat::MSGTYPE_TEXT;
        $res['create_time'] = time();
        M('wechat_ask')->add($res);
        M('wechat_ask')->where(array('id'=>$id))->save(array('status'=>1));
        $data = array(
            'touser'=>$openid,
            'msgtype'=>'text',
            'text'=>array(
                'content'=>$content
            ),
        );

        $helper->weObj->sendCustomMessage($data);
    }

    public function answerdetail($id){
        $uid = M('wechat_ask')->where(array('id'=>$id))->getField('uid');
        $sql = "select * from (select a.*,u.nickname,u.headimgurl,s.name from sm_wechat_ask a left join sm_wechat_user u on u.id=a.uid left join sm_shop s on u.shop_id=s.id where a.uid=$uid order by a.id desc limit 20) tb order by id asc";
        $list = M()->query($sql);
        return $list;
    }

    public function saveArticle($data){
        if(empty($data['id'])) { //执行新增
            if(empty($data['group_id'])){
                $gdata['name'] = $data['title'];
                $gdata['partner_id'] = $data['partner_id'];
                $gdata['create_time'] = time();
                $data['group_id'] = M('wechat_article_group')->add($gdata);
                $data['sort'] = 1;
            }else{
                $data['sort'] = 1 + M('wechat_article')->where(array('group_id'=>$data['group_id']))->max('sort');
            }

            $id = M('wechat_article')->add($data);
//            $name = "cover_".$id.'.jpg';
//            $cover = C('OSS_PATH').ossUploadImg($name,C('BASE_PATH').$data['cover'],2);
//            M('wechat_article')->where(array('id'=>$id))->save(array('cover'=>$cover));
            return $data['group_id'];
        }else{
//            $name = "cover_".$data['id'].'.jpg';
//            $data['cover'] = C('OSS_PATH').ossUploadImg($name,C('BASE_PATH').$data['cover'],2);
            M('wechat_article')->where(array('id'=>$data['id']))->save($data);
            return $data['group_id'];
        }
    }

    public function articlePos($id, $type)
    {

        $info = M('wechat_article')->where(array('id'=>$id))->find();
        $count = M('wechat_article')->where(array('group_id'=>$info['group_id']))->count();
        if($count == 1) return;
        if($type == 'up'){
            $sql = "select id,sort from sm_wechat_article where group_id=".$info['group_id'].' and sort<'.$info['sort'].' order by sort asc limit 1';
            $change = self::getOne($sql);
        }else{
            $sql = "select id,sort from sm_wechat_article where group_id=".$info['group_id'].' and sort>'.$info['sort'].' order by sort asc limit 1';
            $change = self::getOne($sql);
        }
        if(empty($change)) return;
        M('wechat_article')->where(array('id'=>$id))->save(array('sort'=>$change['sort']));
        M('wechat_article')->where(array('id'=>$change['id']))->save(array('sort'=>$info['sort']));
    }

    public function articleDel($id){
        $gid = M('wechat_article')->where(array('id'=>$id))->getField('group_id');
        $count = M('wechat_article')->where(array('group_id'=>$gid))->count();
        if($count == 1){
            M('wechat_article_group')->delete($gid);
        }
        M('wechat_article')->delete($id);
    }

    /** 根据店铺 创建微信分组
     * @param $data
     */
    public function createShopGroup($data){
        M('wechat_group')->add($data);
    }

    public function getShopGroupId($sid){
        $gid = M('wechat_group')->where(array('shop_id'=>$sid))->getField('group_id');
        return $gid;
    }

    public function getGroupArticle($gid){
        $list = M('wechat_article')->where(array('group_id'=>$gid))->select();
        return $list;
    }

    /** 获取站内链接地址
     * @param $pid
     * @return mixed
     */
    public function getSiteUrl($pid)
    {
//        $list = M("site_url")->order('sort asc')->select();
        $key = "getSiteUrl_".$pid;
        $list = S($key);
        if(empty($list)){
            $sql = "select * from sm_site_url where partner_id=0 or partner_id=".$pid;
            $list = M()->query($sql);
            $helper = WechatHelper::get($pid);
            for ($i=0;$i<count($list);$i++){
                $url = $list[$i]['url'];
                $url = str_replace('{pid}',$pid,$url);
                $url = C("BASE_URL").'/'.$url;
                $url = $helper->getUrl($url);
                $list[$i]['url'] = $url;
            }
            S($key,$list,TIME_TEN_MINUTE);
        }
        return $list;
    }

    public function getShopUrl($pid)
    {
        $key = "getShopUrl_".$pid;
        $list = S($key);
        if(empty($list)){
            $sql = "select * from sm_shop where is_del=0 and partner_id=".$pid;
            $list = M()->query($sql);
            $helper = WechatHelper::get($pid);
            for ($i=0;$i<count($list);$i++){
                $url = U("Home/Index/index",array('cl'=>1,'sid'=>$list[$i]['id']));
                $url = C("BASE_URL").$url;
                $url = $helper->getUrl($url);
                $list[$i]['url'] = $url;
            }
            S($key,$list,TIME_TEN_MINUTE);
        }
        return $list;
    }

    public function getCatUrl($pid)
    {
        $key = "getCatUrl_".$pid;
        $list = S($key);
        if(empty($list)){
            $sql = "select * from sm_base_category where is_del=0 and partner_id=".$pid;
            $list = M()->query($sql);
            $helper = WechatHelper::get($pid);
            for ($i=0;$i<count($list);$i++){
                $url = U("Home/Index/index",array('basecat'=>$list[$i]['id']));
                $url = C("BASE_URL").$url;
                $url = $helper->getUrl($url);
                $list[$i]['url'] = $url;
            }
            S($key,$list,TIME_TEN_MINUTE);
        }
        return $list;
    }


    /**获得合作商户的自动回复内容
     * @param $pid
     * @return mixed
     */
    public function getAutoInfo($pid)
    {
        $info = M('wechat_auto')->where(array('partner_id'=>$pid))->select();
        return $info;
    }

    /** 根据id获取自动回复信息
     * @param $id
     * @return mixed
     */
    public function getAutoById($id)
    {
        $info = M('wechat_auto')->find($id);
        return $info;
    }

    /** 自动回复
     * @param $data
     * @return bool
     */
    public function saveAuto($data)
    {
        if(empty($data['id'])){
            $num = M('wechat_auto')->where(array('partner_id'=>$data['partner_id'],'type'=>$data['type']))->count();
            if($num>0 && $data['type'] != 2){
                return false;
            }
        }
        if(!empty($data['id'])){
            M('wechat_auto')->where(array('id'=>$data['id']))->save($data);
        }else{
            M('wechat_auto')->add($data);
        }
        return true;
    }

    public function delauto($id)
    {
        M('wechat_auto')->delete($id);
    }

}
