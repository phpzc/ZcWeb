<?php
/*
 * 数据库长度限制 username 32 password 32 name 32
 */
class UserAction extends CommonAction {
	/*
	 * 登录错误码 设置 error_code 0 用户名或者密码缺失 1 账号不存在 2 密码不正确 3 非法操作 非本网站登录进来的
	 */
	public function login() {
		$error_code = array ();
		
		if ($_POST ["code"] != $_SESSION ["form"] ["code"]) {
			$error_code = array (
					"error_code" => 3,
					"error_str" => "非法操作" 
			);
			echo json_encode ( $error_code );
			exit ();
		}
		
		$User = D ( "User" );
		$email = $this->_post ( "email" );
		$password = $this->_post ( "password" );
		
		$res = $User->where ( array (
				"username" => $email 
		) )->find ();
		if (! $res) {
			$error_code = array (
					"error_code" => 1,
					"error_str" => "账号不存在" 
			);
		} else {
			
			if ($res ['password'] == md5 ( $password )) {
				// 登录操作初始化session、
				$_SESSION ['Auth'] ['name'] = $res ['name'];
				$_SESSION ['Auth'] ['id'] = $res ['id'];
				$_SESSION ['Auth'] ['username'] = $res ['username'];
				// if($this->_post('auto') == 1){
				// cookie
				// setCookie("",);
				// }
				$error_code = array (
						"success" => "1" 
				);
			} else {
				$error_code = array (
						"error_code" => 2,
						"error_str" => "密码不正确" 
				);
			}
		}
		
		echo json_encode ( $error_code );
	}
	/*
	 * 注册错误码 设置 error_code 0 用户名或者密码缺失 1 账号存在 2 昵称重复 3 非法操作 非本网站进来的
	 */
	public function reg() {
		$error_code = array ();
		
		if ($_POST ["code"] != $_SESSION ["form"] ["code"]) {
			$error_code = array (
					"error_code" => 3,
					"error_str" => "非法操作" 
			);
			echo json_encode ( $error_code );
			exit ();
		}
		
		$User = D ( "User" );
		$email = $this->_post ( "email" );
		$password = md5 ( $this->_post ( "password" ) );
		$name = htmlspecialchars ( $this->_post ( "name" ) );
		// 长度
		
		$res = $User->where ( array (
				"username" => $email 
		) )->field ( 'id' )->find ();
		if ($res) {
			$error_code = array (
					"error_code" => 1,
					"error_str" => "账号已存在" 
			);
			echo json_encode ( $error_code );
			exit ();
		}
		
		$res2 = $User->where ( array (
				"name" => $name 
		) )->field ( 'id' )->find ();
		
		if ($res2) {
			$error_code = array (
					"error_code" => 2,
					"error_str" => "昵称重复" 
			);
			echo json_encode ( $error_code );
			exit ();
		}
		
		$ip = ip2long ( $_SERVER ["REMOTE_ADDR"] );
		$time = time ();
		
		$r = $User->data ( array (
				"username" => $email,
				"password" => $password,
				"name" => $name,
				"regip" => $ip,
				"regtime" => $time 
		) )->add ();
		
		if ($r) {
			// 登录操作初始化session、
			$_SESSION ['Auth'] ['name'] = $name;
			$_SESSION ['Auth'] ['id'] = $r;
			$_SESSION ['Auth'] ['username'] = $username;
			
			$error_code = array (
					"success" => "1" 
			);
		} else {
			$error_code = array (
					"error_code" => 4,
					"error_str" => "数据添加失败" 
			);
		}
		
		echo json_encode ( $error_code );
	}
	
	/*
	 * @brief 第三方登录 注册新用户 V1 注册版
	 */
	public function accountBindNew() {
		$error_code = NULL;
		
		if ($_POST ["code"] != $_SESSION ["form"] ["code"]) {
			$error_code = array (
					"error_code" => 1,
					"error_str" => "非法操作" 
			);
			echo json_encode ( $error_code );
			exit ();
		}
		
		// 检测帐号
		$username = $this->_post ( "username" );
		$User = D ( "User" );
		$result = $User->where ( array (
				"username" => $username 
		) )->field ( "id" )->find ();
		
		if ($result) {
			$error_code = array (
					"error_code" => 2,
					"error_str" => "邮箱已注册" 
			);
			echo json_encode ( $error_code );
			exit ();
		}
		
		$data ["username"] = $this->_post ( "username" );
		$data ["password"] = md5 ( $this->_post ( "password" ) );
		$data ["name"] = htmlspecialchars ( $this->_post ( "name" ) );
		
		$res2 = $User->where ( array (
				"name" => $data ["name"] 
		) )->field ( 'id' )->find ();
		
		if ($res2) {
			$error_code = array (
					"error_code" => 3,
					"error_str" => "昵称重复" 
			);
			echo json_encode ( $error_code );
			exit ();
		}
		
		$data ['regip'] = ip2long ( $_SERVER ["REMOTE_ADDR"] );
		$data ['regtime'] = time ();
		
		$r = $User->data ( $data )->add ();
		
		if ($r) {
			// 登录操作初始化session、
			$_SESSION ['Auth'] ['name'] = $name;
			$_SESSION ['Auth'] ['id'] = $r;
			$_SESSION ['Auth'] ['username'] = $username;
			$_SESSION ['Auth'] ['login_type'] = $this->_post ( "type" );
			
			$error_code = array (
					"success" => "1" 
			);
		} else {
			$error_code = array (
					"error_code" => 4,
					"error_str" => "数据添加失败" 
			);
		}
		
		echo json_encode ( $error_code );
	}
	
	/*
	 * @brief 第三方登录 已有帐号绑定验证 V1 注册版 错误码 1 非法操作 2 用户不存在 3 密码不正确
	 */
	public function accountBindOld() {
		$error_code = NULL;
		
		if ($this->_post ( 'code' ) != $_SESSION ["form"] ["code"]) {
			$error_code = array (
					"error_code" => 1,
					"error_str" => "非法操作code" 
			);
			echo json_encode ( $error_code );
			exit ();
		}
		
		// 检测帐号
		$username = $this->_post ( "username" );
		$User = D ( "User" );
		$result = $User->where ( array (
				"username" => $username 
		) )->find ();
		
		if (! $result) {
			$error_code = array (
					"error_code" => 2,
					"error_str" => "用户不存在" 
			);
			echo json_encode ( $error_code );
			exit ();
		}
		
		if ($result ["password"] != md5 ( $this->_post ( "password" ) )) {
			$error_code = array (
					"error_code" => 3,
					"error_str" => "密码不正确" 
			);
			echo json_encode ( $error_code );
			exit ();
		}
		
		$array = array (
				"qq",
				"sina",
				"baidu" 
		);
		
		if (! in_array ( $this->_post ( 'type' ), $array )) {
			$error_code = array (
					"error_code" => 5,
					"error_str" => "非法操作" 
			);
		}
		
		if (! empty ( $result [$this->_post ( 'type' )] )) {
			$error_code = array (
					"error_code" => 4,
					"error_str" => "不能绑定已经绑定的帐号" 
			);
		}
		
		if ($error_code != NULL) {
			echo json_encode ( $error_code );
			exit ();
		}
		
		// 绑定第三方登录标识 跳转登录 首页
		$data ["id"] = $result ["id"];
		$data [$this->_post ( "type" )] = $_SESSION ['Auth'] ['Social'] ['userid'];
		$res = $User->where ( array (
				"id" => $data ["id"] 
		) )->save ( $data );
		
		if ($res) {
			$error_code = array (
					"success" => 1 
			);
			echo json_encode ( $error_code );
			
			// 登录操作初始化session、
			$_SESSION ['Auth'] ['name'] = $result ['name'];
			$_SESSION ['Auth'] ['id'] = $result ['id'];
			$_SESSION ['Auth'] ['username'] = $result ['username'];
			$_SESSION ['Auth'] ['login_type'] = $this->_post ( "type" );
			
			exit ();
		} else {
			$error_code = array (
					"error_code" => 6,
					"error_str" => "数据库更新失败" 
			);
			echo json_encode ( $error_code );
			exit ();
		}
	}
	
	/*
	 * @brief 退出
	 */
	public function logout() {
		cookie ( null );
		session ( null );
		redirect ( 'http://' . $_SERVER ['SERVER_NAME'] );
	}
}
