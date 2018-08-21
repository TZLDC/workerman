<?php
namespace app\api\controller;
use think\Controller;
use think\Db;

class Chat extends Controller
{
    public function get_name()
    {
    	if(request()->isAjax()){
    		$uid = input("toid");
    		$toinfo = Db::name('user')->where('id',$uid)->field('nickname')->find();
    		return ["toname"=>$toinfo['nickname']];
    	}
    }
}