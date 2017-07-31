<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-03
 * Time: 12:16
 */

namespace Partner\Service;


class ActivityService extends BaseService
{
    public function modify1($data){
        $data['name'] = "首单送".$data['param1'].'元红包';
        return $this->model->addModify($data);
    }

    public function modify2($data)
    {
        $data['name'] = "首单送".$data['param1'];
        return $this->model->addModify($data);
    }

    public function modify3($data){
        $data['name'] = "满".$data['param1'].'元减'.$data['param2'];
        return $this->model->addModify($data);
    }

    public function modify4($data){
        $data['name'] = "满".$data['param1'].'元送'.$data['param2']."元红包";
        return $this->model->addModify($data);
    }

    public function modify5($data)
    {
        $data['name'] = "新用户送".$data['param1']."元红包";
        return $this->model->addModify($data);
    }

    public function modify6($data){
        $data['name'] = "满".$data['param1']."元送".$data['param2'];
        return $this->model->addModify($data);
    }

    public function modify7($data)
    {
        $data['name'] = "满减配送费";
        return $this->model->addModify($data);
    }

    public function getlist($partner_id){
        $res = $this->model->getlist($partner_id);
        foreach($res as $key=>$act){
            foreach($act['aclist'] as $k=>$child_act){
                if(in_array($act['id'], array(1,7))){
                    $res[$key]['aclist'][$k]['activity_day'] = $child_act['param2'];
                }elseif(in_array($act['id'], array(4))){
                    $res[$key]['aclist'][$k]['activity_day'] = $child_act['param3'];
                }else{
                    $res[$key]['aclist'][$k]['activity_day'] = '';
                }

                if(in_array($act['id'], array(7))){
                    if(!empty($child_act['param3'])){
                        $res[$key]['aclist'][$k]['name'] .= '---满'.$child_act['param3'].'元可用'; 
                    }else{
                        $res[$key]['aclist'][$k]['name'];
                    }
                    
                }
            }
        }
        return $res;
    }
}