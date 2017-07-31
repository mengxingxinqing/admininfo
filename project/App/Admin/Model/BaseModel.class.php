<?php
namespace Partner\Model;
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-05-17
 * Time: 16:35
 */
class BaseModel
{

    public function __call($name, $args){
        
    }

    public function getPage($sql,$p,$count=''){
        $p = $p==''?1:$p;
        $pageSize = C('PAGE_SIZE');
        if(empty($count)){
            $count = preg_replace("/select .* from/i","select count(*) as num from",$sql);
        }
        $res = M()->query($count);
        $page = new \Org\Util\Page($res[0]['num'],$pageSize);
        $data['page'] = $page->show();
        $sql .= " limit ".($p-1)*$pageSize.','.$pageSize;
        $data['data'] = M()->query($sql);
        return $data;
    }

    public function getOne($sql){
        $res = M()->query($sql);
        return $res[0];
    }

    public function getOneKey($sql,$key){
        $res = M()->query($sql);
        return $res[0][$key];
    }

    /** 获取表的一行一个属性
     * @param $table
     * @param $id
     * @param $key
     * @return mixed
     */
    public function getLineKeyById($table, $id, $key)
    {
        $res = M($table)->where(array('id'=>$id))->getField($key);
        return $res;
    }

    public function getById($table,$id){
        $res = M($table)->find($id);
        return $res;
    }
}