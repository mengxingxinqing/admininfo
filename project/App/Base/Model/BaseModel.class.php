<?php
namespace Base\Model;


class BaseModel
{
    public $table = "";
    public $cacheLong = 86400;
    public $cache6000 = 6000;
    public $cache300 = 300;
    public $cache150 = 150;
    public $cache100 = 100;
    public $cache60 = 60;
    public $cache30 = 30;

    /** 分页查询
     * @param $p
     * @param string $where
     * @param string $order
     * @return mixed
     */
    public function page($p,$where="",$order=""){
        $limit = self::getPageLimit($p);
        if(empty($order)){
            $order = 'id desc';
        }
        $res = M($this->table)->where($where)->order($order)->limit($limit)->select();
        return $res;
    }

    /** 根据sql查询，单行自动返回一个对象
     * @param $where
     * @param $order
     * @return mixed
     */
    public function getByWhere($where,$order){
        $sql = "select * from ".C('DB_PREFIX').$this->table." ".$where.' '.$order;
        $res = M()->query($sql);
        if(count($res)==1){
            return $res[0];
        }
        return $res;
    }

    public function delete($id){
       return  M($this->table)->delete($id);
    }

    public function add($data){
       return  M($this->table)->add($data);
    }

    public function getById($id){
        return  M($this->table)->find($id);
    }

    public function save($where,$data){
       return  M($this->table)->where($where)->save($data);
    }

    /** 不分页查询
     * @param $where
     * @param string $order
     * @return mixed
     */
    public function search($where,$order=""){
        if(empty($order)){
            $order = 'id desc';
        }
        $res = M($this->table)->where($where)->order($order)->select();
        return $res;
    }

    /** 查询总数
     * @param $where
     * @return mixed
     */
    public function count($where=''){
        return  M($this->table)->where($where)->count();
    }

    /** 根据sql查询单个字段
     * @param $sql
     * @param $key
     * @return mixed
     */
    public function queryField($sql,$key){
        $res = M()->query($sql);
        return $res[0][$key];
    }

    /** 获取分页sql
     * @param $p
     * @return string
     */
    public function getPageLimit($p,$hasLimit = 0){
        if($hasLimit == 1){
            $pre = ' limit';
        }
        return $pre.' '.((int)$p-1)*C('PAGE_SIZE').','.C('PAGE_SIZE');
    }


    public  function  update($id,$array){
        return  $rs= M($this->table)->where(array('id'=>$id))->save($array);
    }

    public function checkTime($start_time,$end_time){
        if(empty($start_time)){
            return 1;
        }
        $time = date('H:i');
//        echo 'now:'.$time.'  start:'.$start_time.' end_time:'.$end_time.' if > '.($time>$start_time).'   if < '.(($time<$end_time));
        if($end_time>$start_time){//自然日
            if($time>=$start_time && $time<=$end_time){
                return 1;
            }
        }else{ //非自然日
            if(($time<="23:59" && $time>=$start_time) || $time<=$end_time){
                return 1;
            }
        }
        return 0;
    }
}