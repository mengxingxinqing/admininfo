<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-12
 * Time: 15:34
 */

namespace Partner\Model;


class BonusModel extends BaseModel
{
    /** 获取邀请红包使用数量
     * @param $pid
     * @param $start
     * @param $end
     */
    public function getInviteNum($pid, $start, $end)
    {
        $sql = "select count(*) as num,ifnull(sum(money),0) as total_money from sm_bonus where used_time>$start and used_time<$end and partner_id=$pid and type=2";
        $res = self::getOne($sql);
        return $res;
    }
}