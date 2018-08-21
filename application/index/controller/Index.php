<?php
namespace app\index\controller;
use think\Controller;

class Index extends Controller
{
    public function index()
    {
    	$fromid = input('fromid')?input('fromid'):89;
    	$toid = input('toid')?input('toid'):88;
    	$this->assign('fromid', $fromid);
    	$this->assign('toid', $toid);
        return $this->fetch();
    }
}
