<?php
namespace App\Action;
use Mockery\Exception;
use Think\Action;
class CommonAction extends EmptyAction {
	
    const CACHE_TIME = 86400; //1天
	const CACHE_CATEGORY_TIME = 604800;// 7天
	public $_userId = 0;
	public function setId($id){ $this->_userId = $id;}
	public function getId(){ return $this->_userId; }
	
	public function _initialize() {
		// 分配网站名称
		//$_WEBSITE ["url"] = "http://www.vipmhxy.com";
		//$_WEBSITE ["url_short"] = "www.vipmhxy.com";
		$_WEBSITE ["url"] = NET_NAME;
		$_WEBSITE ["url_short"] = $_SERVER['HTTP_HOST'];
		$_WEBSITE ["name"] = "张成的官方网站";
		$_WEBSITE['CONTROLLER_NAME'] = CONTROLLER_NAME;
		$_WEBSITE['ACTION_NAME'] =ACTION_NAME;
		$this->assign ( "WEBSITE", $_WEBSITE );
		// form code
		if (! isset ( $_SESSION ["form"] ['code'] )) {
			$_SESSION ["form"] ['code'] = md5 ( time () );
		}
		
		// 分类
		//
		if (! isset ( $_SESSION ["website"] ["category"] )) {
			//如果存在
			$tmpArr= $this->getCacheArray("category", "category");
			if($tmpArr != false){
				$_SESSION["website"]["category"] = $tmpArr;
				
			}else{
				$c = M ( 'Category' );
				$r = $c->where ( "pid=0" )->select ();
				$flag = $this->saveCacheArray("category", "category", $r );
				if($flag){
					$_SESSION["website"]["category"] = $r ;
					
				}else{
					
				}
			}

		}
		$this->assign ( "WebsiteCategory", $_SESSION ["website"] ["category"] );
		
		// 标签 关键字
		if (! isset ( $_SESSION ["website"] ["tag"] )) {
			$c = M ( 'Keyword' );
			$r = $c->select ();
			$_SESSION ["website"] ["tag"] = $r;
		}
		// dump($_SESSION["website"]["tag"]);
		$this->assign ( "WebsiteTag", $_SESSION ["website"] ["tag"] );
		
		
		//查找热门资料
		if ( !isset($_SESSION["website"]["cache"]["article_rank"]))
		{
			//如果存在
			$tmpArr= $this->getCacheArray("common", "article_rank");
			if($tmpArr != false){
				$_SESSION["website"]["cache"]["article_rank"] = $tmpArr;
			}else{
				//不存在
				$articleModle = M("Article");
				$res = $articleModle->where("isdel = 0")->field("id,title")->order("visit desc,id desc")->limit(18)->select();
				foreach ($res as $k=>$v){
					$res[$k]["realid"] = $v["id"];
					$res[$k]["id"] = $this->encodeId($v["id"]);
					switch($k){
						case 0:
						case 1:
						case 2:
							$res[$k]["class"] = $k+1;
							$res[$k]["index"] = $k+1;
							break;
						default:
							$res[$k]["class"] = "More";
							$res[$k]["index"] = $k+1;
							break;
					}
				}
				$flag = $this->saveCacheArray("common", "article_rank", $res);
				if($flag){
					$_SESSION["website"]["cache"]["article_rank"] = $res;
				}else{

				}
			}
		}
		$this->assign ( "WebsiteCacheArticle", $_SESSION["website"]["cache"]["article_rank"]);

		//查找热门文档
		if ( !isset($_SESSION["website"]["cache"]["document_rank"]))
		{
			//如果存在
			$tmpArr= $this->getCacheArray("common", "document_rank");
			if($tmpArr != false){
				$_SESSION["website"]["cache"]["document_rank"] = $tmpArr;
			}else{
				//不存在
				$articleModle = M("Document");
				$res = $articleModle->where("isdel = 0")->field("id,title,url")->order("visit desc,id desc")->limit(18)->select();
				foreach ($res as $k=>$v){
						
					$res[$k]["id"] = $this->encodeId($v["id"]);
					switch($k){
						case 0:
						case 1:
						case 2:
							$res[$k]["class"] = $k+1;
							$res[$k]["index"] = $k+1;
							break;
						default:
							$res[$k]["class"] = "More";
							$res[$k]["index"] = $k+1;
							break;
					}
				}
				$flag = $this->saveCacheArray("common", "document_rank", $res);
				if($flag){
					$_SESSION["website"]["cache"]["document_rank"] = $res;
				}else{
		
				}
			}
		}
		
		
		$this->assign ( "WebsiteCacheDocument", $_SESSION["website"]["cache"]["document_rank"]);
		
		//分配project
		//assign project menu
		if(!isset($_SESSION ["website"]['menu_project'])) {
			$allProject = M('project')->select();
			$article = M('article');
			foreach ($allProject as &$v2) {
				$summary = M('project_summary')->where("project_id={$v2['project_id']}")->select();

				foreach ($summary as &$v) {
					if (!$v['article_id_data']) {
						$v['sub_data'] = [];
						continue;
					}

					$tmp = explode(',', $v['article_id_data']);

					$subData = $article->where('id in (' . $v['article_id_data'] . ')')->field('id,title')->select();
					$v['sub_data'] = $subData;
				}

				$v2['summary'] = $summary;

			}

			$_SESSION['website']['menu_project'] = $allProject;
		}
		//dump($allProject);
		$this->assign('MENU_PROJECT',$_SESSION['website']['menu_project']);




		//分配 用户id
		if(!empty($_SESSION ["Auth"] ["id"])){
			$this->_userId = $_SESSION ["Auth"] ["id"];
		}


		//分配nginx源码目录变量
        if(!isset($_SESSION ["website"]['nginx_source_code'])) {
            //如果存在
            $tmpArr= $this->getCacheArray("common", "nginx_source_code");
            if($tmpArr != false){
                $_SESSION["website"]["cache"]["nginx_source_code"] = $tmpArr;
            }else {
                //实时生成数据到数组 保存入文件
                $res = [];

                $prefix = '/project/';
                $dir = 'nginx';

                $this->fetchDirSetData($res,$prefix.$dir,$dir);

                $flag = $this->saveCacheArray("common", "document_rank", $res);
                if($flag){
                    $_SESSION["website"]["cache"]["nginx_source_code"] = $res;
                }
            }
		}


		$this->assign('WebsiteCacheNginx',$_SESSION["website"]["cache"]["nginx_source_code"]);

		//分配一些变量

		$this->assignSomeDatas();

        //添加ip记录
        add_ip_record();
	}
	
	// 跳转函数
	public function formSuccess($title, $url, $sec = 3) {
		$url = trim($url,'/');
		$url = '/'.$url;
		$string = "/title/" . $title . "/url/" . base_encode( "http://" . $_SERVER ["HTTP_HOST"] . $url ) . "/sec/" . $sec;
		header ( "location:http://" . $_SERVER ["HTTP_HOST"] . '/form/success' . $string );
		exit ();
	}
	
	
	public function formError($title, $url, $sec = 3) {
		$url = trim($url,'/');
		$url = '/'.$url;
		$string = "/title/" . $title . "/url/" . base_encode ( "http://" . $_SERVER ["HTTP_HOST"] . $url ) . "/sec/" . $sec;
		header ( "location:http://" . $_SERVER ["HTTP_HOST"] . '/form/error/' . $string );
		exit ();
	}
	public function formErrorReferer($title, $sec = 3) {
		if (! empty ( $_SERVER ["HTTP_REFERER"] )) {
			$string = "/title/" . $title . "/url/" . base_encode ( $_SERVER ["HTTP_REFERER"] ) . "/sec/" . $sec;
		} else {
			$string = "/title/" . $title . "/url/" . base_encode ( "http://" . $_SERVER ["HTTP_HOST"] ) . "/sec/" . $sec;
		}
		header ( "location:http://" . $_SERVER ["HTTP_HOST"] . '/form/error/' . $string );
		exit ();
	}
	public function formLoginCheck($sec = 3) {
		if (empty ( $_SESSION ["Auth"] ["id"] )) {
			$string = "/title/请登录后操作/url/" . base_encode ( "http://" . $_SERVER ["HTTP_HOST"] ) . "/sec/" . $sec;
			header ( "location:http://" . $_SERVER ["HTTP_HOST"] . '/form/error/' . $string );
			exit ();
		}
	}
	public function fieldLengthCheck(&$var, $desc, $max = 100, $min = 1) {
		$length = getLength ( $var );
		if ($length > $max) {
			$this->formErrorReferer ( $desc . "长度不能超过" . $max );
		}
		if ($length < $min) {
			$this->formErrorReferer ( $desc."不能为空" );
		}
	}
	
	/**
	 * 检查数据长度 供json环境使用
	 * @param array $var
	 * @param number $max
	 * @param number $min
	 * @return boolean
	 */
	public function jsonLengthCheck(&$var, $max = 100, $min = 1)
	{
		$length = getLength ( $var );
		if ($length > $max) {
			return true;
		}
		if ($length < $min) {
			return true;
		}
		return false;
	}
	
	/**
	 * 检查是否登陆校验 
	 * 		json返回值
	 * 
	 * @return json
	 */
	public function jsonLoginCheck()
	{
		if (empty ( $_SESSION ["Auth"] ["id"] )) {
			jsonP("未登陆", 1);
		}
	}
	
	/*
	 * 加密id
	 */
	public function encodeId($id) {
		$mid = md5 ( $id );
		$str = substr ( $mid, 0, 16 );
		$str .= $id;
		$str .= substr ( $mid, 16, 16 );
		return $str;
	}
	/*
	 * 解密id
	 */
	public function decodeId($id) {
		$len = strlen ( $id );
		$str = substr ( $id, 16, $len - 32 );
		return $str;
	}
	
	/*
	 * 从文件取得cache数组
	 * */
	public function getCacheArray($path,$name)
	{
		$file = 'cache/';
		$target = $file.$path.'/'.md5($name).'.php';
		$fpath = $file.$path;
		if(!is_dir($fpath)){
			if(!mkdir($fpath,0777,true)){
				return false;
			}
		}
		
		if(!is_file($target))
		{
			return false;
		}
		$compare_time = 0;
		if($path == 'category')
		{
			$compare_time = self::CACHE_CATEGORY_TIME;
		}else{
			$compare_time = self::CACHE_TIME;
		}
		
		if( filectime($target) < time()-$compare_time)
		{
			@unlink($target);
			return false;
		}
		
		
		$arr = array();
		$arr = require_once $target;
		
		return $arr;
	}
	
	/*
	 * 保存cache数据到文件
	 * */
	public function saveCacheArray($path,$name,$data)
	{
		$file = 'cache/';
		$target = $file.$path.'/'.md5($name).'.php';
		$fpath = $file.$path;
		if(!is_dir($fpath)){
			if(!mkdir($fpath,0777,true)){
				return false;
			}
		}
		
		if(is_file($target)){
			@unlink($target);
		}
		$str = "<?php return ";
		$str .= var_export($data,true);
		$str .= ";";

		if(!is_writable($target))
		{
			chmod($fpath,0777);
		}

		if ( file_put_contents($target,$str) )
		{
			return true;
		}
		else
		{
			return false;
		}
		
		
	}

	/**
	 * 分配一些模板变量
	 */
	protected function assignSomeDatas()
	{
		//dump(CONTROLLER_NAME);
		$this->assign('THIS_CONTROLLER',CONTROLLER_NAME);
		$this->assign('THIS_ACTION',CONTROLLER_NAME.'/'.ACTION_NAME);
	}


	public function actionReturn($status,$msg='',$urlOrdata='')
	{
		$data['status'] = $status;
		$data['content'] = $msg;
		if($urlOrdata != ''){
			if(is_string($urlOrdata)){
				$data['url'] = $urlOrdata;
			}elseif(is_array($urlOrdata)){
				$data['data'] = $urlOrdata;
			}
		}

		$this->ajaxReturn($data);


	}
	
	
	public function formRootLoginCheck($sec = 3) {
		if (empty ( $_SESSION ["Auth"] ["id"] ) || $_SESSION ["Auth"] ["id"] != 1) {
			$string = "/title/请管理员登录后操作/url/" . base_encode ( "http://" . $_SERVER ["HTTP_HOST"] ) . "/sec/" . $sec;
			header ( "location:http://" . $_SERVER ["HTTP_HOST"] . '/form/error/' . $string );
			exit ();
		}
	}


	protected function fetchDirSetData(&$res,$dirName,$name)
    {

        $len = count($res);
        if ( is_dir($dirName) && ($handle = opendir( "$dirName" )) ) {
            $tmp['is_file'] = 0;
            $tmp['name'] = $name;
            $tmp['file'] = '';
            $tmp['next'] = [];

            $res[$len] = $tmp;
            while ( false !== ( $item = readdir( $handle ) ) ) {
                if ( $item != "." && $item != ".." ) {
                    $this->fetchDirSetData( $res[$len]['next'],"$dirName/$item", $item);
                }
            }
            closedir( $handle );
        }else{
            $tmp['is_file'] = 1;
            $tmp['name'] = $name;
            $tmp['file'] = substr($dirName,9);
            $tmp['next'] = [];

            $res[$len] = $tmp;
        }
    }
}
