<?php

namespace App\Action;
/**
 * 第三方登录
 *
 * @author zhangcheng <lampzhangcheng@gmail.com>
 * @version $Id$
 * @package
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright Copyright (c) 2014, ZhangCheng 2014/04/21
 */
class SocialAction extends CommonAction {
	/*
	 * @brief 设置每一页标题
	 */
	public function _initialize() {
		parent::_initialize ();
		$action_name = strtolower ( ACTION_NAME );
		switch ($action_name) {
			case 'account' :
				$this->assign ( "website_title", "帐号设置" );
				break;
		}
	}

	/*
	 * 第三方登录 判断 页面 已绑定 跳转至来源页 未绑定 跳转至绑定页
	 */
	public function index() {
		$this->display ( "public:404" );
	}
	public function baidu() {
		if ($_SESSION ['has_login_by_social'] == 1) {


			cookie ( null );
			session ( null );

			header ( "location:".NET_NAME );
			exit ();
		}

		import ( "ORG.Util.Baidu" );
		if (isset ( $_REQUEST ['code'] )) {
			$code = $_REQUEST ['code'];
		} else {
			cookie ( null );
		}
		// $code = $_REQUEST['code'] ? $_REQUEST['code'] : ;

		$client_id = 'fgIIRGufGdzh5h0mE0BT4tM1';
		$client_secret = 'FhawiSi2FlSxIBlvn0i0eeLqut8k1hmE';
		$redirect_uri = "http://www." . $_SERVER ["SERVER_NAME"].'/social/baidu.html';

		$baidu = new Baidu ( $client_id, $client_secret, $redirect_uri, new BaiduCookieStore ( $client_id ) );

		// Get User ID and User Name
		$user = $baidu->getLoggedInUser ();

		if ($user) {

			// dump($user);
			// exit;
			$apiClient = $baidu->getBaiduApiClientService ();
			$profile = $apiClient->api ( '/rest/2.0/passport/users/getInfo', array (
					'fields' => 'userid,username,portrait'
			) );
			// portrait -> 头像
			if ($profile === false) {

				// get user profile failed
				 var_dump(var_export(array('errcode' => $baidu->errcode(),
				 'errmsg' => $baidu->errmsg()), true));

				var_dump ( $profile );
				$user = null;

				exit ();
			} else {
				// 绑定账号
				// 设置session信息
				// dump($profile);
				$_SESSION ['Auth'] ['Social'] = $profile;
				$_SESSION ['Auth'] ['Social'] ['avatar_img'] = "http://tb.himg.baidu.com/sys/portrait/item/" . $profile ['portrait'];

				$_SESSION ['Auth'] ['Social'] ['type'] = 'baidu';
				header ( "location:".NET_NAME . "/social/account.html?ltype=baidu" );
				exit ();
			}
		} else {
			// 跳转到登录页
			$loginUrl = $baidu->getLoginUrl ( '', 'popup' );
			header ( "location:" . $loginUrl );
		}
	}
	public function qq() {
		if ($_SESSION ['has_login_by_social'] == 1) {


			cookie ( null );
			session ( null );

			header ( "location:".NET_NAME );
			exit ();
		}

		require ROOT_PATH."/qq/qqConnectAPI.php";
		$qc = new \QC ();

		$access_token = $qc->qq_callback ();
		$openid = $qc->get_openid ();
		$qc = new \QC ( $access_token, $openid );

		$arr = $qc->get_user_info ();

		if ($arr ["ret"] != 0) {
			// 登录有错误 需要跳转
			exit ( "error login" );
		}
		$_SESSION ['Auth'] ['Social'] = $arr;
		$_SESSION ['Auth'] ['Social'] ['access_token'] = $access_token;
		$_SESSION ['Auth'] ['Social'] ['openid'] = $openid;
		$_SESSION ['Auth'] ['Social'] ['avatar_img'] = $arr ['figureurl_2'];
		$_SESSION ['Auth'] ['Social'] ['userid'] = $openid;
		$_SESSION ['Auth'] ['Social'] ['username'] = $arr ['nickname'];
		$_SESSION ['Auth'] ['Social'] ['type'] = 'qq';

		// 进入统一跳转
		header ( "location:".NET_NAME . "/social/account.html?ltype=qq" );
	}
	public function sina() {
		if ($_SESSION ['has_login_by_social'] == 1) {


			cookie ( null );
			session ( null );

			header ( "location:".NET_NAME );
			exit ();
		}
		$_SESSION ['has_login_by_social'] = 0;


		$o = new \SaeTOAuthV2 ( WB_AKEY, WB_SKEY );
		if (isset ( $_REQUEST ['code'] )) {
			$keys = array ();
			$keys ['code'] = $_REQUEST ['code'];
			$keys ['redirect_uri'] = WB_CALLBACK_URL;
			try {
				$token = $o->getAccessToken ( 'code', $keys );
			} catch ( OAuthException $e ) {
			}

			if ($token) {
				$_SESSION ['token'] = $token;
				setcookie ( 'weibojs_' . $o->client_id, http_build_query ( $token ) );
				// 获取用户信息
				//
				$c = new \SaeTClientV2 ( WB_AKEY, WB_SKEY, $_SESSION ['token'] ['access_token'] );
				$ms = $c->home_timeline (); // done
				$uid_get = $c->get_uid ();
				$uid = $uid_get ['uid'];
				$user_message = $c->show_user_by_id ( $uid );

				// 进入统一
				$_SESSION ['Auth'] ['Social'] ['avatar_img'] = $user_message ['avatar_hd'];

				$_SESSION ['Auth'] ['Social'] ['type'] = 'sina';
				$_SESSION ['Auth'] ['Social'] ['username'] = $user_message ['name'];
				$_SESSION ['Auth'] ['Social'] ['userid'] = $token ['uid'];
				$_SESSION ['Auth'] ['Social'] ['access_token'] = $token ['access_token'];


				header ( "location:". NET_NAME . "/social/account.html?ltype=sina" );
				// dump($_SESSION);
			} else {
				// 授权失败 跳转登录页
				header ( "location:". NET_NAME . "/social/sinalogin.html" );
			}
		}
	}

	/* 新浪登录页url获取与跳转 */
	public function sinalogin() {

		$o = new \SaeTOAuthV2 ( WB_AKEY, WB_SKEY );
		$code_url = $o->getAuthorizeURL ( WB_CALLBACK_URL );

		header ( "location:" . $code_url . "&forcelogin=true" );
	}

	/* qq登录页 获取与跳转 */
	public function qqAuth() {
		require ROOT_PATH."/qq/qqConnectAPI.php";
		$qc = new \QC ();
		$qc->qq_login ();
	}
	/**
	 * 需要session中第三方登录获取信息支持
	 *
	 *
	 *
	 * @access public
	 * @param
	 *
	 * @return void
	 * @author zhangcheng
	 */
	public function account() {
		// 根据类型进行参数重置
		/*
		 * 姓名 头像 第三方标示ID access_token / openid type
		 */

		// 绑定账号
		//dump($_SESSION);
		// dump($_COOKIE);

		// 设置不可返回
		$_SESSION ['has_login_by_social'] = 1;
		$type = "baidu";
		// 分配 第三方登录 标识
		switch ($_REQUEST["ltype"]) {
			case 'baidu' :
				$type = "baidu";
				break;
			case 'qq' :
				$type = "qq";
				break;
			case 'sina' :
				$type = "sina";
				break;
			case 'github':
				$type = "github";
                break;
            case 'battle-us':
                $type = "battle_us";
                break;
		}

		// 查询是否已经绑定过帐号

		$User = M ("User" );
		$where = array($type=>$_SESSION['Auth']['Social']['userid']);
		$result = $User->where($where)->find();

		if ($result) {
			// 进行登录操作

			$_SESSION ['Auth'] ['name'] = $result ['name'];
			$_SESSION ['Auth'] ['id'] = $result ['id'];
			$_SESSION ['Auth'] ['username'] = $result ['username'];//邮箱
			$_SESSION ['Auth'] ['login_type'] = $type;

			header ( "location:".NET_NAME . "/index/index.html" );
			exit ();
		}

		// 未绑定
		//dump($_SESSION);
		$this->assign ( "type", $type );
		$this->assign ( "username", $_SESSION ['Auth'] ['Social'] ['username'] );
		$this->assign ( "avatar_img", $_SESSION ['Auth'] ['Social'] ['avatar_img'] );

		$this->display ();
	}

	public function github() {
		if ($_SESSION ['has_login_by_social'] == 1) {


			cookie ( null );
			session ( null );

			header ( "location:".NET_NAME );
			exit ();
		}

		if($_REQUEST['code']){
			$url = "https://github.com/login/oauth/access_token";
			$data['client_id'] = "553e8a51694f0c2de71f";
			$data['redirect_uri']=urlencode(NET_NAME."/social/github");
			$data['client_secret'] = "96ea1e3ded83c1cd8107cddf20170f76eb2441a4";
			$data['code'] = $_REQUEST['code'];

			$curlPost = '';

			foreach ($data as $key => $value) {
				$curlPost .= ($key.'='.$value.'&');
			}
			$curlPost = rtrim($curlPost,'&');

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
			$data = curl_exec($ch);

			//dump($data);
			curl_close($ch);
			if(empty($data)){
				exit;
			}
			$responseData = explode('&',$data);
			$access_token = explode('=', $responseData[0]);

			$url = "https://api.github.com/user?access_token=".$access_token[1];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			$data = curl_exec($ch);
			//echo $data;
			curl_close($ch);

			$response = json_decode($data,true);

			if($response['error'] != false){
				$this->formErrorReferer('请清除cookie再登陆');
				exit;
			}
			// 进入统一
			$_SESSION ['Auth'] ['Social'] ['avatar_img'] = $response ['avatar_url'];

			$_SESSION ['Auth'] ['Social'] ['type'] = 'github';
			$_SESSION ['Auth'] ['Social'] ['username'] = $response ['login'];
			$_SESSION ['Auth'] ['Social'] ['email'] = $response ['email'];
			$_SESSION ['Auth'] ['Social'] ['userid'] = $response ['id'];
			$_SESSION ['Auth'] ['Social'] ['access_token'] = $access_token[1];
			$_SESSION ['Auth'] ['Social'] ['code'] = $_REQUEST['code'];

			header ( "location:".NET_NAME. "/social/account.html?ltype=github" );

			exit;
	    }

		dump($_REQUEST);
	}

	public function github2()
	{
		if ($_SESSION ['has_login_by_social'] == 1) {

			cookie ( null );
			session ( null );
			header ( "location:".NET_NAME );
			exit ();
		}

		$url = "https://github.com/login/oauth/authorize?";
		$data['client_id'] = "553e8a51694f0c2de71f";
		$data['redirect_uri']=urlencode(NET_NAME."/social/github");
		$data['scope'] = "user,user:email,public_repo";
		$data['state'] = "zc";
		foreach ($data as $key => $value) {
			$url .= ($key."=".$value."&");
		}

		$url = rtrim($url,'&');

		header ( "location:".$url );
	}


	/**
	 * 战网登陆
	 */
	public function battle()
	{
		if ($_SESSION ['has_login_by_social'] == 1) {

			cookie ( null );
			session ( null );
			header ( "location:https://" . $_SERVER ["HTTP_HOST"] );
			exit ();
		}

		$url = "https://www.battlenet.com.cn/oauth/authorize?";
        $url = "https://us.battle.net/oauth/authorize?";
		$data['client_id'] = "qj64g7amth6m79kzax8tf76kuq35tfzn";
        $data['client_id'] = 'jn3f64xrvpvs65ywj45ygcfywkknhgq9';
		$data['redirect_uri']=urlencode(NET_NAME."/social/battle_callback");
		$data['scope'] = "wow.profile";
		$data['state'] = "zc";
		$data['response_type'] = 'code';
		foreach ($data as $key => $value) {
			$url .= ($key."=".$value."&");
		}

		$url = rtrim($url,'&');

		header ( "location:".$url );
	}


	/**
	 * battle token
	 * https://www.battlenet.com.cn/oauth/token
	 */
	public function battle_callback()
	{
		if ($_SESSION ['has_login_by_social'] == 1) {


			cookie ( null );
			session ( null );

			header ( "location:".NET_NAME );
			exit ();
		}
		//dump($_REQUEST);// code state
		//exit;

		if($_REQUEST['code']){
			$url = "https://www.battlenet.com.cn/oauth/token";
            $url = "https://us.battle.net/oauth/token";
			$data['client_id'] = "qj64g7amth6m79kzax8tf76kuq35tfzn";
            $data['client_id'] = 'jn3f64xrvpvs65ywj45ygcfywkknhgq9';
			$data['redirect_uri']= NET_NAME."/social/battle_callback";
			$data['client_secret'] = "EWUYUzp2hCFDtXqUHmFAbGMZ6rEbaMyV";
            $data['client_secret'] = 'vVcTwWYz2J9BPeCTWq9UpyPyGu7ef5Nq';
			$data['scope'] = "wow.profile";
			$data['code'] = $_REQUEST['code'];
			$data['grant_type'] = 'authorization_code';
			//$data['grant_type'] = 'client_credentials';
			$curlPost = '';

			foreach ($data as $key => $value) {
				$curlPost .= ($key.'='.$value.'&');
			}
			$curlPost = rtrim($curlPost,'&');

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
			$data = curl_exec($ch);
			$info = curl_getinfo($ch);

			curl_close($ch);
			//dump($info);
			//dump(json_decode($data,true));
            $tokenArray = json_decode($data,true);


            //根据token请求魔兽世界信息
            $url = "https://us.api.battle.net/wow/user/characters?access_token=".$tokenArray['access_token'];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$data = curl_exec($ch);
			//echo $data;
            //test api
            //https://api.battlenet.com.cn/wow/character/%E4%BC%8A%E6%A3%AE%E5%88%A9%E6%81%A9/%E7%81%AD%E4%B8%96%E7%8B%82%E7%A5%9E?locale=zh_CN&apikey=qj64g7amth6m79kzax8tf76kuq35tfzn&fields=items,titles
			curl_close($ch);

			$response = json_decode($data,true);

            if(!is_array($response) || empty($response))
            {
                $this->formError('获取战网信息失败','/');
            }
            //$response['characters'] 最后一个为第一个人物
            /*
             *
             * array(1) {
  ["characters"] => array(2) {
    [0] => array(11) {
      ["name"] => string(11) "Bigpowerman"
      ["realm"] => string(7) "Illidan"
      ["battlegroup"] => string(7) "Rampage"
      ["class"] => int(12)
      ["race"] => int(4)
      ["gender"] => int(0)
      ["level"] => int(100)
      ["achievementPoints"] => int(1200)
      ["thumbnail"] => string(31) "illidan/62/157846078-avatar.jpg"
      ["spec"] => array(6) {
        ["name"] => string(5) "Havoc"
        ["role"] => string(3) "DPS"
        ["backgroundImage"] => string(17) "bg-rogue-subtlety"
        ["icon"] => string(27) "ability_demonhunter_specdps"
        ["description"] => string(71) "A brooding master of warglaives and the destructive power of Fel magic."
        ["order"] => int(0)
      }
      ["lastModified"] => int(1471355798000)
    }
    [1] => array(13) {
      ["name"] => string(11) "Peakpointer"
      ["realm"] => string(7) "Illidan"
      ["battlegroup"] => string(7) "Rampage"
      ["class"] => int(1)
      ["race"] => int(4)
      ["gender"] => int(0)
      ["level"] => int(100)
      ["achievementPoints"] => int(1200)
      ["thumbnail"] => string(30) "illidan/5/154268677-avatar.jpg"
      ["spec"] => array(6) {
        ["name"] => string(4) "Fury"
        ["role"] => string(3) "DPS"
        ["backgroundImage"] => string(15) "bg-warrior-fury"
        ["icon"] => string(25) "ability_warrior_innerrage"
        ["description"] => string(116) "A furious berserker wielding a weapon in each hand, unleashing a flurry of attacks to carve her opponents to pieces."
        ["order"] => int(1)
      }
      ["guild"] => string(19) "Flying Pastafarians"
      ["guildRealm"] => string(7) "Illidan"
      ["lastModified"] => int(1472488028000)
    }
  }
}
             *
             *
             */
//http://us.battle.net/wow/en/character/illidan/Peakpointer/simple  美服战网英雄榜链接

            //魔兽世界小头像图标 前缀
            // https://render-api-us.worldofwarcraft.com/static-render/us/
            //英雄榜背景图片前缀
            //http://render-api-us.worldofwarcraft.com/static-render/us

            if( ($count = count($response['characters'])) == 0)
            {
                $this->formError('战网角色不存在','/');
            }

			if($response['error'] != false){
				$this->formError('access_token获取失败','/');
			}

			// 进入统一
			$_SESSION ['Auth'] ['Social'] ['avatar_img'] ='http://render-api-us.worldofwarcraft.com/static-render/us/'. $response['characters'][$count-1]['thumbnail'];

			$_SESSION ['Auth'] ['Social'] ['type'] = 'battle-us';
			$_SESSION ['Auth'] ['Social'] ['username'] = $response['characters'][$count-1]['name'] ;
			$_SESSION ['Auth'] ['Social'] ['email'] = '';
			$_SESSION ['Auth'] ['Social'] ['userid'] =$response['characters'][$count-1]['name'] ;
			$_SESSION ['Auth'] ['Social'] ['access_token'] = $tokenArray['access_token'];
			$_SESSION ['Auth'] ['Social'] ['code'] = $_REQUEST['code'];

			header ( "location:".NET_NAME. "/social/account.html?ltype=battle-us" );

			exit;
		}

		dump($_REQUEST);
	}
}
