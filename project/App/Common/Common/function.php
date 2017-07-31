<?php


/***********************begin 文件处理函数*******************************/
//删除文件夹
function rmdirr($dirname) {
    if (!file_exists($dirname)) {
        return false;
    }
    if (is_file($dirname) || is_link($dirname)) {
        return unlink($dirname);
    }
    $dir = dir($dirname);
    if ($dir) {
        while (false !== $entry = $dir->read()) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            //递归
            rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
        }
    }
}

function delfile($file){
    if (is_file($file)){
        return unlink($file);
    }
    return false;
}

//获取文件修改时间
function getfiletime($file, $DataDir) {
    $a = filemtime($DataDir . $file);
    $time = date("Y-m-d H:i:s", $a);
    return $time;
}

//获取文件的大小
function getfilesize($file, $DataDir) {
    $perms = stat($DataDir . $file);
    $size = $perms['size'];
    // 单位自动转换函数
    $kb = 1024;         // Kilobyte
    $mb = 1024 * $kb;   // Megabyte
    $gb = 1024 * $mb;   // Gigabyte
    $tb = 1024 * $gb;   // Terabyte

    if ($size < $kb) {
        return $size . " B";
    } else if ($size < $mb) {
        return round($size / $kb, 2) . " KB";
    } else if ($size < $gb) {
        return round($size / $mb, 2) . " MB";
    } else if ($size < $tb) {
        return round($size / $gb, 2) . " GB";
    } else {
        return round($size / $tb, 2) . " TB";
    }
}
/***********************end 文件处理函数*******************************/

//输出json
function echojson($msg='',$url='',$status="success",$exit=1){
    $ret = array('status'=>$status,'msg'=>$msg,'url'=>$url);
    echo json_encode($ret);
    if($exit) exit;
}

/****************************生成订单号***********************************/
function order_num($uid){
    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
    $orderSn = $yCode[intval(date('Y')) - 2011] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    return $orderSn.$uid;
}

/*********************************字符串**********************************/
//第一个是原串,第二个是 部份串
function startWith($str, $needle) {
    return strpos($str, $needle) === 0;

}

/***********************************微信生成和解析32位邀请码********************************/

/** 从微信服务器下载图片
 * @param $url
 * @param $path
 */
function downloadImgFromWechat($url,$path){
    $ch = curl_init($url);
    curl_setopt($ch,CURLOPT_HEADER,0);
    curl_setopt($ch,CURLOPT_NOBODY,0);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    $package = curl_exec($ch);
    $httpinfo = curl_getinfo($ch);
    curl_close($ch);
    $imgInfo = array_merge(array('body'=>$package),array('header'=>$httpinfo));
    $local = fopen($path,'w');
    if(false != $local){
        if(false != fwrite($local,$imgInfo['body'])){
            fclose($local);
        }
    }
}

/** 下载文件
 * @param $url
 * @param $dir
 * @return string
 */
function saveMedia($url,$dir,$openid){
    $basepath = $_SERVER['DOCUMENT_ROOT'].__ROOT__;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_NOBODY, 0);    //对body进行输出。
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $package = curl_exec($ch);
    $httpinfo = curl_getinfo($ch);
    curl_close($ch);
    $media = array_merge(array('mediaBody' => $package), $httpinfo);

    //求出文件格式
    preg_match('/\w\/(\w+)/i', $media["content_type"], $extmatches);
    $fileExt = $extmatches[1];
    $filename = $openid.time().rand(100,999).".{$fileExt}";
    $dirname = "/Public/$dir/";
    if(!file_exists($dirname)){
        mkdir($dirname,0777,true);
    }
    file_put_contents($basepath.$dirname.$filename,$media['mediaBody']);
    return $dirname.$filename;
}

//计算地图两点间距离
function getShortDistance($lngx,$latx,$lngy,$laty)
{
    $DEF_PI = 3.14159265359; // pi();
    $DEF_2PI = 6.28318530712; // 2 * $DEF_PI;
    $DEF_PI180 = 0.01745329252; // $DEF_PI/180.0;
    $DEF_R = 6378.137; // radius of earth

    // 角度换弧度
    $radLat1 = $lngx * $DEF_PI180;
    $radLat2 = $lngy * $DEF_PI180;

    $a = $radLat1 - $radLat2;
    $b = ( $latx * $DEF_PI180 ) - ( $laty * $DEF_PI180 );
    $s = 2 * sin(sqrt(pow(sin($a/2),2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));
    $s = $s * $DEF_R;
    $distance = round($s * 1000);
    return $distance;
}

function getDistance($lngx,$latx,$lngy,$laty){
    $DEF_PI = 3.14159265359; // pi();
    $lon1 = ($DEF_PI / 180) * $lngx;
    $lon2 = ($DEF_PI / 180) * $lngy;
    $lat1 = ($DEF_PI / 180) * $latx;
    $lat2 = ($DEF_PI / 180) * $laty;

    // 地球半径
    $R = 6371;
    // 两点间距离 km，如果想要米的话，结果*1000就可以了
    $d = acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($lon2 - $lon1)) * $R;
    return (int)($d * 1000);
}

function getWxCode($uid){
//    $len = strlen($uid);
    $res = 100000000+(int)$uid;
    return $res;
}

function expWxCode($code){
    $res = (int)$code - 100000000;
    return $res;
}

/*****************红包算钱********************/
/**根据订单金额，生成对应的红包金额
 * @param $money
 * @return int
 */
function getMoney($money){
    if($money<10){
        return 1;
    }elseif($money>=10 && $money<30){
        return 2;
    }elseif($money>=30){
        return 5;
    }
}

/**
 * 获取城市信息
 */

function getCity($lng,$lat){
    $url = 'http://apic.map.qq.com/rgeoc/?lnglat='.$lng.'%2c'.$lat.'&output=jsonp&pf=jsapi&ref=jsapi';
    $data = file_get_contents($url);
    $pos = strpos($data,'"name":"');
    $pos2 = strpos($data,'"dtype":"AD"');
    $len = $pos2-$pos;
    return substr($data,$pos+8,$len-11);
}

/** 获取价位区间sql
 * @param $i
 * @return bool|string
 */
function getPriceRange($i){
    $arr = array(0,5,10,20,30,50,100,1000);
    if($i>6) return false;
    $where = 'total_price>='.$arr[$i].' and total_price<'.$arr[$i+1];
    return $where;
}

/** 获取价位区间字符串
 * @return bool|string
 */
function getPriceRangeStr($i)
{
    $arr = array(0, 5, 10, 20, 30, 50, 100, 1000);

    return $arr[$i].'-'.$arr[$i+1].'元';
}

/** 获取价位区间数组
 * @return bool|string
 */
function getPriceRangeArr()
{
    $arr = array(0, 5, 10, 20, 30, 50, 100, 1000);
    return $arr;
}

//小数点后截取6位,高德地图调用必须要
function getLatlng($data){
    $tmp = explode('.',$data);
    return $tmp[0].'.'.substr($tmp[1],0,6);
}

function getShareUrl($url){
    return C('BASE_URL').'/'.$url;
}

function get_extension($file){
    return end(explode('.', $file));
}

//只兼容 A-Z 1232131
function getExcelIndex($row,$cel){
    $char = "";

    $str = base_convert($cel, 10, 26);
    for($i=0;$i<strlen($cel);$i++){
        $c = $str[$i];
        if($c==''){
            break;
        }else if($c>='a'){
            $c = 10+ord($c)-ord('a');
        }
        $char .= chr(ord('A')+$c-1);
    }

    return $char.$row;
}

//获取当前域名
function getDomainName(){
    return $_SERVER['SERVER_NAME'];
}

function getTodayTimespan(){
    $y = date("Y");
    $m = date("m");
    $d = date("d");
    return mktime(0,0,0,$m,$d,$y);
}

//下载图片函数
function grabImage($url,$filename=""){
    if ($url == "") return false;
    if(!strstr($url,"http://")){
        $url = C("BASE_URL").$url;
    }
    if($filename == "") {
        $ext=strrchr($url,"."); //获取扩展名
        $ext_arr = array(".gif",".png",".jpg",".bmp");
        //判断扩展名是否为图片
        if (!in_array($ext, $ext_arr)) $ext = ".jpg";
        //我就随便将图片文件名保存为时间戳了，你可自行修改
        $filename = time().$ext;
    }
    ob_start(); //打开浏览器的缓冲区
    readfile($url); //将图片读入缓冲区
    $img = ob_get_contents(); //获取缓冲区的内容复制给变量$img
    ob_end_clean(); //关闭并清空缓冲
    $fp = @fopen($filename,"a"); //将文件绑定到流
    fwrite($fp,$img); //写入文件
    fclose($fp); //关闭文件之争
    return $filename;
}

function getRandomFileName(){
    return time().mt_rand();
}

function getExt($url)
{
    $path=parse_url($url);
    $str=explode('.',$path['path']);
    return $str[1];
}

/** 获取合作商户的accesstoken
 * @param $pid
 * @return bool|string
 */
function getAccessToken($pid){
    $ser = new \Base\Service\AccesstokenService();
    return $ser->index($pid);
}

/** 获取合作商户的jsticket
 * @param $pid
 * @return bool|string
 */
function getJsTicket($pid){
    $ser = new \Base\Service\AccesstokenService();
    return $ser->index($pid,1);
}


//上传图片
function upload($file,$type,$name='',$exts=''){
    $upload = new \Think\Upload();// 实例化上传类
    $upload->maxSize   =     3145728 ;//设置附件上传大小
    $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg','pem');// 设置附件上传类型
    if(!empty($exts)){
        $upload->exts = $exts;
    }
    $upload->rootPath  =     './Public/'.$type.'/';
    $upload->saveName  =     time().'_'.mt_rand();
    if(!empty($name)){
        $upload->saveName = $name;
    }
    //上传文件
    $info   =   $upload->uploadOne($_FILES[$file]);
    if(!$info) {// 上传错误提示错误信息
        var_dump($upload->getError());die;
    }else{// 上传成功
        return '/Public/'.$type.'/'.str_replace('../','/',$info['savepath'].$info['savename']);
    }
}

/** 多图上传
 * @param $type
 * @return array|bool
 */
function uploadMany($type){
    $upload = new \Think\Upload();// 实例化上传类
    $upload->maxSize   =     3145728 ;// 设置附件上传大小
    $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
    $upload->rootPath  =     './Public/'.$type.'/';
    $upload->savePath  =      ''; // 设置附件上传（子）目录
    // 上传文件
    $info   =   $upload->upload();
    if(!$info) {// 上传错误提示错误信息
        var_dump($upload->getError());
    }else{// 上传成功 获取上传文件信息
        return $info;
    }
}



/** 实现curl 中 -d 数据传输
 * @param $url
 * @param $data
 * @return mixed
 */
function postJsonBody($url,$data){
    $data_string = json_encode($data);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
    );
    $sContent = curl_exec($ch);
    curl_close($ch);
    return $sContent;
}

//html直接下载图片
function getFile($filename){
//文件的类型
    header('Content-type: image/jpeg');
//下载显示的名字
    header('Content-Disposition: attachment; filename="erweima.jpg"');
    readfile("$filename");
    exit;
}

/** 发布一个新订单的消息
 * @param $id
 * @return mixed
 */
function publishOrder($sid,$oid){
    $url = C('YB_URL');
    $data['method'] = C('YB_METHOD');
    $data['appkey'] = C('YB_ID');
    $data['seckey'] = C('YB_SEC');
    $data['topic'] = C("YB_ORDER_TOPIC").'_'.$sid;
    \Think\Log::write("publishOrder = ".json_encode($data));
    $data['msg'] = $oid;
    return postJsonBody($url,$data);
}

function publishMsg($topic,$msg){
    $url = C('YB_URL');
    $data['method'] = C('YB_METHOD');
    $data['appkey'] = C('YB_ID');
    $data['seckey'] = C('YB_SEC');
    $data['topic'] = $topic;
    $data['msg'] = $msg;
    return postJsonBody($url,$data);
}


/** 往阿里文件服务器上传文件
 * @param $name
 * @param $path 文件路径可以是相对的./Public/.....
 * @param int $type 文件是否为临时文件 n天自动删除
 * @throws \OSS\Core\OssException
 * @return bool
 */
function ossUploadImg($name,$path,$type=0){
    if(!file_exists($path)){
        return false;
    }
    if($type == 1){
        $name = "tmp_".$name;
    }else if($type == 0){
        $name = "goods_".$name;
    }else if($type == 2){
        $name = "other_".$name;
    }
    $client = new \OSS\OssClient(\OSS\OssConfig::OSS_ACCESS_ID,\OSS\OssConfig::OSS_ACCESS_KEY,\OSS\OssConfig::OSS_ENDPOINT,\OSS\OssConfig::OSS_BUCKET);
    $res = $client->uploadFile(\Oss\OssConfig::OSS_BUCKET,$name,$path);
    return $name;
}

/** 删除阿里文件服务器上的文件
 * @param $name
 */
function ossDeleteImg($name,$type=0){
    if($type == 0){
        $name = "goods_".$name;
    }
    $client = new \OSS\OssClient(\OSS\OssConfig::OSS_ACCESS_ID,\OSS\OssConfig::OSS_ACCESS_KEY,\OSS\OssConfig::OSS_ENDPOINT,\OSS\OssConfig::OSS_BUCKET);
    $client->deleteObject(\Oss\OssConfig::OSS_BUCKET,$name);
}

function getQrcode($url,$path = '',$size = 4){
    vendor("phpqrcode.phpqrcode");
    // 纠错级别：L、M、Q、H
    $level = 'L';
    // 下面注释了把二维码图片保存到本地的代码,如果要保存图片,用$fileName替换第二个参数false
    if(empty($path)){
        $path = './Public/qrcode/pay/';
        // 生成的文件名
        $path = $path.time().'.png';
    }
    QRcode::png($url, $path, $level, $size);
    return $path;
}

/** 发送短信通知
 * @param $tel
 * @param $datas
 * @return bool
 */
function sendSms($tel,$datas){
    $accountSid= 'aaf98f894c9d994b014ca1f7f70a0339';
    //主帐号Token
    $accountToken= 'e15aef40e48c40de85982e27170baa29';
    //应用Id
    $appId='8a216da8567745c001568ca3eca10d83';
    //请求地址，格式如下，不需要写https://
    $serverIP='app.cloopen.com';
    //请求端口
    $serverPort='8883';
    //REST版本号
    $softVersion='2013-12-26';
    $tempId = "122148";
    $rest = new \Com\CcRestSdk($serverIP,$serverPort,$softVersion);
    $rest->setAccount($accountSid,$accountToken);
    $rest->setAppId($appId);
    // 发送模板短信
    $result = $rest->sendTemplateSMS($tel,$datas,$tempId);
    if($result == NULL ) {
        return false;
    }
    if($result->statusCode!=0) {
        //TODO 添加错误处理逻辑
        return false;
    }else{
        // 获取返回信息
        $smsmessage = $result->TemplateSMS;
        //TODO 添加成功处理逻辑
        return true;
    }
}

//删除商品缓存，goods_id为sm_shop_goods表的id
function clearGoodsCacheByShopGoodsId($goods_id){
    $category_id = M('shop_goods')->where(array('id'=>$goods_id))->getField('category_id');
    // var_dump($category_id);die;
    S('goodsList_'.$category_id,NULL);
}

function clearGoodsCacheByBaseGoodsId($basegoods_id){
    $base_category_id = M('base_goods')->where(array('id'=>$basegoods_id))->getField('category_id');
    $res = M('shop_category')->where(array('base_id'=>$base_category_id))->select();
    foreach($res as $key=>$value){
        S('goodsList_'.$value['id'],NULL);
    }
}

function clearCatCacheByShopCatId($category_id){
    $res  =M('shop_category')->where(array('id'=>$category_id))->find();
    S('category_'.$res['partner_id'].'_'.$res['shop_id'],NULL);
}

function clearCatCacheByBaseCatId($base_category_id){
    $res = M('shop_category')->where(array('base_id'=>$base_category_id))->select(); 
    foreach($res as $key=>$value){
        S('category_'.$value['partner_id'].'_'.$value['shop_id'],NULL);
    }
}

/*** 过滤/r/n
 * @param $str
 * @return mixed
 */
function filteNewLine($str){
    $str = str_replace("\r", "", $str);
    $str = str_replace("\n", "", $str);
    return $str;
}

function getWeekDateList(){
    $weekDay = array('周日','周一','周二','周三','周四','周五','周六');
    $today = strtotime('today');
    $arr = array();
    $day = $today;
    for($i=0;$i<7;$i++){
        $arr[$i]['bid_date'] = date('Y-m-d',$day);
        $arr[$i]['small_date'] = date('m-d',$day);
        $arr[$i]['week'] = $weekDay[date('w',$day)];
        $arr[$i]['number_week'] = date('w',$day) == 0 ? '7' : date('w',$day);
        $day = $day+86400;
    }
    return $arr;
}

function formatTime($time)
{
    if(strlen($time) == 4){
        return '0'.$time;
    }
    return $time;
}

//二维数组根据key排序
function bubble_sort($array,$key,$sort) {
    for($i = 0; $i < count($array) - 1; $i++) {    //$i为已经排过序的元素个数
        for($j = 0; $j < count($array) - 1 - $i; $j++) {    //$j为需要排序的元素个数,用总长减去$i
            if($sort == 'asc'){
                if((float)$array[$j][$key] > (float)$array[$j + 1][$key]) {    //按升序排序
                    $temp = $array[$j];
                    $array[$j] = $array[$j + 1];
                    $array[$j + 1] = $temp;
                }
            }else{
                if((float)$array[$j][$key] <= (float)$array[$j + 1][$key]) {    //按升序排序
                    $temp = $array[$j];
                    $array[$j] = $array[$j + 1];
                    $array[$j + 1] = $temp;
                }
            }

        }
    }
    return $array;
}




