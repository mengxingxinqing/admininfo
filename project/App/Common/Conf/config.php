<?php


return array(

    /***********************************************
     * 商品图片上传
     */
    'UPLOAD_MAX_SIZE' => 20000000, //最大上传大小
    'UPLOAD_EXTS' => array('jpg','jpeg','gif','png'),

    'LOAD_EXT_CONFIG' => 'db,site,const,wx,',
    'URL_ROUTER_ON'   => true,
    'TMPL_L_DELIM'          =>  '{',            // 模板引擎普通标签开始标记
    'TMPL_R_DELIM'          =>  '}',            // 模板引擎普通标签结束标记
    'LOG_RECORD' => true, // 开启日志记录
    'LOG_LEVEL'  =>'EMERG,ALERT,CRIT,ERR', // 只记录EMERG ALERT CRIT ERR 错误
    'DEFAULT_MODULE'=>'Admin',
    //开发时配置，上线需要注释
//    "APP_DEBUG"=>false,
//    "DB_FIELD_CACHE"=>false,
    "DB_FIELD_CACHE"=>true,

    //缓存配置
//    'DATA_CACHE_TYPE' => 'Memcache',
//    'MEMCACHE_HOST' => '127.0.0.1',
//    'MEMCACHE_PORT'	=>	'11211',

//    'HTML_CACHE_ON'     =>    true, // 开启静态缓存
//    'HTML_CACHE_TIME'   =>    600,   // 全局静态缓存有效期（秒）
//    'HTML_FILE_SUFFIX'  =>    '.shtml', // 设置静态缓存文件后缀
//    'HTML_CACHE_RULES'  =>     array(  // 定义静态缓存规则
//        'Article/{:action}_{id}',
//    )
);