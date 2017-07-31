<?php 
namespace  Base\Service;
use Com;
use Base\Model\WechatModel;

Class WechatHelper {
    private $partner_id;
    private $conf;
    private $model;
    public $weObj;
    private $topcolor='#FF0000';
    private $color= '#173177';
    private $jssdk;
    private function __construct($partner_id){
        $this->partner_id = $partner_id;
        $this->model = new WechatModel();
        $this->getConf();
        $this->getWeObj();
        $this->getJssdk();
    }

    public static function get($pid){
        static $helpers = array();
        if(empty($helpers[$pid])){
            $helpers[$pid] = new WechatHelper($pid);
        }
        return $helpers[$pid];
    }



	// 1.根据店铺获取微信配置信息
    public function getConf(){
        if(empty($this->conf)){
            $this->conf = $this->model->getConfig($this->partner_id);
        }
        return $this->conf;
    }
	// 2.根据店铺id获取微信操作对象
    private function getWeObj(){
        if(empty($this->weObj)){
            $this->weObj = new Com\TPWechat(self::getConf());
        }
        return $this->weObj;
    }
    
	// 3.获取店铺的长连接
    public function getUrl($url){
        $config = $this->conf;
        return $url="https://open.weixin.qq.com/connect/oauth2/authorize?appid={$config['appid']}&redirect_uri={$url}&response_type=code&scope=snsapi_base&state=1&connect_redirect=1#wechat_redirect";
    }
	// 4.获取微信支付的参数
    public function getJsPayParam($orderid,$openid,$order_num,$money,$flag = 0,$data=''){
        vendor('Wxpay.WxPayData');
        vendor('Wxpay.WxPayApi');
        vendor('Wxpay.WxPayConfig');
        vendor('Wxpay.WxPayJs');
        \Think\Log::write("param = orderid=".$orderid.' ordernum='.$order_num." money=".$money.' data='.json_encode($data));
//        $flag = 0;
        if(empty($data['appid'])) {
            $data['appid'] = $this->conf['appid'];
        }
        if(empty($data['mchid'])) {
            $data['mchid'] = $this->conf['mchid'];
        }
        if(empty($data['sslcert_path'])) {
            $data['sslcert_path'] = $this->conf['sslcert_path'];
        }
        if(empty($data['sslkey_path'])) {
            $data['sslkey_path'] = $this->conf['sslkey_path'];
        }

        if(empty($data['notify_url'])){
            $data['notify_url'] = C('BASE_URL').U("Home/Notify/wx",array('id'=>$orderid));
        }
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("商品付款");
        $input->SetAttach("商品支付");
        $input->SetOut_trade_no($order_num);
        $input->SetTotal_fee($money*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($this->conf['name']."商品支付");
        $input->SetNotify_url($data['notify_url']);
        if($flag == PAYTO_PARTNER){
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($openid);

        }else if($flag == PAYTO_SYSTEM){
            $input->SetTrade_type("NATIVE");
        }
        $input->SetAppid($data['appid']);
        $input->SetMch_id($data['mchid']);
        $input->SetProduct_id($order_num);
        \Think\Log::write(" input data = ".json_encode($input));
        try{
            $order = \WxPayApi::unifiedOrder($input,$data['sslcert_path'],$data['sslkey_path']);
            if($input->GetTrade_type() == "NATIVE"){
                $code_url = getQrcode($order['code_url']);
                $code_url = substr($code_url, 1-strlen($code_url));
                $res['code_url'] = $code_url;
                $res['type'] = "code";
                return json_encode($res);
            }
        }catch (\WxPayException $e){
            \Think\Log::write('wxpay-unifiedOrder '.$e->errorMessage());
        }
        $tools = new \JsApiPay();
        try {
            $jsApiParameters = $tools->GetJsApiParameters($order);
        }catch (\WxPayException $e){
            \Think\Log::write('wxpay-GetJsApiParameters '.$e->errorMessage().' --- '.json_encode($order));
            \Think\Log::write('wxpay-GetJsApiParameters '.' openid:'.$openid.' order_num:'.$order_num.' money:'.$money);
        }
        return $jsApiParameters;
    }

    /** 微信退款
     * @param  $order_id id
     * @param $order_num 订单号
     * @param $total_fee 总金额
     * @param string $payto 1 系统退款 0 商户退款
     * @return xml
     * @throws \WxPayException
     */
    public function refund($order_id,$order_num,$total_fee,$payto=''){
        ini_set('date.timezone','Asia/Shanghai');
        header("Content-type: text/html; charset=utf-8");
        vendor('Wxpay.WxPayData');
        vendor('Wxpay.WxPayApi');
        vendor('Wxpay.WxPayConfig');
        vendor('Wxpay.WxPayJs');
        $total_fee = $total_fee*100;
        if (empty($refund)) {
            $refund_fee = $total_fee;
        }else{
            $refund_fee = $refund*100;
        }

        $input = new \WxPayRefund();
        if($payto != 1){
            $data['mchid'] = $this->conf['mchid'];
            $data['appid'] = $this->conf['appid'];
            $data['sslcert_path'] = $this->conf['sslcert_path'];
            $data['sslkey_path'] = $this->conf['sslkey_path'];
        }else{
            $data = C('DEFAULT_PAY');
        }
        $input->SetOut_refund_no($order_num);
        $input->SetOut_trade_no($order_num);


        $input->SetTotal_fee($total_fee);
        $input->SetRefund_fee($refund_fee);

        //SetProduct_id
        $input->SetOp_user_id($data['mchid']);
        $input->SetAppid($data['appid']);//公众账号ID
        $input->SetMch_id($data['mchid']);//商户号
        $res = \WxPayApi::refund($input,C('BASE_PATH').$data['sslcert_path'],C('BASE_PATH').$data['sslkey_path']);
        return $res;
    }


	// 5.发送消息模板
    public function sendTpl($tmp_id,$openid,$url,$data){
        if(empty($tmp_id)){
            return;
        }
        $data['touser'] = $openid;
        $data['template_id'] = $tmp_id;
        $data['topcolor'] = $this->topcolor;
        if(!empty($url)){
            $data['url'] = $url;
        }
        foreach($data as $k =>$v){
            $data['data'][$k]['value'] = $v;
            $data['data'][$k]['color'] = $this->color;
        }
        return $this->weObj->sendTemplateMessage($data);
    }
	// 6.用户分组
    public function joinGroup($group_id,$openid){
        $this->weObj->updateGroupMembers($group_id,$openid);
    }

    public function createGroup($id,$name){
        $group=$this->weObj->createGroup($name.'_'.$id);
        return $group['group'];
    }


	// 7.获取jssdk对象
    public  function  getJssdk(){
        if(empty($this->jssdk)){
            $this->jssdk = new \Org\Util\JSSDK($this->conf['appid'], $this->conf['appsecret'],getJsTicket($this->partner_id));
        }
        return $this->jssdk;
    }
	//TODO 8.获取页面jssdk页面分享参数


    //上传文章
    private function uploadArticle($list){
        $articles = array();
        foreach($list as $item){
            $cover = C('BASE_PATH').$item['cover'];
            $cover = str_replace('//','/',$cover);
            $cover = self::uploadImgMediaId($cover);
            $content = htmlspecialchars_decode($item['content']);
            $content = self::processContent($content);
            $tmp = array(
                "thumb_media_id"=>$cover,
                "author"=>$item['author'],
                "title"=>$item['title'],
                "content"=>$content,
                "digest"=>$item['abstract'],
                "show_cover_pic"=>"0"
            );
            $articles[] = $tmp;
        }

        //首先将文章上传,获得mediaid
        $res = $this->weObj->uploadArticles(array('articles'=>$articles));
        if(!empty($res['media_id'])){
            return $res['media_id'];
        }
    }

    //上传图片
    public function uploadImgMediaId($path){
        $path = '@'.$path;
        $path = str_replace('//','/',$path);
        $data = array(
            'media'=>$path
        );
        $res = $this->weObj->uploadMedia($data,Com\TPWechat::MSGTYPE_IMAGE);
        return $res['media_id'];
    }

    public function uploadImgUrl($imgPath){
        $path = '@'.$imgPath;
        $path = str_replace('\\','/',$path);
        $data = array(
            'media'=>$path
        );
        return $this->weObj->uploadImg($data);
    }


    //文章推送
    public function sendArticle($group_id,$list){
        $media_id = $this->uploadArticle($list);
        $data = array(
            "filter"=>array(
                "is_to_all"=>false,
                "group_id"=>$group_id
            ),
            "mpnews"=>array(
                "media_id"=>$media_id
            ),
            "msgtype"=>"mpnews"
        );
        $res = $this->weObj->sendGroupMassMessage($data);
        if(!empty($res['msg_id'])){
            return true;
        }else{
            return false;
        }
    }
    
    public function getArticleList(){
        $list = $this->weObj->getForeverList("news",0,20);
    }
    //推送预览
    public function preview($list,$openid){
        $media_id = $this->uploadArticle($list);
        $preData = array(
            "touser"=>$openid,
            "mpnews"=>array(
                "media_id"=>$media_id
            ),
            "msgtype"=>"mpnews"
        );
        $res = $this->weObj->previewMassMessage($preData);
        if($res){
            return true;
        }else{
            return false;
        }
    }

    /** 正常的文章转换成微信文章
     * @param $content
     * @return mixed
     */
    private function processContent($content){
        preg_match_all('/< *img[^>]*src *= *["\']?([^"\']*)/i',$content,$matches);
        $imgs = $matches[1];
        for ($i=0;$i<count($imgs);$i++){
            $tmp = self::img2Wx($imgs[$i]);
            $content = str_replace($imgs[$i],$tmp,$content);
        }
        return $content;
    }

    //更新菜单
    public function setMenus($menus){
        $newmenu =  array("button"=>$menus);
        $result = $this->weObj->createMenu($newmenu);
        if($result){
            return true;
        }else{
            return false;
        }
    }

    //获取个人微信二维码
    public function getMyQrcode($uid){
        $weObj = $this->weObj;
        $fid = $uid;
        $ticket = $weObj->getQRCode($fid,0);
        $img = $weObj->getQRUrl($ticket['ticket']);
        $basepath = C('BASE_PATH');
        $file_name = time().$uid.'.jpg';
        $path = $basepath.'Public/qrcode/'.$file_name;
        downloadImgFromWechat($img,$path);
        $showpath = '/Public/qrcode/'.$file_name;
        return $showpath;
    }
    //获取企业渠道二维码，永久
    public function getQyQrcode($id){
        $qrcodenum = (int)$id+500000000;
        $ticket = $this->weObj->getQRCode($qrcodenum,2);
        $img = $this->weObj->getQRUrl($ticket['ticket']);
        $basepath = C('BASE_PATH');
        $file_name = time().'.jpg';
        $path = $basepath.'/Public/qrcode/'.$file_name;
        downloadImgFromWechat($img,$path);
        $res['qrcode'] = $path;
        $res['url'] = '/Public/qrcode/'.$file_name;
        $res['ticket'] = $ticket;
        return $res;
    }



    public function queryRefund($out_trade_no){
        ini_set('date.timezone','Asia/Shanghai');
        header("Content-type: text/html; charset=utf-8");
        vendor('Wxpay.WxPayData');
        vendor('Wxpay.WxPayApi');
        vendor('Wxpay.WxPayConfig');
        vendor('Wxpay.WxPayJs');
        if(isset($out_trade_no) && $out_trade_no != ""){
            $input = new \WxPayRefundQuery();
            $input->SetOut_trade_no($out_trade_no);
            exit();
        }
    }

	//TODO 11.

    //下载一个临时图片，上传到微信 保存返回地址
    private function img2Wx($url,$key='article')
    {
        if(strstr($url,'http://')){
            $path = C('BASE_PATH').'Public/'.$key.'/';
            $name = getRandomFileName().'.'.getExt($url);
            grabImage($url,$path.$name);
            $path = $path.$name;
        }else{
            $path = C('BASE_PATH').$url;
        }
        return self::uploadImgUrl($path);
//        return $path.$name;
    }


}
?>