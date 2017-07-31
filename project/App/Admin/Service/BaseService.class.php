<?php
namespace Partner\Service;


class BaseService
{
    public $model;
    public function __construct()
    {
        $serviceName = get_called_class();
        $modelName = str_replace("Service","Model",$serviceName);
        if (class_exists($modelName)) {
            $this->model = new $modelName();
        }
    }

    public function __call($name, $args){
        $serviceName = get_called_class();
        $modelName = str_replace("Service","Model",$serviceName);
        $model = new $modelName;
        return call_user_func_array(array($model, $name), $args);
    }
}