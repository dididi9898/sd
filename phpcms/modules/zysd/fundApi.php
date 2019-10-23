<?php
/**
 * Created by PhpStorm.
 * User: 徐强
 * Date: 2019/7/12
 * Time: 8:41
 */
defined('IN_PHPCMS') or exit('No permission resources.');
pc_base::load_app_func('global');
pc_base::load_sys_class('form', '', 0);
pc_base::load_sys_class('format', '', 0);
pc_base::load_sys_class('Res', '', 0);

use zysd\classes\FundControl as fc;

class fundApi
{
    public $fund;
    function __construct()
    {
        $this->fund = new fc();
    }

    //添加银行卡
    public function add_bank_card(){
        $data=checkArg(["userid"=>[true,1,"请输入用户ID"],"bank_cardid"=>[true,0,"请输入银行卡号"],"bank_name"=>[true,0,"请输入开户银行"],"bank_branch"=>[true,0,"请输入所属支行"],"owner_name"=>[true,0,"请输入持卡人姓名"]],$_POST);
        $id=$this->fund->add_bank_card($data);
        if($id){
            returnAjaxData(200,"操作成功",$id);
        }else{
            returnAjaxData(-200,"操作失败");
        }
    }

    //编辑银行卡
    public function edit_bank_card(){
        $data=checkArg(["bid"=>[true,1,"请输入ID"],"bank_cardid"=>[false,0,"请输入银行卡号"],"bank_name"=>[false,0,"请输入开户银行"],"bank_branch"=>[false,0,"请输入所属支行"],"owner_name"=>[false,0,"请输入持卡人姓名"]],$_POST);
        $id=$this->fund->edit_bank_card($data,$data['bid']);
        if($id){
            returnAjaxData(200,"操作成功",$id);
        }else{
            returnAjaxData(-200,"操作失败");
        }
    }

    //删除银行卡
    public function del_bank_card(){
        $data=checkArg(["bid"=>[true,1,"请输入ID"]],$_POST);
        $id=$this->fund->del_bank_card($data['bid']);
        if($id){
            returnAjaxData(200,"操作成功",$id);
        }else{
            returnAjaxData(-200,"操作失败");
        }
    }

    //银行卡信息
    public function get_bank_card()
    {
        $data=checkArg(["userid"=>[true,1,"请输入用户ID"],"page"=>[false,0,"请输入page"],"pagesize"=>[false,0,"请输入pagesize"]],$_POST);
        $where="1";
        if($data['userid']){
            $where.=" AND userid=".$data['userid'];
        }
        list($info,$pagenums, $pageStart, $pageCount)=$this->fund->bank_card_list($where,$data['page']);
        if($info){
            returnAjaxData(200,"操作成功",['data'=>$info,'pagenums'=>$pagenums, 'pageStart'=>$pageStart, 'pageCount'=>$pageCount]);
        }else{
            returnAjaxData(200,"暂无数据",['data'=>$info,'pagenums'=>$pagenums, 'pageStart'=>$pageStart, 'pageCount'=>$pageCount]);
        }
    }


    //账本记录
    public function account_records()
    {
        $data=checkArg(["userid"=>[true,1,"请输入用户ID"],"type"=>[true,1,"请输入类型"],"page"=>[true,0,"请输入page"],"pagesize"=>[true,0,"请输入pagesize"]],$_POST);
        $info=$this->fund->account_list(['userid'=>$data['userid'],'type'=>$data['type']],$data['page'],$data['pagesize']);
        if($info){
            returnAjaxData(200,"操作成功",$info);
        }else{
            returnAjaxData(200,"暂无数据");
        }
    }



    //提现申请
    public function tx_apply()
    {
        $data=checkArg(["userid"=>[true,1,"请输入用户ID"],"fund_money"=>[true,1,"请输入提现金额"],"bankcard_id"=>[true,0,"请选择银行卡"]],$_POST);
        $info=[
            'userid'=>$data['userid'],
            'fund_type'=>2,
            'fund_money'=>$data['fund_money'],
            'bankcard_id'=>$data['bankcard_id'],
            'addtime'=>date("Y-m-d H:i:s",time()),
        ];
        $id=$this->fund->fund_application($info);
        if($id){
            returnAjaxData(200,"操作成功",$id);
        }else{
            returnAjaxData(-200,"操作失败");
        }
    }

    /**
     * 完善资料
     * @status [状态] -1手机号码不能为空/-2用户名格式错误/-3帐号不存在/-4短信验证码错误/-5密码格式错误/-11 密码输入不一致/-100操作错误，进度错误
     *
     * @param [type] $mobile
     *            [*手机号码]
     * @param [type] $verify_code
     *            [*手机验证码]
     * @param [type] $password
     *            [2*用户密码]
     * @param [type] $repassword
     *            [2*重复密码]
     * @param [type] $progress
     *            [*进度：1输入手机号码；2发送短信验证码；3设置密码]
     * @param [type] $type
     *            [*类型：1web端、2APP端]
     * @param [type] $forward
     *            [接下来该跳转的页面链接]
     * @return [json] [json数组]
     */
    public function edit_card()
    {
        $type = $_POST['type'] ? $_POST['type'] : 1; // 类型：1web端、2APP端
        $forward = $_POST['forward'] ? urldecode($_POST['forward']) : APP_PATH . 'index.php?m=member&c=index'; // 接下来该跳转的页面链接

        $mobile = $_POST['mobile']; // 手机号
        $verify_code = $_POST['verify_code']; // 短信验证码
        $realname = $_POST['realname'];
        $bankname = $_POST['bankname'];
        $bankcard = $_POST['bankcard'];
        $idcard = $_POST['idcard'];
        $userid = $_POST['userid'];
        $idcard_positive = $_POST['idcard_positive'];
        $idcard_negative = $_POST['idcard_negative'];
        if (! $realname || ! $bankname || ! $bankcard || ! $idcard || ! $userid|| ! $idcard_positive|| ! $idcard_negative) {
            $result = [
                'status' => 'error',
                'code' => - 1,
                'message' => '参数不能为空'

            ];
            exit(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        // 用手机号码查出用户账号
        $memberinfo = $this->member_db->get_one(array(
            'userid' => $userid
        ));

        // ================== 操作失败-验证 START
        // 帐号密码类型不能为空
        if (! $mobile) {
            $result = [
                'status' => 'error',
                'code' => - 1,
                'message' => '手机号码不能为空'

            ];
            exit(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        // 用户名格式验证（手机号码格式验证）
        if (! $this->_verify_ismobile($mobile)) {
            $result = [
                'status' => 'error',
                'code' => - 2,
                'message' => '手机号码格式错误' // 只允许 13，14，15，16，17，18，19的号码,11位

            ];
            exit(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        // 帐号不存在
        if (! $memberinfo) {
            $result = [
                'status' => 'error',
                'code' => - 3,
                'message' => '帐号不存在' // 帐号不存在

            ];
            exit(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        // ================== 操作失败-验证 END

        // ================== 操作失败-验证 START
        // 短信验证码错误
        // 调用通讯模块-短信接口-查询此账号的短信验证码是否匹配上了
        // $sms_verify = true;
        // ================== 获取其他接口-接口 START
        $config = $this->zyconfig_db->get_one(array(
            'key' => 'zymessagesys4'
        ), "url");
        $curl = [
            'mobile' => $mobile,
            'verify_code' => $verify_code,
            'clear' => 1
        ];
        $sms_verify = _crul_post($config['url'], $curl);
        $sms_verify = json_decode($sms_verify, true);
        // ================== 获取其他接口-接口 END

        if ($sms_verify['status'] == 'error') { // false,进入
            $result = [
                'status' => 'error',
                'code' => - 4,
                'message' => $sms_verify['message'] // 短信验证码错误

            ];
            exit(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        // 扣除手续费
        $curl = [
            'userid' => $userid,
            'type' => 1
        ];
        $sms_verify = _crul_post(APP_PATH . 'index.php?m=zyadm&c=api&a=service_charge', $curl);
        $sms_verify = json_decode($sms_verify, true);
        // ================== 获取其他接口-接口 END
        if ($sms_verify['status'] == 'error') { // false,进入
            $result = [
                'status' => 'error',
                'code' => - 5,
                'message' => $sms_verify['message'] // 扣除手续费错误
            ];
            exit(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        // ================== 操作失败-验证 END

        // ================== 操作成功-修改数据 START
        // 调用通讯模块-短信接口-清空此账号的短信验证码
        // 操作成功之后删除遗留的短信验证码
        // ================== 获取其他接口-接口 START
        $config = $this->zyconfig_db->get_one(array(
            'key' => 'zymessagesys5'
        ), "url");
        $curl = [
            'mobile' => $memberinfo['mobile']
        ];
        _crul_post($config['url'], $curl);
        // ================== 获取其他接口-接口 END

        // 更改数据库密码
        $data = [
            'realname' => $_POST['realname'],
            'bankname' => $_POST['bankname'],
            'bankcard' => $_POST['bankcard'],
            'idcard' => $_POST['idcard'],
            'idcard_positive' => $_POST['idcard_positive'],
            'idcard_negative' => $_POST['idcard_negative']
        ];

        $this->member_db->update($data, array(
            'userid' => $memberinfo['userid']
        ));
        if($this->is_all_set($memberinfo['userid'])){
            $this->member_db->update(array('identifiy_status'=>1),array(
                'userid' => $memberinfo['userid']
            ));
        }
        $result = [
            'status' => 'success',
            'code' => 200,
            'message' => '等待审核',
            'data' => [
                'userid' => $memberinfo['userid'],
                'groupid' => $memberinfo['groupid'],
                'forward' => $forward // 给web端用的，接下来跳转到哪里
            ]
        ];
        exit(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        // ================== 操作成功-修改数据 END
    }
}