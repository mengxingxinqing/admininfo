<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-11-29
 * Time: 15:59
 */

namespace Home\Service;


class PointService
{
    public function getById($id)
    {
        $info = M("pick_building")->find($id);
        return $info;
    }

    public function useRec()
    {
        //$sql = "select d.*,p.name from sm_user_recharge_declog d LEFT JOIN sm_pick_building p on p.id=d.point_id where d.type=3 and d.point_id=".$id;
    }
}