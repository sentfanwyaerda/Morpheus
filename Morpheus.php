<?php
 class Morpheus {
	var $parsers = array();
	function Morpheus(){}
	
	function morpheus_hook($str){
		//Detect Morpheus hooks
		/*replacement-initiate*/ $str = str_replace('</morpheus>', '¤', $str);
		preg_match_all("#<morpheus([^/>]{0,})?([/][>]|[>]([^¤]{0,})¤)#i", $str, $buffer);
		foreach($buffer[1] as $i=>$j){
			preg_match_all("#([a-z-]+)=[\"]([^\"]+)[\"]#i", $buffer[1][$i], $k);
			$buffer[1][$i] = array();
			foreach($k[0] as $l=>$m){ $buffer[1][$i][$k[1][$l]] = $k[2][$l]; }
		}
		foreach($buffer[2] as $i=>$j){ if($j == '/>'){ unset($buffer[3][$i]); } } unset($buffer[2]);
		//*debug*/ print '<pre>'; print htmlspecialchars(str_replace("¤", '</morpheus>', print_r($buffer, TRUE))); print '</pre>';
		
		//Process Morpheus hooks
		foreach($buffer[0] as $i=>$j){
			if(isset($buffer[1][$i]["ref"]) && substr($buffer[1][$i]["ref"], 0, 1) == '#' && isset($buffer[3][$i])){
				preg_match_all("#(<div id=[\"]".substr($buffer[1][$i]["ref"], 1)."[\"]>)(.*)(</div>)#i", $str, $a);
				//*debug*/ print '<pre>'; print htmlspecialchars(print_r($a, TRUE)); print '</pre>';
				$q = 0;
				$str = str_replace($a[0][$q], $a[1][$q].$a[2][$q].$buffer[3][$i].$a[3][$q], $str);
				/*clear*/ $str = str_replace($buffer[0][$i], NULL, $str); 
			}
		}
		
		//*fix*/ foreach($buffer[0] as $i=>$v){ $str = str_replace($buffer[0][$i], '<font color="red">'.md5($buffer[0][$i]).'</font>', $str); }
		/*replacement-restore*/ $str = str_replace('¤', '</morpheus>', $str);
		return $str;
	}
	
	function notify($code, $vars=array(), $line=NULL){
		if(class_exists("Hades")){ return Hades::notify($code, $vars, $line); }
	}
	function get_root($sub=NULL){
		if(class_exists("Hades")){ return Hades::get_root($sub); }
	}
	
	/* Parser Engine*/
	private function _execute_parsers($str){
		return $str;
	}
	
	/* Basic Template Parser */
	public function basic_parse_str($str, $set=array(), $prefix='{', $postfix='}', $parse=FALSE){
		/**/ $str = self::parse_include_str($str, $set, $prefix, $postfix, $parse);
		foreach($set as $tag=>$value){
			$str = str_replace($prefix.$tag.$postfix, (is_array($value) ? json_encode($value) : (string) $value), $str);
		}
		if(preg_match_all("#".Morpheus::escape_preg_chars($prefix)."([\*]+|[:][^:]+[:]|[\%\#\.]{1})?([a-z][^\?".Morpheus::escape_preg_chars($postfix)."]{0,})\?([^:]+)[:]([^".Morpheus::escape_preg_chars($postfix)."]{0,})".Morpheus::escape_preg_chars($postfix)."#i", $str, $buffer)){
			//*debug*/ print '<!-- '; print_r($buffer); print ' -->';
			if(isset($buffer[0]) && is_array($buffer[0])){foreach($buffer[0] as $i=>$original){
				$str = str_replace($original, 
						self::_basic_parse_encapsule($buffer[1][$i],
							$buffer[(isset($set[$buffer[2][$i]]) && ( is_bool($set[$buffer[2][$i]]) ? $set[$buffer[2][$i]] : (in_array(strtolower($set[$buffer[2][$i]]), array('true','false','yes','no')) ? in_array(strtolower($set[$buffer[2][$i]]), array('true','yes')) : $set[$buffer[2][$i]] != NULL) ) ? 3 : 4)][$i],
							$buffer[2][$i])
						, $str);
			}}
		}
		if(preg_match_all("#".Morpheus::escape_preg_chars($prefix)."([\*]+|[:][^:]+[:]|[\%\#\.]{1})?([a-z][^\|".Morpheus::escape_preg_chars($postfix)."]{0,})[\|]([^".Morpheus::escape_preg_chars($postfix)."]{0,})".Morpheus::escape_preg_chars($postfix)."#i", $str, $buffer)){
			if(isset($buffer[0]) && is_array($buffer[0])){foreach($buffer[0] as $i=>$original){
				$str = str_replace($original,
						self::_basic_parse_encapsule($buffer[1][$i],
							(isset($set[$buffer[2][$i]]) ? $set[$buffer[2][$i]] : $buffer[3][$i]),
							$buffer[2][$i])
						, $str);
			}}
		}
		if($parse !== FALSE && isset($this)){ #parse only within the ${Morpheus} object
			$str = $this->_execute_parsers($str);
		}
		return $str;
	}
	private function _basic_parse_encapsule($trigger, $str, $id=NULL){
		switch(substr($trigger, 0, 1)){
			case '*':
				$anchor = (strlen($trigger) >1 ? '<a name="'.$id.'"></a>' : NULL);
				if(strlen($str) > 0){ $str = '<div'.($id != NULL ? ' id="'.$id.'"' : NULL).'>'.$anchor.$str.'</div>'; }
				break;
			case ':':
				if(preg_match("#^[:]([a-z0-9]+)([\.]([a-z0-9 _-]+))?[:]$#", $trigger, $buffer)){
					$element = $buffer[1];
					$test = (isset($buffer[2]) ? $buffer[2] : FALSE);
					$class = (isset($buffer[3]) ? $buffer[3] : NULL);
					if(strlen($str) > 0){ $str = '<'.$element.(isset($test) && strlen($test)>1 ? ' class="'.$class.'"' : NULL).'>'.$str.'</'.$element.'>'; }
				}
				break;
			case '%':
				if(class_exists('Heracles') && method_exists('Heracles','load_record_flags')){
					$flags = Heracles::load_record_flags(Heracles::get_user_id());
					//*debug*/ print '<!-- '.$id.' :{ '.$str.' } :'.print_r($flags, TRUE).' -->';
					$str = (isset($flags[$id]) ? $flags[$id] : $str);
				}
				break;
			case '.':
				if(class_exists('Hades') && method_exists('Hades','get_status_flags')){
					//implement like % (Heracles)
				}
				break;
			case '#':
				///if(class_exists('undefined')){}
				break;
			default: /*do nothing*/
		}
		/*fix*/ $str = (is_array($str) ? json_encode($str) : (string) $str);
		return $str;
	}
	public function basic_parse($src, $set=array()){
		$prefix='{';
		$postfix='}';
		return Morpheus::basic_parse_template($src, $set, $prefix, $postfix, TRUE);
	}
	public function basic_parse_template($src, $set=array(), $prefix='{', $postfix='}', $parse=FALSE){
		if(file_exists($src)){
			$str = file_get_contents($src);
			Morpheus::notify(array(__METHOD__.'.exists', 200), array("src"=>str_replace(Morpheus::get_root(), NULL, $src)));
			if(strlen($str) <= 0){
				$str = Morpheus::basic_parse_template(Morpheus::get_root("content/text").'000-empty-document.md', array('src'=>$src, "document"=>basename($src)));
				Morpheus::notify(000, array("src"=>str_replace(Morpheus::get_root(), NULL, $src)));
			}
		} else {
			if($src != Morpheus::get_root("content/text").'404-not-found.md'){
				$str = Morpheus::basic_parse_template(Morpheus::get_root("content/text").'404-not-found.md', array('src'=>$src, "document"=>basename($src)));
				Morpheus::notify(404, array("src"=>str_replace(Morpheus::get_root(), NULL, $src)));
			}
			else {
				$str = NULL;
				Morpheus::notify(__METHOD__.'.failed', array("src"=>str_replace(Morpheus::get_root(), NULL, $src)));
			}
		}
		return Morpheus::basic_parse_str($str, $set, $prefix, $postfix, $parse);
	}
	public function escape_preg_chars($str, $qout=array(), $merge=FALSE){
		if($merge !== FALSE){
			$qout = array_merge(array('\\'), (is_array($qout) ? $qout : array($qout)), array('[',']','(',')','{','}','$','+','^','-'));
			#/*debug*/ print_r($qout);
		}
		if(is_array($qout)){
			$i = 0;
			foreach($qout as $k=>$v){
				if($i == $k){
					$str = str_replace($v, '\\'.$v, $str);
				} else{
					$str = str_replace($k, $v, $str);	
				}
				$i++;
			}
		}
		else{ $str = str_replace($qout, '\\'.$qout, $str); }
		return $str;
	}
	
	/*experimental: Morpheus\LaTEX & Morpheus\markdown++ */
	/**/ function parse_include_str($str, $set=array(), $prefix='{', $postfix='}', $parse=FALSE){
		if(preg_match_all("#[\\\\]i(nclude)?".Morpheus::escape_preg_chars($prefix)."([^".Morpheus::escape_preg_chars($postfix)."]+)".Morpheus::escape_preg_chars($postfix)."#i", $str, $buffer)){
			if(isset($buffer[0]) && is_array($buffer[0])){foreach($buffer[0] as $i=>$original){
				$str = str_replace($buffer[0][$i],
						self::_basic_parse_encapsule('**',
							self::basic_parse_template((isset($set['content-root']) ? $set['content-root'] : Morpheus::get_root("content/text")).$buffer[2][$i], $set, $prefix, $postfix, $parse),
							md5($buffer[2][$i])
						)
						, $str);
			}}
		}
		return $str;
	}
 }
 ?>
