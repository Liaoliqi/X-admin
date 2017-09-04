<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

function getPages($total,$curr){
    $limit=\app\admin\Dict::LIMIT;
    return [
        'total'=>$total,    //总条数
        'limit'=>$limit,    //每页显示的条数
        'pages'=>ceil($total/$limit),//分页数
        'curr'=>$curr           //当前页
    ];
}

/**
 * 用于权限换行
 * @param $num
 * @return int
 */
function myMod($num){
    return $num%3;
}