<?php

/**
 * Created by PhpStorm.
 * User: 徐强
 * Date: 2019/4/26
 * Time: 10:51
 */
require_once "Extend/fxMember.php";
require_once "Extend/Res.php";
require_once "Extend/fxConfig.php";
defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_func('global');
pc_base::load_sys_class('form', '', 0);
pc_base::load_sys_class('format', '', 0);
use zysd\classes\SdControl as sd;
//Api函数(返回数据暂时没处理)
class frontApi
{
    function __construct()
    {
        $this->member = new Fx();

    }
    function insertMember($userid)//创建用户的时候插入分销用户表和提现中，
    {
        $neadArg = ["userid"=>[true, 1]];
        $info = checkArg($neadArg, $userid);
        Res::AssertOk($this->member->insertMember($info), "2");
//        returnAjaxData("200", "添加成功");
    }
    function updateMemberLoginTime()
    {
        $neadArg = ["userid"=>[true, 6]];
        $info = checkArg($neadArg, $_POST);
        $this->member->updateLoginTime($info);
        returnAjaxData("200", "修改成功");

    }
    function getMemberInfo()//得到用户所有信息
    {
        $neadArg = ["userid"=>[true, 6]];
        $info = checkArg($neadArg, $_POST);
        $s = $this->member->getMemberInfo($info);
        if($s == null)
            returnAjaxData("-1", "没有该用户信息");
        returnAjaxData("200", "成功", $s);
    }
    function getTeamInfo()//得到所有下级队员的信息
    {
        $neadArg = ["userid"=>[true, 6,"请登录"], "grade"=>[false, 1]];
        $info = checkArg($neadArg, $_POST);
        $grade = isset($info['grade'])?array_pop($info):0;
        $s = $this->member->getMemberInfo($info);
        if($s == null)
            returnAjaxData("-1", "没有该用户信息");
        $data = array(0=>array(), 1=>array(), 2=>array(), 3=>array());
        $s = $this->member->getTeamID($info, 0, $data, $grade);
        $in = array_shift($s);
        returnAjaxData("200", "成功", $s);
    }
    function addchild()//添加下级队员
    {
        $neadArg = ["userid"=>[true, 6], "pid"=>[true, 0]];
        $info = checkArg($neadArg, $_POST);
        $member_db=pc_base::load_model("member_model");
        $data=$member_db->get_one(["username"=>$info["pid"]], "userid,nickname");
        $info["pid"] = $data["userid"];
        $userid["userid"] = $info["userid"];
        $pid["pid"] = $info["pid"];
        if($userid["userid"] == $pid["pid"])
            returnAjaxData("-1", "无法添加自己为上级");
        Res::AssertOk($this->member->addchild($userid, $pid) ,"2");
        $member_db->update(["extra_times"=>"+=2"],["userid"=>$info["userid"]]);
        $sd = new sd();
        $sd->add_message($info["userid"],"成功添加".$data["nickname"]."为上级");
        $data_2=$member_db->get_one(["userid"=>$info["userid"]], "userid,nickname");
        $sd->add_message($info["pid"],"成功添加".$data_2["nickname"]."为下级");
        returnAjaxData("200", "添加成功");
    }
    function addchild_2($info)
    {
        $neadArg = ["userid"=>[true, 1], "pid"=>[true, 0]];
        $info = checkArg($neadArg, $info);
        $member_db=pc_base::load_model("member_model");
        $data=$member_db->get_one(["userid"=>$info["pid"]], "userid,nickname");
        $info["pid"] = $data["userid"];
        $userid["userid"] = $info["userid"];
        $pid["pid"] = $info["pid"];
        if($userid["userid"] == $pid["pid"])
            returnAjaxData("-1", "无法添加自己为上级");
        Res::AssertOk($this->member->addchild($userid, $pid) ,"2");
        $member_db->update(["extra_times"=>"+=2"],["userid"=>$info["userid"]]);
        $sd = new sd() ;
        $sd->add_message($info["userid"],"成功添加".$data["nickname"]."为上级");
        $data_2=$member_db->get_one(["userid"=>$info["userid"]], "userid,nickname");
        $sd->add_message($info["pid"],"成功添加".$data_2["nickname"]."为下级");
    }
    function addchild_yqm()//添加下级队员
    {
        $neadArg = ["userid"=>[true, 6], "yqm"=>[true, 0]];
        $info = checkArg($neadArg,'POST');
        $userid["userid"] = $info["userid"];
        $s = $this->member->getMemberInfo($userid);
        if($s["pid"] != 0)
            returnAjaxData("-2", "已经存在上级,请勿重复添加");
        $member = $this->member->getMember($info);
        $pid["pid"] = $member["userid"];
        if($userid["userid"] == $pid["pid"])
            returnAjaxData("-1", "无法添加自己为上级");
        Res::AssertOk($this->member->addchild($userid, $pid) ,"2");
        returnAjaxData("200", "添加成功");
    }

    function awardMoney($userid, $SID)//奖励钱****
    {
//        $neadArg = ["userid"=>[true, 1, "真的吗"], "SID"=>[true, 0]];
//        $info = checkArg($neadArg, $_POST);
//        $SID = array_pop($info);
//        $shopDB = pc_base::load_model("zyshop_model");
//        $shopInfo = $shopDB->get_one(["SID"=>$SID], "brokerage");
        $config = new fxConfig();
        $cof = $config->returnConfig("awardNumber");
        $shopprice = json_decode($cof, true);
        foreach ($shopprice as $key=>$value)
        {
            $shopprice[$key] = $value * $SID/100;
        }
//        returnAjaxData("-1","d",$shopprice);
        Res::AssertOk($this->member->awardMoney(["userid"=>$userid],$shopprice),"2");
//        returnAjaxData("200", "奖励成功");
    }
    function TX()
    {
        $neadArg = ["userid"=>[true, 1], "money"=>[true, 1]];
        $info = checkArg($neadArg, $_POST);
        $money = array_pop($info);
        $TXmoney = Res::AssertOk($this->member->TX($info, $money), "2");
        returnAjaxData("200", "得到金额成功", $TXmoney);
    }
    function TXAffirm()//
    { 
        $neadArg = ["userid"=>[true, 6], "money"=>[true, 0]];
        $info = checkArg($neadArg, $_POST);
        $money = array_pop($info);
        Res::AssertOk($this->member->TXAffirm( $info, $money),"2");
        returnAjaxData("200", "提现成功");
    }
    function getMoneyInfo()
    {
        $neadArg = ["userid"=>[true, 6]];
        $info = checkArg($neadArg, $_POST);
        $moneyInfo = Res::AssertOk($this->member->getMoneyInfo($info), '2');
        returnAjaxData("200", "查询成功", $moneyInfo);
    }
}