<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-24
 * Time: 16:07
 */

namespace Home\Service;


class OrderAfterService
{
    private $funs;
    private function loadfun(){
        $this->funs = array(
            'count'
        );
    }


    public function run($data){
        $this->loadfun();
        $args[0] = $data;
        foreach ($this->funs as $fun){
            call_user_func_array(array($this, $fun), $args);
        }
    }

    private function count($data){

    }
}