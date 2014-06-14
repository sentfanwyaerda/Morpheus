<?php
 class Morpheus {
	var $parsers = array();
	function Morpheus(){}
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
		foreach($set as $tag=>$value){
			$str = str_replace($prefix.$tag.$postfix, $value, $str);
		}
		if(preg_match_all("#".Morpheus::escape_preg_chars($prefix)."([\*]+|[:][^:]+[:])?([^\?".Morpheus::escape_preg_chars($postfix)."]{0,})\?([^:]+)[:]([^".Morpheus::escape_preg_chars($postfix)."]{0,})".Morpheus::escape_preg_chars($postfix)."#i", $str, $buffer)){
			//*debug*/ print '<!-- '; print_r($buffer); print ' -->';
			if(isset($buffer[0]) && is_array($buffer[0])){foreach($buffer[0] as $i=>$original){
				$str = str_replace($original, 
						self::_basic_parse_encapsule($buffer[1][$i],
							$buffer[(isset($set[$buffer[2][$i]]) && ( is_bool($set[$buffer[2][$i]]) ? $set[$buffer[2][$i]] : $set[$buffer[2][$i]] != NULL ) ? 3 : 4)][$i],
							$buffer[2][$i])
						, $str);
			}}
		}
		if(preg_match_all("#".Morpheus::escape_preg_chars($prefix)."([\*]+|[:][^:]+[:])?([^\|".Morpheus::escape_preg_chars($postfix)."]{0,})[\|]([^".Morpheus::escape_preg_chars($postfix)."]{0,})".Morpheus::escape_preg_chars($postfix)."#i", $str, $buffer)){
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
			default: /*do nothing*/
		}
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
 }
 ?>
