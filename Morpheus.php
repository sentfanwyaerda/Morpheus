<?php
class Morpheus {
	private $_parsers = array();
	private $_template;
	
	
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
		/* add alias of the Heracles method */
		return dirname(__FILE__).'/';
	}
	
	/* Parser Engine*/
	private function _execute_parsers($str){
		return $str;
	}
	
	/* Basic Template Parser */
	public function basic_parse_str($str, $flags=array(), $prefix='{', $postfix='}', $parse=FALSE){
		/**/ $str = self::parse_include_str($str, $flags, $prefix, $postfix, $parse);
		foreach($flags as $tag=>$value){
			$str = str_replace($prefix.$tag.$postfix, (is_array($value) ? json_encode($value) : (string) $value), $str);
		}
		if(preg_match_all("#".Morpheus::escape_preg_chars($prefix)."([\*]+|[:][^:]+[:]|[\%\#\.]{1})?([a-z][^\?".Morpheus::escape_preg_chars($postfix)."]{0,})\?([^:]+)[:]([^".Morpheus::escape_preg_chars($postfix)."]{0,})".Morpheus::escape_preg_chars($postfix)."#i", $str, $buffer)){
			//*debug*/ print '<!-- '; print_r($buffer); print ' -->';
			if(isset($buffer[0]) && is_array($buffer[0])){foreach($buffer[0] as $i=>$original){
				$str = str_replace($original, 
						self::_basic_parse_encapsule($buffer[1][$i],
							$buffer[(isset($flags[$buffer[2][$i]]) && ( is_bool($flags[$buffer[2][$i]]) ? $flags[$buffer[2][$i]] : (in_array(strtolower($flags[$buffer[2][$i]]), array('true','false','yes','no')) ? in_array(strtolower($flags[$buffer[2][$i]]), array('true','yes')) : $flags[$buffer[2][$i]] != NULL) ) ? 3 : 4)][$i],
							$buffer[2][$i])
						, $str);
			}}
		}
		if(preg_match_all("#".Morpheus::escape_preg_chars($prefix)."([\*]+|[:][^:]+[:]|[\%\#\.]{1})?([a-z][^\|".Morpheus::escape_preg_chars($postfix)."]{0,})[\|]([^".Morpheus::escape_preg_chars($postfix)."]{0,})".Morpheus::escape_preg_chars($postfix)."#i", $str, $buffer)){
			if(isset($buffer[0]) && is_array($buffer[0])){foreach($buffer[0] as $i=>$original){
				$str = str_replace($original,
						self::_basic_parse_encapsule($buffer[1][$i],
							(isset($flags[$buffer[2][$i]]) ? $flags[$buffer[2][$i]] : $buffer[3][$i]),
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
	public function basic_parse($src, $flags=array()){
		$prefix='{';
		$postfix='}';
		return Morpheus::basic_parse_template($src, $flags, $prefix, $postfix, TRUE);
	}
	public function basic_parse_template($src, $flags=array(), $prefix='{', $postfix='}', $parse=FALSE){
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
		return Morpheus::basic_parse_str($str, $flags, $prefix, $postfix, $parse);
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
	/*public*/ function parse_include_str($str, $flags=array(), $prefix='{', $postfix='}', $parse=FALSE){
		if(preg_match_all("#[\\\\]i(nclude)?".Morpheus::escape_preg_chars($prefix)."([^".Morpheus::escape_preg_chars($postfix)."]+)".Morpheus::escape_preg_chars($postfix)."#i", $str, $buffer)){
			if(isset($buffer[0]) && is_array($buffer[0])){foreach($buffer[0] as $i=>$original){
				$str = str_replace($buffer[0][$i],
						self::_basic_parse_encapsule('**',
							self::basic_parse_template((isset($flags['content-root']) ? $flags['content-root'] : Morpheus::get_root("content/text")).$buffer[2][$i], $flags, $prefix, $postfix, $parse),
							md5($buffer[2][$i])
						)
						, $str);
			}}
		}
		return $str;
	}
	
	/*Mustache*/
	function mustache($template, $obj=array(), $prefix='{{{', $postfix='}}}'){
		/*fix*/ if(isset($this) && ( is_array($template) || is_object($template) ) ){ $obj = $template; $template = $this->get_template(); }
		
		$str = $template; $arr = array();
		if(is_object($obj)){
			$class = get_class($obj);
			foreach(get_object_vars($obj) as $key=>$value){
				if(!preg_match('#^[_]#', $key)){ $arr[$key] = $value; }
			}
			//*debug*/ print_r(get_class_methods($obj));
			foreach(get_class_methods($obj) as $i=>$method){
				//https://stackoverflow.com/questions/3989190/get-number-of-arguments-for-a-class-function
				$classMethod = new ReflectionMethod($class,$method);
				$cmParameters = $classMethod->getParameters();
				//*debug*/ print $class.'::'.$method.' ('.count($cmParameters).') = '; print_r($classMethod->getParameters());
				if(!preg_match('#^[_]#', $method) && ($method != $class) ){
					$bool = TRUE;
					foreach($cmParameters as $j=>$key){
						$bool = ($bool && isset($arr[$cmParameters[$j]->name]) ? TRUE : FALSE);
					}
					if($bool){
						switch(count($cmParameters)){
							case 1: $arr[$method] = $obj->$method($arr[$cmParameters[0]->name]); break;
							case 2: $arr[$method] = $obj->$method($arr[$cmParameters[0]->name], $arr[$cmParameters[1]->name]); break;
							case 3: $arr[$method] = $obj->$method($arr[$cmParameters[0]->name], $arr[$cmParameters[1]->name], $arr[$cmParameters[2]->name]); break;
							case 4: $arr[$method] = $obj->$method($arr[$cmParameters[0]->name], $arr[$cmParameters[1]->name], $arr[$cmParameters[2]->name], $arr[$cmParameters[3]->name]); break;
							case 5: $arr[$method] = $obj->$method($arr[$cmParameters[0]->name], $arr[$cmParameters[1]->name], $arr[$cmParameters[2]->name], $arr[$cmParameters[3]->name], $arr[$cmParameters[4]->name]); break;
							case 6: $arr[$method] = $obj->$method($arr[$cmParameters[0]->name], $arr[$cmParameters[1]->name], $arr[$cmParameters[2]->name], $arr[$cmParameters[3]->name], $arr[$cmParameters[4]->name], $arr[$cmParameters[5]->name]); break;
							case 0: $arr[$method] = $obj->$method(); break;
							default: if(!isset($arr[$method])){$arr[$method] = NULL;}
						}
					}
				}
			}
		}
		elseif(is_array($obj)){
			$arr = $obj;
		}
		
		/*+ Partials {{> mustache}} */
		/*+ Sections and inverted Sections: {{#mustache}} ... {{/mustache}} */
		
		if(strlen($prefix) >= 3 && strlen($postfix) >= 3){ $str = self::basic_parse_str($str, $arr, substr($prefix, 0, 3), substr($postfix, 0, 3), TRUE); }
		$str = self::basic_parse_str($str, $arr, substr($prefix, 0, 2).'&', substr($postfix, 0, 2), TRUE);
		$str = self::basic_parse_str($str, self::htmlspecialchars($arr), substr($prefix, 0, 2), substr($postfix, 0, 2), TRUE);
	 	return $str;
	}
	public function /*recursive*/ htmlspecialchars($o){
		if(is_array($o)){
			foreach($o as $i=>$j){ $o[$i] = self::htmlspecialchars($j); }
		} elseif(is_object($o)){
			foreach(get_object_vars($o) as $key=>$value){ $o->$key = self::htmlspecialchars($value); }
		} else { return htmlspecialchars($o); }
		return $o;
	}
	 
	/*Delayed rendering*/
	public function set_template($template=NULL){
		if(isset($this)){
			$this->_template = $template;
		}
	}
	public function get_template(){ return (isset($this) && isset($this->_template) ? $this->_template : NULL); }
	function __toString(){
		if(isset($this) && isset($this->_template) ){
			return $this->mustache($this->_template, $this);
		} else { return NULL; }
	}
	
	
	function taxed_value($value){ return round( $value * (1 / 1.21) , 2); }
}
?>
