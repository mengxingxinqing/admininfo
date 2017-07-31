<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-05-31
 * Time: 16:38
 */

namespace Partner\Model;


class MenusModel extends BaseModel
{
    public function getPartnerMenus($pid)
    {
        $key = "getPartnerMenus_".$pid;
        $parent = S($key);
        if(empty($parent)){
            $fields = 'id,name,domain,controller,action,param,icon,check_func';
            $sql = "select $fields,1 as is_base from sm_menus m where m.type=0 and m.parent_id=0 order by m.sort asc";
            $parent = M()->query($sql);

            $sql = "select $fields,0 as is_base from sm_partner_menus m where m.parent_id=0 and m.partner_id=$pid order by m.sort asc";
            $self = M()->query($sql);
            $parent = array_merge($parent,$self);
            for($i=0;$i<count($parent);$i++){
                $id = $parent[$i]['id'];
                if($parent[$i]['is_base'] == 1){
                    $sql = "select $fields from sm_menus m where m.type=0 and m.parent_id=".$id." order by m.sort asc";
                    $child = M()->query($sql);
                    $sql = "select $fields from sm_partner_menus m where m.partner_id=$pid and m.parent_id=".$id." and m.extend_base=1 order by m.sort asc";
                    $self = M()->query($sql);
                    $child = array_merge($child,$self);
                }else{
                    $sql = "select $fields from sm_partner_menus m where m.partner_id=$pid and m.parent_id=".$id." and m.extend_base=0 order by m.sort asc";
                    $child = M()->query($sql);
                }
                $parent[$i]['child'] = $child;
            }
            foreach ($parent as $k=>$v){
                foreach ($v['child'] as $ik=>$iv){
                    echo $iv['check_func'];
                    if(!empty($iv['check_func']) && !self::$iv['check_func']($pid)){
                        unset($parent[$k]['child'][$ik]);
                    }
                }
            }
            S($key,$parent,TIME_HALF_HOUR);
        }

        return $parent;
    }

    public function getPartnerAdminMenu($type)
    {
        $fields = 'id,name,domain,controller,action,param,icon';
        $sql = "select $fields,1 as is_base from sm_menus m where m.type=0 and m.parent_id=0 and m.id in (select menu_id from sm_partner_admin_menu where type_id=$type) order by m.sort asc";
        $parent = M()->query($sql);

        for($i=0;$i<count($parent);$i++){
            $id = $parent[$i]['id'];
            $sql = "select $fields from sm_menus m where m.type=0 and m.parent_id=".$id." and m.id in (select menu_id from sm_partner_admin_menu where type_id=$type) order by m.sort asc";
            $child = M()->query($sql);
            $parent[$i]['child'] = $child;
        }


        return $parent;
    }

    //菜单判定函数：判定店铺是否开启预定
    public function checkYuding($pid)
    {
        $info = M('shop_conf')->where(array('reserve_status'=>1,'partner_id'=>$pid))->find();
        if(empty($info)){
            return false;
        }
        return true;
    }




    public function getMbx($partner_id,$domain,$controller,$action){
        $info = M('partner_menus')->where(array('partner_id'=>$partner_id,'domain'=>$domain,'controller'=>$controller,'action'=>$action))->find();
        $data['parent'] = M('partner_menus')->find($info['parent_id']);
        $data['child'] = $info;
        return $data;
    }
}