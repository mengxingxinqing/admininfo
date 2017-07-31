<?php
namespace Partner\Controller;
use Partner\Service\MenusService;
use Partner\Service\PartnerService;
use Think\Controller;

class BaseController extends Controller {

    public $partner_id;
    public $menus;
    public $service;
    function _initialize(){
        $pid = session('partner_id');
        if(empty($pid)){
            redirect(U("Login/index"));
        }
        $this->partner_id = $pid;
//        $this->menus = self::getMenus();
        $controllerName = get_called_class();
        $serviceName = str_replace("Controller","Service",$controllerName);
        if (class_exists($serviceName)) {
            $this->service = new $serviceName;
        }
        $type = session("partner_type");
        $this->assign('type',$type);
        $ser = new MenusService();
        if(empty($type)){
            $menus = $ser->getPartnerMenus($this->partner_id);
        }else{
            $menus = $ser->getPartnerAdminMenu($type);
        }

        $this->assign('menus',$menus);
        $this->assign('controller',CONTROLLER_NAME);
        $this->assign('action',ACTION_NAME);
        $this->assign('module',MODULE_NAME);
        $mbx = $ser->getMbx($this->partner_id,MODULE_NAME,CONTROLLER_NAME,ACTION_NAME);
        $this->assign('mbx',$mbx);
        $ser = new PartnerService();
        $info = $ser->getInfo($this->partner_id);
        $this->assign('partner',$info);
    }




}
