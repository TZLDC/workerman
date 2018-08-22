<?php
namespace app\api\controller;
use think\Controller;
use think\Db;

class Chat extends Controller
{
    public function save()
    {
    	if(request()->isAjax()){
    		$message = input("post.")['data'];
            $datas['fromid']=$message['fromid'];
            $datas['toid']=$message['toid'];
            $datas['content']=$message['data'];
            $datas['time']=$message['time'];
            // $datas['isread']=$message['isread'];
            $datas['isread']=0;
            $datas['type'] = 1;
            Db::name("communication")->insert($datas);
    	}
    }

    public function get_head(){

        if(request()->isAjax()){
            $fromid = input('fromid');
            $toid = input('toid');

            $frominfo = Db::name('user')->where('id',$fromid)->field('headimgurl')->find();
            $toinfo = Db::name('user')->where('id',$toid)->field('headimgurl')->find();
            $count =  Db::name('communication')->where('(fromid=:fromid and toid=:toid) || (fromid=:toid1 and toid=:fromid1)',['fromid'=>$fromid,'toid'=>$toid,'toid1'=>$toid,'fromid1'=>$fromid])->count('id');
            if($count>=10){
             $message = Db::name('communication')->where('(fromid=:fromid and toid=:toid) || (fromid=:toid1 and toid=:fromid1)',['fromid'=>$fromid,'toid'=>$toid,'toid1'=>$toid,'fromid1'=>$fromid])->limit($count-10,10)->order('id')->select();
            }else{
              $message = Db::name('communication')->where('(fromid=:fromid and toid=:toid) || (fromid=:toid1 and toid=:fromid1)',['fromid'=>$fromid,'toid'=>$toid,'toid1'=>$toid,'fromid1'=>$fromid])->order('id')->select();
            }
            $toname = Db::name('user')->where('id',$toid)->field('nickname')->find();
            return [
                'from_head'=>$frominfo['headimgurl'],
                'to_head'=>$toinfo['headimgurl'],
                'message'=>$message,
                'toname' => $toname['nickname']
            ];
        }
    }

    public function changeNoRead(){
        if(request()->isAjax()){
            $fromid = input('toid');
            $toid = input('fromid');
            Db::name('communication')->where(['fromid'=>$fromid,"toid"=>$toid])->update(['isread'=>1]);
        }

    }

    /**
     * 根据用户id返回用户姓名；
     */
    public function getName($id){
            $uid = $id;
            $toinfo = Db::name('user')->where('id',$uid)->field('nickname')->find();
            return ["toname"=>$toinfo['nickname']];
    }

    public function get_list(){
        if(request()->isAjax()){
            $fromid = input('id');
            $info  = Db::name('communication')->field(['fromid','toid'])->where('toid',$fromid)->group('fromid')->select();

            $rows = array_map(function($res){
                return [
                    'head_url'=>$this->get_head_one($res['fromid']),
                    'username'=>$this->getName($res['fromid'])['toname'],
                    'countNoread'=>$this->getCountNoread($res['fromid'],$res['toid']),
                    'last_message'=>$this->getLastMessage($res['fromid'],$res['toid']),
                    'chat_page'=>"http://web.workerman.com?fromid={$res['toid']}&toid={$res['fromid']}"
                ];

            },$info);

            return $rows;
        }
    }

    /**
     * @param $fromid
     * @param $toid
     * 根据fromid来获取fromid同toid发送的未读消息。
     */
    public function getCountNoread($fromid,$toid){

        return Db::name('communication')->where(['fromid'=>$fromid,'toid'=>$toid,'isread'=>0])->count('id');

    }

    /**
     * @param $uid
     * 根据uid来获取它的头像
     */
    public function get_head_one($uid){

        $fromhead = Db::name('user')->where('id',$uid)->field('headimgurl')->find();

        return $fromhead['headimgurl'];
   }

   /**
     * @param $fromid
     * @param $toid
     * 根据fromid和toid来获取他们聊天的最后一条数据
     */
    public function getLastMessage($fromid,$toid){

        $info = Db::name('communication')->where('(fromid=:fromid&&toid=:toid)||(fromid=:fromid2&&toid=:toid2)',['fromid'=>$fromid,'toid'=>$toid,'fromid2'=>$toid,'toid2'=>$fromid])->order('id DESC')->limit(1)->find();

        return $info;
    }

    /**
     * 上传图片，返回图片地址
     */
    public function uploadimg(){

        $file = $_FILES['file'];
        $fromid = input('fromid');
        $toid = input('toid');
        $suffix =  strtolower(strrchr($file['name'],'.'));
        $type = ['.jpg','.jpeg','.gif','.png'];
        if(!in_array($suffix,$type)){
            return ['status'=>'img type error'];
        }

        if($file['size']/1024>5120){
            return ['status'=>'img is too large'];
        }

        $filename =  uniqid("chat_img_",false);
        $uploadpath = ROOT_PATH.'public\\uploads\\';
        $file_up = $uploadpath.$filename.$suffix;
        $re = move_uploaded_file($file['tmp_name'],$file_up);

        if($re){
            $name = $filename.$suffix;
            $data['content'] = $name;
            $data['fromid'] = $fromid;
            $data['toid'] = $toid;
            $data['time'] = time();
           // $data['isread'] = $online;
            $data['isread'] = 0;
            $data['type'] = 2;
            $message_id = Db::name('communication')->insertGetId($data);
            if($message_id){
                return['status'=>'ok','img_name'=>$name];
            }else{
                return ['status'=>'false'];
            }

        }
    }
}