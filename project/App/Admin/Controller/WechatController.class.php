<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-05-23
 * Time: 17:18
 */

namespace Partner\Controller;
use Base\Service\WechatHelper;
use Partner\Service\ShopService;
use Partner\Service\UserService;
use Partner\Service\WechatService;
use Think\Controller;

class WechatController extends BaseController
{
    /**
     * 渠道二维码列表
     */
    public function qrcode(){
        $p = I('p',1);
        $list = $this->service->getQrcodePage($this->partner_id,$p);
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 修改渠道二维码
     */
    public function qrcodeedit(){
        if(IS_POST){
            $data = I();
            $data['partner_id'] = $this->partner_id;
            $res = $this->service->qrcodesave($data);
            if($res !== false){
                echojson("操作成功","refresh");
            }else{
                echojson("操作失败","refresh","error");
            }
        }else{
            $id = I('id');
            $this->assign('id',$id);
            $info = $this->service->getById('wechat_qrcode',$id);
            $this->assign('info',$info);
            $this->display();
        }
    }

    /**
     * 删除渠道二维码
     */
    public function qrcodedel(){
        $id = I('id');
        $res = $this->service->qrcodeDel($id);
        if($res !== false){
            echojson("删除成功","refresh");
        }else{
            echojson("删除失败","refresh","error");
        }
    }

    /**
     * 下载二维码
     */
    public function qrcodedown()
    {
        $id = I('id');
        $info = $this->service->getById('wechat_qrcode',$id);
        $path = C('BASE_PATH').$info['url'];
        getFile($path);
    }

    /**
     * 用户留言列表
     */
    public function asklist(){
        $p = I('p',1);
        $list = $this->service->asklist($this->partner_id,$p);
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 回复用户
     */
    public function answer(){
        $id = I('id');
        $content = I('content');
        $this->service->answer($this->partner_id,$id,$content);
        echojson("回复成功");
    }

    /**
     * 用户回复详情
     */
    public function askdetail(){
        $id = I('id');
        $this->assign('id',$id);
        $list = $this->service->answerdetail($id);
        // var_dump($list);die;
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 菜单列表
     */
    public function menus(){
        $list = $this->service->getMenusAll($this->partner_id);
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 菜单编辑
     */
    public function menusedit(){
        if(IS_POST){
            $data = I();
            $data['partner_id'] = $this->partner_id;
            $res = $this->service->saveMenus($data);
            if($res !== false){
                echojson("修改成功","refresh");
            }else{
                echojson("修改失败","refresh","error");
            }
        }else{
            $id = I('id');
            $this->assign('id',$id);
            $pid = I('pid');
            $this->assign('pid',$pid);

            if(!empty($id)){
                $info = $this->service->getMenusById($id);
                $this->assign('info',$info);
            }

            $site_url = $this->service->getSiteUrl($this->partner_id);
            $this->assign('urls',$site_url);
            $list = $this->service->getMenusButtonList($this->partner_id);
            $this->assign('list',$list);
            $this->display();
        }
    }

    /**
     * 菜单更新
     */
    public function menusupdate(){
        $res = $this->service->createMenus($this->partner_id);
        if($res){
            echojson("更新成功","");
        }else{
            echojson("更新失败","","error");
        }
    }

    /**
     * 菜单删除
     */
    public function menusdel(){
        $id = I('id');
        $res = $this->service->menusDel($id);
        if($res !== false){
            echojson("操作成功","refresh");
        }else{
            echojson("操作失败","refresh","error");
        }
    }

    /**
     * 文章分组列表
     */
    public function articlegroup(){
        $p = I('p',1);
        $list = $this->service->getarticlegroupList($this->partner_id,$p);
        $this->assign('list',$list);
        $shopser = new ShopService();
        $shops = $shopser->getBaseList();
        $this->assign('shops',$shops);
        $this->display();
    }


    /**
     * 文章编辑
     */
    public function articleedit(){
        $id = I('id');
        $gid= I('gid');
        if(IS_POST){
            $data = I();
            $content = I('content');
            $data['content'] = htmlspecialchars_decode($content);
            if(!empty($_FILES['imgfile']['name'])) {
                $data['cover'] = upload('imgfile','article');
            }
            $data['create_time'] = time();
            $data['partner_id'] = $this->partner_id;
            $data['group_id'] = $gid;
            $gid = $this->service->saveArticle($data);
            $this->success("操作成功",U('Wechat/articlegroupinfo',array('id'=>$gid)));
        }else{
            $this->assign('gid',$gid);
            $info = $this->service->getById('wechat_article',$id);
            $this->assign('info',$info);
            $this->display();
        }
    }

    /**
     * 文章顺序改变
     */
    public function articlePos(){
        $id = I('id');
        $type = I('type');
        $this->service->articlePos($id,$type);
    }

    /**
     * 文章删除
     */
    public function articleDel(){
        $id = I('id');
        $this->service->articleDel($id);
        $this->success("删除成功",U('articlegroup'));
    }



    

    public function test()
    {
//        $content = '<img alt="" src="js/fckeditor/UserFiles/image/F201005201210502415831196.jpg" width="600" height="366"><br><br><br><br><img alt="" src="js/fckeditor/UserFiles/image/33_avatar_middle.jpg" width="120" height="120">';

        //提取图片路径的src的正则表达式
//        preg_match_all('/<img(.*)src="([^"]+)"[^>]+>/isU',$content,$matches);
//
//        echo BASE_PATH;exit;
//        $helper = WechatHelper::get($this->partner_id);
//        echo $helper->img2Wx("http://www.jb51.net/images/logo.gif");
//        var_dump($matches[2]);exit;



    }

    /**
     * 测试，移动用户组
     */
    public function moveUserGroup(){
        $uid = 10;
        $gid = 14;
        $openid = M('wechat_user')->where(array('id'=>$uid))->getField('openid');
        $gid = M('wechat_group')->where(array('id'=>$gid))->getField('group_id');
        $helper = WechatHelper::get($this->partner_id);
        $helper->joinGroup($gid,$openid);
        $res = $helper->weObj->getUserGroup($openid);
        var_dump($res);
    }

    /**
     * 分组详情页
     */
    public function articlegroupinfo()
    {
        $id = I('id');
        $data = $this->service->articlegroupinfo($id);
        $this->assign('gid',$id);
        $this->assign('data',$data);
        $this->display();
    }

    /**
     * 文章预览
     */
    public function articlePre()
    {
        $uid = I('uid');
        $gid = I('gid');
        $helper = WechatHelper::get($this->partner_id);
        $articles = $this->service->getGroupArticle($gid);
        $userSer = new UserService();
        $openid = $userSer->getOpenid($uid);
        $helper->preview($articles,$openid);

        \Think\Log::write("tong-test:".$helper->weObj->errMsg);
    }

    /**
     * 文章预览输入信息页
     */
    public function articlePreInput()
    {
        $id = I('id');
        $this->assign('id',$id);
        $this->display();
    }

    /**
     * 分组推送
     */
    public function articleSend(){
        $id = I('id');
        $sid = I('sid');
        $gid = $this->service->getShopGroupId($sid);
        if(empty($gid)){
            echojson("微信分组未建立，请配置",'','error');
        }
        $helper = WechatHelper::get($this->partner_id);
        $articles = $this->service->getGroupArticle($id);
        $articleId = $helper->sendArticle($gid,$articles);
        \Think\Log::write('articleSend id='.$articleId);
        echojson("推送成功");
    }


    public function auto(){
        $ser = new WechatService();
        $list = $ser->getAutoInfo($this->partner_id);
        $this->assign("list",$list);
        $this->display();
    }

    public function editauto()
    {
        $id = I('id');
        $ser = new WechatService();

        if(IS_POST){
            $data = I();
            $data['partner_id'] = $this->partner_id;
            if($data['type'] != 2){
                $data['key'] = '';
            }
            $res = $ser->saveAuto($data);
            if($res){
                echojson("操作成功","refresh");
            }else{
                echojson("已经存在，操作失败","refresh","error");
            }
        }else{
            $info = $ser->getAutoById($id);
            $this->assign('info',$info);
            $this->display();
        }
    }

    public function delauto()
    {
        $id = I('id');
        $ser = new WechatService();
        $ser->delauto($id);
        echojson("操作成功","refresh");
        
    }

    public function systemUrl()
    {
        $site_url = $this->service->getSiteUrl($this->partner_id);
        $this->assign('list1',$site_url);
        $shop_url = $this->service->getShopUrl($this->partner_id);
        $this->assign('list2',$shop_url);
        $cat_url = $this->service->getCatUrl($this->partner_id);
        $this->assign('list3',$cat_url);
        $this->display();
    }
    


}