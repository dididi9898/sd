<?php
defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_func('global');
pc_base::load_sys_class('form', '', 0);
pc_base::load_sys_class('format', '', 0);
require_once dirname(__FILE__).'/index.php';
require_once 'classes/phpqrcode/QRcode.class.php';
class api
{
    public function __construct()
    {
        $this->get_db = pc_base::load_model('get_model');
        $this->member_db = pc_base::load_model('member_model');
        $this->zyconfig_db = pc_base::load_model('zyconfig_model');
        $this->zyfxconfig = pc_base::load_model('zyfxconfig_model');
		$this->qrcode_db = pc_base::load_model('zyqrcode_model');
		$this->fx = pc_base::load_model("zyfxmember_model");
		$this->captain = pc_base::load_model("zycaptain_model");
        $this->_userid = param::get_cookie('_userid');
    }
	public function checkMember()
	{
		$_userid = $this->_userid;
		$member =  $this->fx->get_one(["userid"=>$_userid]);
		if(!empty($member))
			exit($member["iscaptain"]);
		else
			exit('-200');
	}
	public function moneyPay()
	{	
		$member = pc_base::load_model('member_model');
		$fxConfig = $this->zyfxconfig->get_one(["id"=>1]);
		$memberInfo = $member->get_one(["userid"=>$this->_userid]);
		if($memberInfo["amount"]< $fxConfig["captainmoney"])
			exit('-1');
		$memberInfo = $member->update(["amount"=>"-=".$fxConfig["captainmoney"]],["userid"=>$this->_userid]);
		$this->fx->update(["iscaptain"=>2], ["userid"=>$this->_userid]);
		$caption_info_db = pc_base::load_model('zycaptain_model');
		$info["addtime"] = date("Y-m-d H:i:s",time());
		$info["userid"] = $this->_userid;
		$info["money"] = $fxConfig["captainmoney"];
		$info["trade_no"] = date("YmdHis",time()) + rand(1, 2000);
		$info["type"] = 1;
		$caption_info_db->insert($info);
		$zyfxmoney_db = pc_base::load_model('zyfxmoney_model');
		$zyfxmoney_db->update(["WTXmoney"=>"+=".$info["money"], "moneycount"=>"+=".$info["money"]],array("userid"=>$this->_userid));
		exit('1');
	}
	public function captain()
	{
		$info = $this->captain->moreTableSelect(["zy_zycaptain"=>["addtime", "userid"], "zy_member"=>["nickname", "realname", "mobile"]], ["userid"], "1", "15", "addtime DESC");
		$config = $this->zyfxconfig->get_one(["id"=>1]);
		returnAjaxData("1", "成功", ["info"=>$info, "config"=>$config]);
	}
	/**
     * 获取会员信息
     * @param $project
     * @return json
     */
    public function qrcode_api($project)
    {
		$project = empty($_POST['project']) ? 0 : $_POST['project'];
		//$sql="SELECT * FROM zy_zyqrcode WHERE isshow=1 AND project='".$project."' ORDER BY id DESC";
		$info = $this->qrcode_db->select('isshow=1 AND project="'.$project.'"','`id`,`project`,`name`,`url`,`thumb`,`qrcode`','', $order = 'id DESC');
        if($info){
            $json['status']='success';
            $json['code']='200';
            $json['message']='操作成功';
            $json['data']=$info;
        }else{
            $json['status']='error';
            $json['code']='-200';
            $json['message']='数据为空';
        }
		
		echo "<pre>";
        exit(json_encode($json,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        echo  "</pre>";
    }

    /**
     * 在线生成二维码
     * @param msg 是否带缩略图 1是 其他否
     * @param thumb 缩略图
     * @param userid 用户id
     * @return json
     */
    public function create_qrcode_api()
    {
        $data=checkArg(["userid"=>[true,6,"请输入用户ID"],"update"=>[false,1]],$_POST);
        $memberinfo=$this->member_db->get_one(["userid"=>$data['userid']]);
        if($memberinfo["qrcode"]){
            if(isset($data["update"])){

            }else{
                returnAjaxData(200,"操作成功",['qrcode'=>$memberinfo["qrcode"]]);
            }
        }

        $msg = 0;
        $url=APP_PATH."index.php?m=zymember&c=index&a=register"."&token=".$memberinfo["username"];

        if(!empty($data['userid'])){
//            $token=sys_auth($data['userid'], 'ENCODE', 'add');
            if ($msg==1){
                $data['qrcode']= $this->create_qrcode_pic($url,$memberinfo['headimgurl']);
            }else {
                $data['qrcode'] = $this->create_qrcode($url);
            }
        }else{
            if ($msg==1){
                $data['qrcode']= $this->create_qrcode_pic($url,$memberinfo['headimgurl']);
            }else {
                $data['qrcode'] = $this->create_qrcode($url);
            }
        }
        if($data['qrcode']){
            $this->member_db->update(["qrcode"=>$data['qrcode']],["userid"=>$memberinfo['userid']]);
            $json['status']='success';
            $json['code']=200;
            $json['message']='操作成功';
            $json['data']['qrcode']=$data['qrcode'];
        }else{
            $json['status']='error';
            $json['code']=-200;
            $json['message']='数据为空';
        }
        exit(json_encode($json,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    }
    /**
     * 在线生成二维码
     * @param thumburl 二维码地址
     * @return json
     */
    public function destroy_qrcode_api()
    {
        $thumburl = empty($_POST['thumburl']) ? 0 : $_POST['thumburl'];
        $thumburl = $this->strget($thumburl); //准备好的logo图片
        if($thumburl){
            if(file_exists($thumburl)) {
                unlink($thumburl);
                $json['status']='success';
                $json['code']='200';
                $json['message']='删除成功';
            }else{
                $json['status']='error';
                $json['code']='-1';
                $json['message']='文件不存在';
            }
        }else{
            $json['status']='error';
            $json['code']='-200';
            $json['message']='参数为空';
        }

        exit(json_encode($json,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    }


    //生成文件路径
    function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
        if (!mkdirs(dirname($dir), $mode)) return FALSE;
        return @mkdir($dir, $mode);
    }

    /*
     * 更新二维码
     * */
    //生成原始的二维码(生成图片文件)
    public function create_qrcode($url=''){
        //require_once 'QRcode.class.php';
        $value = $url;                //二维码内容
        $errorCorrectionLevel = 'L';  //容错级别
        $matrixPointSize = 5;         //生成图片大小
        //生成二维码图片
        $this->mkdirs("uploadfile/qrcode/");
        $filename = 'uploadfile/qrcode/'.time().'.png';
        QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
        return $filename;
    }

    // 在生成的二维码中加上logo(生成图片文件)
    function create_qrcode_pic($url='',$logo_path=''){
        //require_once 'phpqrcode.php';
        $value = $url;         //二维码内容
        $errorCorrectionLevel = 'H';  //容错级别
        $matrixPointSize = 6;      //生成图片大小
        //生成二维码图片
        $this->mkdirs("uploadfile/qrcode/");
        $filename = 'uploadfile/qrcode/'.time().'.png';
        QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
        $logo = $this->strget($logo_path); //准备好的logo图片
        //$QR=$filename;//已经生成的原始二维码图
        if (file_exists($logo)) {
            $QR = imagecreatefromstring(file_get_contents($filename));    //目标图象连接资源。
            $logo = imagecreatefromstring(file_get_contents($logo));  //源图象连接资源。
            $QR_width = imagesx($QR);      //二维码图片宽度
            $QR_height = imagesy($QR);     //二维码图片高度
            $logo_width = imagesx($logo);    //logo图片宽度
            $logo_height = imagesy($logo);   //logo图片高度
            $logo_qr_width = $QR_width / 4;   //组合之后logo的宽度(占二维码的1/5)
            $scale = $logo_width/$logo_qr_width;  //logo的宽度缩放比(本身宽度/组合后的宽度)
            //$logo_qr_height = $logo_height/$scale; //组合之后logo的高度
            $logo_qr_height = $logo_qr_width; //组合之后logo的高度
            $from_width = ($QR_width - $logo_qr_width) / 2;  //组合之后logo左上角所在坐标点
            //重新组合图片并调整大小
            /*
             * imagecopyresampled() 将一幅图像(源图象)中的一块正方形区域拷贝到另一个图像中
             */
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height);
        }
        //输出图片
        imagepng($QR, $filename);
        imagedestroy($QR);
        imagedestroy($logo);
        return $filename;
    }

    /*
	 * 截取字符
	 * $string 输入字符串
	 * return 返回uploadfile之后的字符串
	 * */
    public function strget($string=''){
        if(strpos($string,'statics')){
            $newstring= strstr( $string, 'statics'); //默认返回查找值@之后的尾部，@jb51.net
        }else if(strpos($string,'uploadfile')){
            $newstring= strstr( $string, 'uploadfile'); //默认返回查找值@之后的尾部，@jb51.net
        }else{
            $newstring=$string;
        }

        return $newstring;
    }
}