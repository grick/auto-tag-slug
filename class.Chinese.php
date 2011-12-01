<?php

/**
 * 中文编码集合类库
 *
 * 目前该类库可以实现，简体中文 <-> 繁体中文编码互换，简体中文、繁体中文 -> 拼音单向转换，
 * 简体中文、繁体中文 <-> UTF8 编码转换，简体中文、繁体中文 -> Unicode单向转换
 *
 * @作者         Hessian(solarischan@21cn.com)
 * @版本         1.5
 * @版权所有     Hessian / NETiS
 * @使用授权     GPL（不能应用于任何商业用途，无须经过作者同意即可修改代码，但修改后的代码必须按照GPL协议发布）
 * @特别鸣谢     unknow（繁简转换代码片断）
 * @起始         2003-04-01
 * @最后修改     2003-06-06
 * @访问         公开
 *
 * 更新记录
 * 
 * ver 1.7 2005-07-26
 * 修改了while循环导致的bug。此bug当字符串最后一个字符为"0"的时候将处理错误。
 * 受影响方法: CHStoUTF8() , CHStoUNICODE()
 * (by Zeal Li ,http://www.zeali.net/)
 * 
 * ver 1.6 2005-05-16
 * 构造函数增加了一个参数以便用户可以方便的在使用的时候设置配置文件路径
 * (by Zeal Li ,http://www.zeali.net/)
 *
 * ver 1.5 2003-06-06
 * 增加 UTF8 转换到 GB2312、BIG5的功能。
 *
 * ver 1.4 2003-04-07
 * 增加 当转换HTML时设定为true，即可改变charset的值。
 *
 * ver 1.3 2003-04-02
 * 增加 繁体中文转换至拼音的功能。
 *
 * ver 1.2 2003-04-02
 * 合并 简体、繁体中文转换至UTF8的函数。
 * 修改 简体中文转换至拼音的函数，返回值更改为字符串，每一个汉字的拼音用空格分开
 * 增加 简体中文转换为 UNICODE 的功能。
 * 增加 繁体中文转换为 UNICODE 的功能。
 *
 * ver 1.1 2003-04-02
 * 增加 OpenFile() 函数，支持打开本地文件和远程文件。
 * 增加 简体中文转换为 UTF8 的功能。
 * 增加 繁体中文转换为 UTF8 的功能。
 *
 * ver 1.0 2003-04-01
 * 一个集合了中文简体，中文繁体对应各种编码互换的类库已经初步完成。
 */
class Chinese
{

	/**
	 * 存放简体中文与拼音对照表
	 *
	 * @变量类型  数组
	 * @起始      1.0
	 * @最后修改  1.0
	 * @访问      内部
	 */
	var $pinyin_table = array();

	
	/**
	 * 存放 GB <-> UNICODE 对照表的内容
	 * @变量类型  
	 * @起始      1.1
	 * @最后修改  1.2
	 * @访问      内部
	 */
	var $unicode_table = array();

	/**
	 * 访问中文繁简互换表的文件指针
	 *
	 * @变量类型  对象
	 * @起始      1.0
	 * @最后修改  1.0
	 * @访问      内部
	 */
	var $ctf;

	/**
	 * 等待转换的字符串
	 * @变量类型
	 * @起始      1.0
	 * @最后修改  1.0
	 * @访问      内部
	 */
	var $SourceText = "";

	/**
	 * Chinese 的运行配置
	 *
	 * @变量类型  数组
	 * @起始      1.0
	 * @最后修改  1.2
	 * @访问      公开
	 */
	var $config  =  array(
		'codetable_dir'         => "./config/",           //  存放各种语言互换表的目录
		'SourceLang'            => '',                    //  字符的原编码
		'TargetLang'            => '',                    //  转换后的编码
		'GBtoBIG5_table'        => 'gb-big5.table',       //  简体中文转换为繁体中文的对照表
		'BIG5toGB_table'        => 'big5-gb.table',       //  繁体中文转换为简体中文的对照表
		'GBtoPinYin_table'      => 'gb-pinyin.table',     //  简体中文转换为拼音的对照表
		'GBtoUnicode_table'     => 'gb-unicode.table',    //  简体中文转换为UNICODE的对照表
		'BIG5toUnicode_table'   => 'big5-unicode.table'   //  繁体中文转换为UNICODE的对照表
	);

	/**
	 * Chinese 的悉构函数
	 *
	 * 详细说明
	 * @形参      字符串 $SourceLang 为需要转换的字符串的原编码
	 *            字符串 $TargetLang 为转换的目标编码
	 *            字符串 $SourceText 为等待转换的字符串
	 *            字符串 $CodetableDir 编码对应表的目录
	 *
	 * @起始      1.0
	 * @最后修改  1.2
	 * @访问      公开
	 * @返回值    无
	 * @throws
	 */
	function Chinese( $SourceLang , $TargetLang , $SourceString='', $CodetableDir='')
	{
		if ($SourceLang != '') {
		    $this->config['SourceLang'] = $SourceLang;
		}

		if ($TargetLang != '') {
		    $this->config['TargetLang'] = $TargetLang;
		}

		if ($SourceString != '') {
		    $this->SourceText = $SourceString;
		}

		// codes Added by Zeal Li on ver 1.6 begin.
		if ($CodetableDir != '') {
			if(! is_dir($CodetableDir)){
				echo "configuration directory [".$CodetableDir."] not exists!";
				exit;
			}
		    $this->config['codetable_dir'] = $CodetableDir;
		}
		// codes Added by Zeal Li on ver 1.6 end.

		$this->OpenTable();
	} // 结束 Chinese 的悉构函数


	/**
	 * 将 16 进制转换为 2 进制字符
	 *
	 * 详细说明
	 * @形参      $hexdata 为16进制的编码
	 * @起始      1.5
	 * @最后修改  1.5
	 * @访问      内部
	 * @返回      字符串
	 * @throws    
	 */
	function _hex2bin( $hexdata )
	{
		for ( $i=0; $i<strlen($hexdata); $i+=2 )
			$bindata.=chr(hexdec(substr($hexdata,$i,2)));

		return $bindata;
	}


	/**
	 * 打开对照表
	 *
	 * 详细说明
	 * @形参      
	 * @起始      1.3
	 * @最后修改  1.3
	 * @访问      内部
	 * @返回      无
	 * @throws    
	 */
	function OpenTable()
	{
	    
		// 假如原编码为简体中文的话
		if ($this->config['SourceLang']=="GB2312") {

			// 假如转换目标编码为繁体中文的话
			if ($this->config['TargetLang'] == "BIG5") {
				$this->ctf = fopen($this->config['codetable_dir'].$this->config['GBtoBIG5_table'], "r");
				if (is_null($this->ctf)) {
					echo "打开打开转换表文件失败！";
					exit;
				}
			}

			// 假如转换目标编码为拼音的话
			if ($this->config['TargetLang'] == "PinYin") {
				$tmp = @file($this->config['codetable_dir'].$this->config['GBtoPinYin_table']);
				if (!$tmp) {
					echo "打开打开转换表文件失败！";
					exit;
				}
				//
				$i = 0;
				for ($i=0; $i<count($tmp); $i++) {
					$tmp1 = explode("	", $tmp[$i]);
					$this->pinyin_table[$i]=array($tmp1[0],$tmp1[1]);
				}
			}

			// 假如转换目标编码为 UTF8 的话
			if ($this->config['TargetLang'] == "UTF8") {
				$tmp = @file($this->config['codetable_dir'].$this->config['GBtoUnicode_table']);
				if (!$tmp) {
					echo "打开打开转换表文件失败！";
					exit;
				}
				$this->unicode_table = array();
				while(list($key,$value)=each($tmp))
					$this->unicode_table[hexdec(substr($value,0,6))]=substr($value,7,6);
			}

			// 假如转换目标编码为 UNICODE 的话
			if ($this->config['TargetLang'] == "UNICODE") {
				$tmp = @file($this->config['codetable_dir'].$this->config['GBtoUnicode_table']);
				if (!$tmp) {
					echo "打开打开转换表文件失败！";
					exit;
				}
				$this->unicode_table = array();
				while(list($key,$value)=each($tmp))
					$this->unicode_table[hexdec(substr($value,0,6))]=substr($value,9,4);
			}
		}

		// 假如原编码为繁体中文的话
		if ($this->config['SourceLang']=="BIG5") {
			// 假如转换目标编码为简体中文的话
			if ($this->config['TargetLang'] == "GB2312") {
				$this->ctf = fopen($this->config['codetable_dir'].$this->config['BIG5toGB_table'], "r");
				if (is_null($this->ctf)) {
					echo "打开打开转换表文件失败！";
					exit;
				}
			}
			// 假如转换目标编码为 UTF8 的话
			if ($this->config['TargetLang'] == "UTF8") {
				$tmp = @file($this->config['codetable_dir'].$this->config['BIG5toUnicode_table']);
				if (!$tmp) {
					echo "打开打开转换表文件失败！";
					exit;
				}
				$this->unicode_table = array();
				while(list($key,$value)=each($tmp))
					$this->unicode_table[hexdec(substr($value,0,6))]=substr($value,7,6);
			}

			// 假如转换目标编码为 UNICODE 的话
			if ($this->config['TargetLang'] == "UNICODE") {
				$tmp = @file($this->config['codetable_dir'].$this->config['BIG5toUnicode_table']);
				if (!$tmp) {
					echo "打开打开转换表文件失败！";
					exit;
				}
				$this->unicode_table = array();
				while(list($key,$value)=each($tmp))
					$this->unicode_table[hexdec(substr($value,0,6))]=substr($value,9,4);
			}

			// 假如转换目标编码为拼音的话
			if ($this->config['TargetLang'] == "PinYin") {
				$tmp = @file($this->config['codetable_dir'].$this->config['GBtoPinYin_table']);
				if (!$tmp) {
					echo "打开打开转换表文件失败！";
					exit;
				}
				//
				$i = 0;
				for ($i=0; $i<count($tmp); $i++) {
					$tmp1 = explode("	", $tmp[$i]);
					$this->pinyin_table[$i]=array($tmp1[0],$tmp1[1]);
				}
			}
		}

		// 假如原编码为 UTF8 的话
		if ($this->config['SourceLang']=="UTF8") {

			// 假如转换目标编码为 GB2312 的话
			if ($this->config['TargetLang'] == "GB2312") {
				$tmp = @file($this->config['codetable_dir'].$this->config['GBtoUnicode_table']);
				if (!$tmp) {
					echo "打开打开转换表文件失败！";
					exit;
				}
				$this->unicode_table = array();
				while(list($key,$value)=each($tmp))
					$this->unicode_table[hexdec(substr($value,7,6))]=substr($value,0,6);
			}

			// 假如转换目标编码为 BIG5 的话
			if ($this->config['TargetLang'] == "BIG5") {
				$tmp = @file($this->config['codetable_dir'].$this->config['BIG5toUnicode_table']);
				if (!$tmp) {
					echo "打开打开转换表文件失败！";
					exit;
				}
				$this->unicode_table = array();
				while(list($key,$value)=each($tmp))
					$this->unicode_table[hexdec(substr($value,7,6))]=substr($value,0,6);
			}
		}

	} // 结束 OpenTable 函数

	/**
	 * 打开本地或者远程的文件
	 *
	 * 详细说明
	 * @形参      字符串 $position 为需要打开的文件名称，支持带路径或URL
	 *            布尔值 $isHTML 为待转换的文件是否为html文件
	 * @起始      1.1
	 * @最后修改  1.1
	 * @访问      公开
	 * @返回      无
	 * @throws    
	 */
	function OpenFile( $position , $isHTML=false )
	{
	    $tempcontent = @file($position);

		if (!$tempcontent) {
		    echo "打开文件失败！";
			exit;
		}

		$this->SourceText = implode("",$tempcontent);

		if ($isHTML) {
			$this->SourceText = eregi_replace( "charset=".$this->config['SourceLang'] , "charset=".$this->config['TargetLang'] , $this->SourceText);

			$this->SourceText = eregi_replace("\n", "", $this->SourceText);

			$this->SourceText = eregi_replace("\r", "", $this->SourceText);
		}
	} // 结束 OpenFile 函数

	/**
	 * 打开本地或者远程的文件
	 *
	 * 详细说明
	 * @形参      字符串 $position 为需要打开的文件名称，支持带路径或URL
	 * @起始      1.1
	 * @最后修改  1.1
	 * @访问      公开
	 * @返回      无
	 * @throws    
	 */
	function SiteOpen( $position )
	{
	    $tempcontent = @file($position);

		if (!$tempcontent) {
		    echo "打开文件失败！";
			exit;
		}

		// 将数组的所有内容转换为字符串
		$this->SourceText = implode("",$tempcontent);

		$this->SourceText = eregi_replace( "charset=".$this->config['SourceLang'] , "charset=".$this->config['TargetLang'] , $this->SourceText);


//		ereg(href="css/dir.css"
	} // 结束 OpenFile 函数

	/**
	 * 设置变量的值
	 *
	 * 详细说明
	 * @形参
	 * @起始      1.0
	 * @最后修改  1.0
	 * @访问      公开
	 * @返回值    无
	 * @throws
	 */
	function setvar( $parameter , $value )
	{
		if(!trim($parameter))
			return $parameter;

		$this->config[$parameter] = $value;

	} // 结束 setvar 函数

	/**
	 * 将简体、繁体中文的 UNICODE 编码转换为 UTF8 字符
	 *
	 * 详细说明
	 * @形参      数字 $c 简体中文汉字的UNICODE编码的10进制
	 * @起始      1.1
	 * @最后修改  1.2
	 * @访问      内部
	 * @返回      字符串
	 * @throws    
	 */
	function CHSUtoUTF8($c)
	{
		$str="";

		if ($c < 0x80) {
			$str.=$c;
		}

		else if ($c < 0x800) {
			$str.=(0xC0 | $c>>6);
			$str.=(0x80 | $c & 0x3F);
		}

		else if ($c < 0x10000) {
			$str.=(0xE0 | $c>>12);
			$str.=(0x80 | $c>>6 & 0x3F);
			$str.=(0x80 | $c & 0x3F);
		}

		else if ($c < 0x200000) {
			$str.=(0xF0 | $c>>18);
			$str.=(0x80 | $c>>12 & 0x3F);
			$str.=(0x80 | $c>>6 & 0x3F);
			$str.=(0x80 | $c & 0x3F);
		}

		return $str;
	} // 结束 CHSUtoUTF8 函数
	
	/**
	 * 简体、繁体中文 <-> UTF8 互相转换的函数
	 *
	 * 详细说明
	 * @形参      
	 * @起始      1.1
	 * @最后修改  1.5
	 * @访问      内部
	 * @返回      字符串
	 * @throws    
	 */
	function CHStoUTF8(){

		if ($this->config["SourceLang"]=="BIG5" || $this->config["SourceLang"]=="GB2312") {
			$ret="";

			while($this->SourceText != ""){

				if(ord(substr($this->SourceText,0,1))>127){

					if ($this->config["SourceLang"]=="BIG5") {
						$utf8=$this->CHSUtoUTF8(hexdec($this->unicode_table[hexdec(bin2hex(substr($this->SourceText,0,2)))]));
					}
					if ($this->config["SourceLang"]=="GB2312") {
						$utf8=$this->CHSUtoUTF8(hexdec($this->unicode_table[hexdec(bin2hex(substr($this->SourceText,0,2)))-0x8080]));
					}
					for($i=0;$i<strlen($utf8);$i+=3)
						$ret.=chr(substr($utf8,$i,3));

					$this->SourceText=substr($this->SourceText,2,strlen($this->SourceText));
				}
				
				else{
					$ret.=substr($this->SourceText,0,1);
					$this->SourceText=substr($this->SourceText,1,strlen($this->SourceText));
				}
			}
			$this->unicode_table = array();
			$this->SourceText = "";
			return $ret;
		}

		if ($this->config["SourceLang"]=="UTF8") {
			$out = "";
			$len = strlen($this->SourceText);
			$i = 0;
			while($i < $len) {
				$c = ord( substr( $this->SourceText, $i++, 1 ) );
				switch($c >> 4)
				{ 
					case 0: case 1: case 2: case 3: case 4: case 5: case 6: case 7:
						// 0xxxxxxx
						$out .= substr( $this->SourceText, $i-1, 1 );
					break;
					case 12: case 13:
						// 110x xxxx   10xx xxxx
						$char2 = ord( substr( $this->SourceText, $i++, 1 ) );
						$char3 = $this->unicode_table[(($c & 0x1F) << 6) | ($char2 & 0x3F)];

						if ($this->config["TargetLang"]=="GB2312")
							$out .= $this->_hex2bin( dechex(  $char3 + 0x8080 ) );

						if ($this->config["TargetLang"]=="BIG5")
							$out .= $this->_hex2bin( $char3 );
					break;
					case 14:
						// 1110 xxxx  10xx xxxx  10xx xxxx
						$char2 = ord( substr( $this->SourceText, $i++, 1 ) );
						$char3 = ord( substr( $this->SourceText, $i++, 1 ) );
						$char4 = $this->unicode_table[(($c & 0x0F) << 12) | (($char2 & 0x3F) << 6) | (($char3 & 0x3F) << 0)];

						if ($this->config["TargetLang"]=="GB2312")
							$out .= $this->_hex2bin( dechex ( $char4 + 0x8080 ) );

						if ($this->config["TargetLang"]=="BIG5")
							$out .= $this->_hex2bin( $char4 );
					break;
				}
			}

			// 返回结果
			return $out;
		}
	} // 结束 CHStoUTF8 函数

	/**
	 * 简体、繁体中文转换为 UNICODE编码
	 *
	 * 详细说明
	 * @形参      
	 * @起始      1.2
	 * @最后修改  1.2
	 * @访问      内部
	 * @返回      字符串
	 * @throws    
	 */
	function CHStoUNICODE()
	{

		$utf="";

		while($this->SourceText != "")
		{
			if (ord(substr($this->SourceText,0,1))>127)
			{

				if ($this->config["SourceLang"]=="GB2312")
					$utf.="&#x".$this->unicode_table[hexdec(bin2hex(substr($this->SourceText,0,2)))-0x8080].";";

				if ($this->config["SourceLang"]=="BIG5")
					$utf.="&#x".$this->unicode_table[hexdec(bin2hex(substr($this->SourceText,0,2)))].";";

				$this->SourceText=substr($this->SourceText,2,strlen($this->SourceText));
			}
			else
			{
				$utf.=substr($this->SourceText,0,1);
				$this->SourceText=substr($this->SourceText,1,strlen($this->SourceText));
			}
		}
		return $utf;
	} // 结束 CHStoUNICODE 函数

	/**
	 * 简体中文 <-> 繁体中文 互相转换的函数
	 *
	 * 详细说明
	 * @起始      1.0
	 * @访问      内部
	 * @返回值    经过编码的utf8字符
	 * @throws
	 */
	function GB2312toBIG5()
	{
		// 获取等待转换的字符串的总长度
		$max=strlen($this->SourceText)-1;

		for($i=0;$i<$max;$i++){

			$h=ord($this->SourceText[$i]);

			if($h>=160){

				$l=ord($this->SourceText[$i+1]);

				if($h==161 && $l==64){
					$gb="  ";
				}
				else{
					fseek($this->ctf,($h-160)*510+($l-1)*2);
					$gb=fread($this->ctf,2);
				}

				$this->SourceText[$i]=$gb[0];
				$this->SourceText[$i+1]=$gb[1];
				$i++;
			}
		}
		fclose($this->ctf);

		// 将转换后的结果赋予 $result;
		$result = $this->SourceText;

		// 清空 $thisSourceText
		$this->SourceText = "";

		// 返回转换结果
		return $result;
	} // 结束 GB2312toBIG5 函数

	/**
	 * 根据所得到的编码搜寻拼音
	 *
	 * 详细说明
	 * @起始      1.0
	 * @最后修改  1.0
	 * @访问      内部
	 * @返回值    字符串
	 * @throws
	 */
	function PinYinSearch($num){

		if($num>0&&$num<160){
			return chr($num);
		}

		elseif($num<-20319||$num>-10247){
			return "";
		}

		else{

			for($i=count($this->pinyin_table)-1;$i>=0;$i--){
				if($this->pinyin_table[$i][1]<=$num)
					break;
			}

			return $this->pinyin_table[$i][0];
		}
	} // 结束 PinYinSearch 函数

	/**
	 * 简体、繁体中文 -> 拼音 转换
	 *
	 * 详细说明
	 * @起始      1.0
	 * @最后修改  1.3
	 * @访问      内部
	 * @返回值    字符串，每个拼音用空格分开
	 * @throws
	 */
	function CHStoPinYin(){
		if ( $this->config['SourceLang']=="BIG5" ) {
			$this->ctf = fopen($this->config['codetable_dir'].$this->config['BIG5toGB_table'], "r");
			if (is_null($this->ctf)) {
				echo "打开打开转换表文件失败！";
				exit;
			}

			$this->SourceText = $this->GB2312toBIG5();
			$this->config['TargetLang'] = "PinYin";
		}

		$ret = array();
		$ri = 0;
		for($i=0;$i<strlen($this->SourceText);$i++){

			$p=ord(substr($this->SourceText,$i,1));

			if($p>160){
				$q=ord(substr($this->SourceText,++$i,1));
				$p=$p*256+$q-65536;
			}

			$ret[$ri]=$this->PinYinSearch($p);
			$ri = $ri + 1;
		}

		// 清空 $this->SourceText
		$this->SourceText = "";

		$this->pinyin_table = array();

		// 返回转换后的结果
		return implode(" ", $ret);
	} // 结束 CHStoPinYin 函数

	/**
	 * 输出转换结果
	 *
	 * 详细说明
	 * @形参
	 * @起始      1.0
	 * @最后修改  1.2
	 * @访问      公开
	 * @返回      字符换
	 * @throws
	 */
	function ConvertIT()
	{
		// 判断是否为中文繁、简转换
		if ( ($this->config['SourceLang']=="GB2312" || $this->config['SourceLang']=="BIG5") && ($this->config['TargetLang']=="GB2312" || $this->config['TargetLang']=="BIG5") ) {
			return $this->GB2312toBIG5();
		}

		// 判断是否为简体中文与拼音转换
		if ( ($this->config['SourceLang']=="GB2312" || $this->config['SourceLang']=="BIG5") && $this->config['TargetLang']=="PinYin" ) {
			return $this->CHStoPinYin();
		}

		// 判断是否为简体、繁体中文与UTF8转换
		if ( ($this->config['SourceLang']=="GB2312" || $this->config['SourceLang']=="BIG5" || $this->config['SourceLang']=="UTF8") && ($this->config['TargetLang']=="UTF8" || $this->config['TargetLang']=="GB2312" || $this->config['TargetLang']=="BIG5") ) {
			return $this->CHStoUTF8();
		}

		// 判断是否为简体、繁体中文与UNICODE转换
		if ( ($this->config['SourceLang']=="GB2312" || $this->config['SourceLang']=="BIG5") && $this->config['TargetLang']=="UNICODE" ) {
			return $this->CHStoUNICODE();
		}

	} // 结束 ConvertIT 函数

} // 结束类库

/**
*/

?>
