<?php
class Morpheus {
	//private $_parsers = array();
	private $_src = FALSE;
	private $_domain = "text/plain";
	private $_template = "";
	private $_args = array();
	
	function Morpheus($a=NULL, $b=array(), $c=FALSE){
		if($b === array()){ $this->_args = array(); }
		if(!($a === NULL) && is_string($a)){
			if(preg_match("#[\.](".implode('|', Morpheus::get_file_extensions()).")$#", $a)){
				$this->_src = $a;
				if(!($c===FALSE)){ $this->_domain = $c; }
				$this->_template = $this->load_template($this->_src, FALSE);
			} //*/
			elseif(class_exists('Hades') && preg_match("#^([\\]?[a-z][a-z_]+([\\][a-z_]+)*)[:]{2}([a-z_]+)$#", $a, $buf) && class_exists($buf[1]) && method_exists($buf[1], $buf[3]) && method_exists($buf[1], 'validate_hades_elements') ){
				$nam = $buf[1]; $other = new $nam(); $act = $buf[3];
				if($other->validate_hades_elements($act)){
					$this->_src = $a;
					$other->$act($this->_args);
					$this->_template = $other;
				} else { $this->_template = $a; }
			} //*/
			else{
				$this->_template = $a;
			}
		}
	}
	
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
		//*debug*/ print '<!-- Morpheus::notify '.preg_replace('#\s+#', ' ', print_r($code, TRUE).' '.print_r($vars, TRUE).' ('.print_r($line, TRUE)).') -->'."\n";
		if(class_exists("Hades") ){ return Hades::notify($code, $vars, $line); }
		return FALSE;
	}
	function get_root($sub=NULL){
		//*debug*/ print '<!-- Morpheus::get_root '.preg_replace('#\s+#', ' ', print_r($sub, TRUE)).' -->'."\n";
		if(class_exists("Hades") && defined('HADES') && isset(${HADES}) ){ return Hades::get_root($sub); }
		/* add alias of the Heracles method */
		return dirname(__FILE__).'/';
	}
	function get_file_uri($name, $sub=NULL, $ext=FALSE, $result_number=0, $with_prefix=TRUE){
		//*debug*/ print '<!-- Morpheus::get_file_uri '.preg_replace('#\s+#', ' ', print_r($name, TRUE).' '.print_r($sub, TRUE).' '.print_r($ext, TRUE)).' -->'."\n";
		//*debug*/ global ${HADES}; print "\t".'<!-- '.print_r(class_exists("Hades"), TRUE).' | '.print_r(defined('HADES'), TRUE).' | '.print_r(isset(${HADES}), TRUE).' -->'."\n";
		if(class_exists("Hades") && defined('HADES') ){ global ${HADES}; if( isset(${HADES}) ){ return Hades::get_file_uri($name, $sub, $ext, $result_number, $with_prefix); }}
		
		if($ext === FALSE){ $ext = array_merge(array(NULL), self::get_file_extensions()); }
		/*fix*/ if(!is_array($sub)){ $sub = array($sub); }
		foreach($sub as $i=>$m){
			if($mroot = self::get_root($m)){
				if(is_dir($mroot)){
					foreach($ext as $j=>$x){
						if(file_exists($mroot.$name.($x === NULL ? NULL : '.'.$x))){ return $mroot.$name.($x === NULL ? NULL : '.'.$x); }
					}
				}
			}
		}
		return FALSE;
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
		if(preg_match_all("#".Morpheus::escape_preg_chars($prefix)."([\*]+|[:][^:]+[:]|[\%\@\.]{1})?([a-z][^\?".Morpheus::escape_preg_chars($postfix)."]{0,})\?([^:]+)[:]([^".Morpheus::escape_preg_chars($postfix)."]{0,})".Morpheus::escape_preg_chars($postfix)."#i", $str, $buffer)){
			//*debug*/ print '<!-- '; print_r($buffer); print ' -->';
			if(isset($buffer[0]) && is_array($buffer[0])){foreach($buffer[0] as $i=>$original){
				$str = str_replace($original, 
						self::_basic_parse_encapsule($buffer[1][$i],
							$buffer[(isset($flags[$buffer[2][$i]]) && ( is_bool($flags[$buffer[2][$i]]) ? $flags[$buffer[2][$i]] : (in_array(strtolower($flags[$buffer[2][$i]]), array('true','false','yes','no')) ? in_array(strtolower($flags[$buffer[2][$i]]), array('true','yes')) : $flags[$buffer[2][$i]] != NULL) ) ? 3 : 4)][$i],
							$buffer[2][$i])
						, $str);
			}}
		}
		if(preg_match_all("#".Morpheus::escape_preg_chars($prefix)."([\*]+|[:][^:]+[:]|[\%\@\.]{1})?([a-z][^\|".Morpheus::escape_preg_chars($postfix)."]{0,})[\|]([^".Morpheus::escape_preg_chars($postfix)."]{0,})".Morpheus::escape_preg_chars($postfix)."#i", $str, $buffer)){
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
			case '@': case '!': case '~': case '\\':
				///if(class_exists('undefined')){}
				break;
			// case '#': case '^': case '/': /* Mustache uses these for (inverted) sections */ break;
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
			Morpheus::notify(array(__METHOD__.'.exists', 200), array("src"=>str_replace(Morpheus::get_root('text/plain'), NULL, $src)));
			if(strlen($str) <= 0){
				$str = Morpheus::basic_parse_template(Morpheus::get_root("text/error").'000-empty-document.md', array('src'=>$src, "document"=>basename($src)));
				Morpheus::notify(000, array("src"=>str_replace(Morpheus::get_root('text/plain'), NULL, $src)));
			}
		} else {
			if($src != Morpheus::get_root("text/error").'404-not-found.md'){
				$str = Morpheus::basic_parse_template(Morpheus::get_root("text/error").'404-not-found.md', array('src'=>$src, "document"=>basename($src)));
				Morpheus::notify(404, array("src"=>str_replace(Morpheus::get_root('text/plain'), NULL, $src)));
			}
			else {
				$str = NULL;
				Morpheus::notify(__METHOD__.'.failed', array("src"=>str_replace(Morpheus::get_root('text/plain'), NULL, $src)));
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
							self::basic_parse_template((isset($flags['content-root']) ? $flags['content-root'] : Morpheus::get_root("text/plain")).$buffer[2][$i], $flags, $prefix, $postfix, $parse),
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
		
		$str = $template; $arr = self::get_obj_tags($obj);
		
		/*+ Partials {{> mustache}} */
		if(preg_match_all("#".Morpheus::escape_preg_chars(substr($prefix, 0, 2))."([\>]\s?([^\|\?".Morpheus::escape_preg_chars($postfix)."]{0,}))([\|\?][^".Morpheus::escape_preg_chars($postfix)."]+)?".Morpheus::escape_preg_chars(substr($postfix, 0, 2))."#", $str, $buffer)){
			foreach($buffer[1] as $i=>$partial){
				$arr[$partial] = self::load_template($buffer[2][$i]);
				if(isset($this)){ $this->_args[$partial] = $arr[$partial]; }
			}
		}
		
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
	
	function _encapsule($str=NULL, $instruction=NULL, $ident=NULL, $src=FALSE){
		if(!($ident === NULL) && strlen($instruction) == 0 ){ $instruction = 'div'; }
		if(!(strlen($str) == 0) && preg_match("#^(([a-z0-9_-]+[\:])?[a-z0-9_-]+)(([\.][a-z0-9_-]+)*)([\#][a-z0-9_-]+)?$#i", $instruction, $buffer)){
			list($all, $tag, $namespace, $class, $sub, $idref) = $buffer;
			$str = '<'.$tag
				.(strlen($ident) > 0 || strlen($idref) > 0 ? ' id="'.(strlen($idref) > 0 ? substr($idref, 1) : $ident).'"' : NULL)
				.(strlen($class) > 0 ? ' class="'.str_replace('.', ' ', substr($class, 1)).'"' : NULL)
				.(!($src === FALSE) ? ' contenteditable="true" data-src="'.$src.'"' : NULL)
				.'>'.$str.'</'.$tag.'>';
		}
		return $str;
	}
	
	function parse($str=NULL, $flags=array(), $recursive=FALSE, $src=FALSE){
		if($str === NULL && isset($this->_template)){ $str = $this->_template; }
		if($src === FALSE && isset($this->_src)){ $src = $this->_src; }
		$out = $str;
		$flags = array_merge(self::get_tags($out), self::get_obj_tags($flags) /*, $flags*/ );
		$BLOCK = self::get_blocks($str);
		foreach($BLOCK as $i=>$b){
			/*RESET*/ $val = NULL;
			if(isset($flags[$b['name']])){
				/* set value override */ $val = $flags[$b['name']];
			} else {
				switch($b['name-prefix']){
					case '> ': case '>':
						$val = file_get_contents(self::get_file_uri($b['name-part']));
						break;
					case '%': //domain: Heracles
						if(class_exists('Heracles') && method_exists('Heracles','load_record_flags')){
							$h = Heracles::load_record_flags(Heracles::get_user_id());
							$val = (isset($h[$b['name-part']]) ? $h[$b['name-part']] : $val);
						}
						break;
					case '.': //domain Hades
						if(class_exists('Hades') && method_exists('Hades','get_element_by_name')){ $val = Hades::get_element_by_name($b['name-part']); }
						break;
					default:
						/* set value */ $val = NULL;
				}
			}
			/* conditional */
			switch(substr($b['conditional-full'], 0, 1)){
				case '?':
					$val = ((is_bool($val) ? $val == TRUE : !($val === NULL || preg_match('#^(false|no)$#i', $val))) ? $b['condition-positive'] : $b['condition-negative']);
					break;
				case '|':
					if($val === NULL){ $val = $b['default-value']; }
					break;
				default: /*is not conditional*/
			}
			/* encapsule */ if($b['encapsule'] || preg_match('#^[\*]{1,2}$#', $b['aterisk'])){ $val = self::_encapsule($val, (isset($b['encapsule-tag']) ? $b['encapsule-tag'] : $b['encapsule-colon']), (preg_match('#^[\*]{1,2}$#', $b['aterisk']) ? $b['name-part'] : NULL), (!($src === FALSE) && preg_match('#^[\#]#', $b['aterisk']) ? $src : FALSE) ); }
			/* aterisk (**) */ if(preg_match('#^[\*]{2}$#', $b['aterisk'])){ $val = '<a name="'.$b['name-part'].'"></a>'.$val; }
			/* apply value */
			$out = str_replace($b['match'], $val, $out);
		}
		return $out;
	}

	function get_blocks($str=NULL, $prefix='{', $postfix='}'){
		$BLOCK = array();
		$b = self::get_flags($str, $prefix, $postfix, array());
		foreach($b[5] as $i=>$name){
			$BLOCK[$i] = array('match'=>$b[0][$i], 'aterisk'=>$b[1][$i], 'encapsule'=>$b[2][$i], 'encapsule-colon'=>$b[3][$i], 'encapsule-tag'=>$b[4][$i], 'name'=>$b[5][$i], 'name-prefix'=>$b[6][$i], 'name-part'=>$b[7][$i], 'conditional-full'=>$b[8][$i], 'default-value'=>$b[9][$i], 'condition-positive'=>$b[10][$i], 'condition-negative'=>$b[11][$i]);
			foreach($BLOCK[$i] as $n=>$v){ if(strlen($v) > 0){ $BLOCK[$i]['activated'][] = $n; } }
		}
		return $BLOCK;
	}
	function get_flags($str=NULL, $prefix='{', $postfix='}', $select=5){
		if($str === NULL && isset($this)){ $str = $this->_template; }
		preg_match_all('#'.Morpheus::escape_preg_chars($prefix).'([\*]{1,2}|[\#])?([\:]([^\:]+)[\:]|[\<]([^\>]+)[\>])?(([\.\%\@\!\~\\\\]|[>\&\/\^]\s?)?([a-z0-9_-]+))([\|]([^'.Morpheus::escape_preg_chars($postfix).']*)|[\?]([^\:]*)[\:]([^'.Morpheus::escape_preg_chars($postfix).']*))?'.Morpheus::escape_preg_chars($postfix).'#i', $str, $buffer);
		return (is_array($select) || !isset($buffer[$select]) ? $buffer : array_unique($buffer[$select]));
	}
	
	function get_tags($str=NULL, $all=TRUE){
		if($str === NULL && isset($this)){ $str = $this->_template; }
		$from = self::_select_part($str, $all);
		$tags = array();
		preg_match_all('#[\@]([a-z0-9_\:\.\/\-]+)([\(]([^\)]+)[\)])?(\s|$)#i', $from, $buffer);
		foreach($buffer[1] as $i=>$tag){
			$tags = self::assign_value($tags, $tag, $buffer[3][$i]);
		}
		return $tags;
	}
	function strip_tags($str=NULL, $all=TRUE){
		if($str === NULL && isset($this)){ $str = $this->_template; }
		$from = self::_select_part($str, $all);
		$where = preg_replace('#[\@]([a-z0-9_\:\.\/\-]+)([\(]([^\)]+)[\)])?(\s|$)#i', '', $from);
		$str = str_replace($from, $where, $str);
		return $str;
	}
	function get_obj_tags($obj=FALSE){
		$arr = array();
		if(is_object($obj)){
			$class = get_class($obj);
			if(isset($this) && isset($this->_args) && is_array($this->_args)){ $arr = array_merge($this->_args, $arr); }
			if(isset($obj->_args)){ $arr = array_merge($obj->_args, $arr); }
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
			if(isset($this) && isset($this->_args) && is_array($this->_args)){ $arr = array_merge($this->_args, $arr); }
		}
		return $arr;
	}
	/*private*/ function _select_part($str, $all=TRUE){
		switch($all){
			case FALSE: /*check for tags in only the first line*/
				$from = preg_replace('#^([^\n\r]+)(.*)$#', '\\1', $str);
				break;
			case NULL: /*<morpheus>(.*)</morpheus>*/	break;
			case TRUE: default:
				$from = $str;
		}
		return $from;
	}
	/*private?*/ function assign_value(&$tags, $tag, $val=NULL){
		/*fix*/ if(!isset($tags) || !is_array($tags)){ $tags = array(); }
		if(isset($tags[$tag])){
			if(is_array($tags[$tag])){
				$tags[$tag][] = $val;
			}
			else{
				$tags[$tag] = array($tags[$tag], $val);
			}
		}
		else{
			$tags[$tag] = $val;
		}
		return $tags;
	}
	 
	/*Delayed rendering*/
	public function set_template($template=NULL){
		if(isset($this)){
			$this->_template = $template;
		}
	}
	public function get_template(){ return (isset($this) && isset($this->_template) ? $this->_template : NULL); }
	public function _old_load_template($src=NULL, $ext=TRUE){
		if($ext === FALSE){ $ext = array(); }
		elseif($ext === TRUE || (!is_string($ext) && !is_array($ext))){ $ext = Morpheus::get_file_extensions(); }
		elseif(is_string($ext) && strlen($ext) > 0){ $ext = array($ext); }
		if($template === NULL && isset($this->_src) ){ $src = $this->_src; }
		foreach(array_merge(array(NULL), $ext) as $i=>$x){
			if(file_exists( Morpheus::get_root("text/plain").$src.($x !== NULL ? '.'.$x : NULL) )){
				return file_get_contents( Morpheus::get_root("text/plain").$src.($x !== NULL ? '.'.$x : NULL) );
			}
		}
		return FALSE;
	}
	public function load_template($src=NULL, $ext=NULL){
		//Morpheus::notify(__METHOD__, array('src'=>$src, 'ext'=>$ext) );
		$sub = (isset($this) && isset($this->_domain) ? $this->_domain : NULL);
		if($uri = self::get_file_uri($src, $sub, $ext)){
			return file_get_contents($uri);
		}
		return FALSE;
	}
	public function get_file_extensions(){
		 /*in order of importance*/ 
		return array('m','template','morph','morpheus','mustache','md','markdown','json','html','txt','taskpaper');
	}
	
	
	function __toString(){
		Morpheus::notify(__METHOD__);
		if(isset($this) && isset($this->_template) ){
			return self::strip_tags(self::parse($this->_template, $this));
			//return $this->mustache($this->_template, $this);
		} else { return NULL; }
	}
}
?>
