<?php
namespace Morpheus;

require_once(dirname(__FILE__).'/Morpheus.php');

class Markdown extends \Morpheus {
	function Markdown($a=NULL, $b=array(), $c=FALSE){
		$this->_template = $a;
		$this->_tag = $b;
	}
	
	function _encode_order(){ return array_reverse(self::_decode_order()); }
	function _decode_order(){ return array('clean','bold','italic','strikethrough', 'inline_code', 'syntax_highlighting', 'underline', 'link', 'headers','horizontal_rule', 'blockquote', 'lists', 'task_done', 'table', 'p_br','clean'); }
	
	/* Encode: HTML to Markdown*/
	function encode($html=FALSE){
		if($html === FALSE && isset($this)){ $html = $this->_template; }
		$md = $html;
		foreach(self::_encode_order() as $i=>$el){
			$cur = 'encode_'.strtolower($el);
			if(method_exists($this, $cur)){ $md = $this->$cur($md); }
		}
		return $md;
	}
	
	/* Decode: Markdown to HTML */
	function decode($md=FALSE){
		if($md === FALSE && isset($this)){ $md = $this->_template; }
		$html = $md;
		foreach(self::_decode_order() as $i=>$el){
			$cur = 'decode_'.strtolower($el);
			if(method_exists($this, $cur)){ $html = $this->$cur($html); }
		}
		return $html;
	}
	
	/* ELEMENTS */
	function encode_bold($str=NULL){ return self::_encode_tag_only($str, 'strong', '**', '**', array('b','strong') ); }
	function decode_bold($str=NULL){ return self::_decode_tag_only($str, 'strong', '**', '**', array('b','strong') ); }
	
	function encode_italic($str=NULL){ return self::_encode_tag_only($str, 'em', '*', '*', array('i','em') ); }
	function decode_italic($str=NULL){ return self::_decode_tag_only($str, 'em', '*', '*', array('i','em') ); }
	
	function encode_strikethrough($str=NULL){ return self::_encode_tag_only($str, 's', '~~', '~~' ); }
	function decode_strikethrough($str=NULL){ return self::_decode_tag_only($str, 's', '~~', '~~' ); }
	
	function encode_underline($str=NULL){ return self::_encode_tag_only($str, 'u', '_', '_'); }
	function decode_underline($str=NULL){ return self::_decode_tag_only($str, 'u', '_', '_'); }

	function encode_link($str=NULL){
		$str = preg_replace('#\<a href\=\"([^\"]+)\"\>([^\<]+)\<\/a\>#', '[\\2](\\1)', $str);
		return $str;
	}
	function decode_link($str=NULL){
		$str = preg_replace('#(^|[^\!])[\[]([^\]]+)[\]][\(]([^ \)]+)\s([\"]([^\"\)]+)[\"])[\)]#', '\\1<a href="\\3" title="\\5">\\2</a>', $str);
		$str = preg_replace('#(^|[^\!])[\[]([^\]]+)[\]][\(]([^\)]+)[\)]#', '\\1<a href="\\3">\\2</a>', $str);
		$str = preg_replace('#\<((http[s]?\:\/\/|mailto\:)([^ \>]+))\>#', '<a href="\\1">\\3</a>', $str);
		return $str;
	}
	
	function encode_headers($str=NULL){
		for($i=6;$i>=1;$i--){
			$str = str_replace('</h'.$i.'>', '¤', $str);
			if(preg_match_all('#(^\s*|\n\s*)\<h'.$i.'\>([^¤]+)[¤](\s*)#', $str, $buffer) > 0){
				foreach($buffer[2] as $a=>$h){
					$str = str_replace($buffer[0][$a], $buffer[1][$a].str_repeat('#', $i).' '.$h.$buffer[3][$a], $str);
				}
			}
			/*fix*/ $str = str_replace('¤', '</h'.$i.'>', $str);
		}
		return $str;
	}
	function decode_headers($str=NULL){
		if(preg_match_all('#(^|\n)([\#]{1,6})\s?([^\n]+)#', $str, $buffer) > 0){
			foreach($buffer[2] as $i=>$h){
				$str = str_replace($buffer[0][$i], $buffer[1][$i].'<h'.strlen($h).'>'.$buffer[3][$i].'</h'.strlen($h).'>', $str);
			}
		}
		$str = preg_replace('#(^|\n)([^\n]+)\n[\=]{3,}\n#', '\\1<h1>\\2</h1>\n', $str);
		$str = preg_replace('#(^|\n)([^\n]+)\n([\-]{3,}|\<hr\/\>)\n#', '\\1<h2>\\2</h2>\n', $str);
		if(preg_match_all('#\<h([1-6])\>([^\<]+)\<\/h[1-6]\>#i', $str, $buffer) > 0){
			foreach($buffer[2] as $a=>$h){
				$str = str_replace($buffer[0][$a], '<h'.$buffer[1][$a].' id="H'.substr(md5($h), 0, 15).'">'.$h.'</h'.$buffer[1][$a].'>', $str);
			}
		}
		return $str;
	}
	
	function encode_horizontal_rule($str=NULL){
		$str = preg_replace('#(\s*)<hr/>(\s*)#i', '\\1----------\\2', $str);
		return $str;
	}
	function decode_horizontal_rule($str=NULL){
		$str = preg_replace('#(^|\n)([\*][ ]?[\*][ ]?[\*][\* ]*)(\n|$)#', '\\1<hr/>\\3', $str);
		$str = preg_replace('#(^|\n)([-][ ]?[-][ ]?[-][ -]*)(\n|$)#', '\\1<hr/>\\3', $str);
		return $str;
	}
	
	function encode_inline_code($str=NULL){ return self::_encode_tag_only($str, 'code', '`', '`' ); }
	function decode_inline_code($str=NULL){ return self::_decode_tag_only($str, 'code', '`', '`' ); }
	
	function encode_syntax_highlighting($str=NULL){
		return $str;
	}
	function decode_syntax_highlighting($str=NULL){
		$str = self::_decode_prefixed_line($str, '[ ]{4}', NULL, 'pre');
		$str = str_replace('```', '¤', $str);
		if(preg_match_all('#¤([^\n¤]+)?\n([^¤]+)¤#', $str, $buffer) > 0){
			foreach($buffer[0] as $i=>$orig){
				$str = str_replace($orig, '<pre><code'.(strlen($buffer[1][$i]) > 0 ? ' class="'.$buffer[1][$i].'"' : NULL).'>'.$buffer[2][$i].'</code></pre>', $str);
			}
		}
		$str = str_replace('¤', '```', $str);
		return $str;
	}
	
	function encode_blockquote($str=NULL){ return $str; }
	function decode_blockquote($str=NULL){ return self::_decode_prefixed_line($str, '[\>]\s', NULL, 'blockquote'); }
	
	function encode_table($str=NULL){ return $str; }
	function decode_table($str=NULL){
		$tnr = $hdr = array();
		$lines = explode("\n", $str);
		foreach($lines as $i=>$line){
			if(preg_match_all('#(^|\s|[\-\:])[\|]{1,}(\s|[\-\:]|$)#', $line) >= 2){
				$tnr[] = $i;
				if(preg_match('#^\s*[\|]?\s?[:]?[\-]{3,}[\:]?\s?([\|]\s?[:]?[\-]{3,}[\:]?\s?)*[\|]?\s*$#', $line)){
					$hdr[$i] = $line;
				}
			}
		}
		//*debug*/ print '<!-- tnr='.print_r($tnr, TRUE).' hdr='.print_r($hdr, TRUE).' -->';
		foreach($lines as $i=>$line){
			if(in_array($i, $tnr) && !isset($hdr[$i])){
				$lines[$i] = (!in_array($i-1, $tnr) ? '<table>' : NULL).'<tr>'.self::_table_decode_line($line, self::_table_decode_middle($hdr[self::_table_line_type($i, $tnr, array_keys($hdr), FALSE)]), self::_table_line_type($i, $tnr, array_keys($hdr))).'</tr>'.(!in_array($i+1, $tnr) ? '</table>' : NULL);
			}
		}
		foreach($hdr as $q=>$i){ unset($lines[$q]); }
		$str = implode("\n", $lines);
		return $str;
	}
	function _table_line_type($line=0, $tnr=array(), $hdr=array(), $typ=TRUE){
		if(!in_array($line, $tnr)){ /*line is NOT within table*/ return FALSE; }
		if(in_array($line, $hdr)){ /*line is THE table divider*/ return NULL; }
		$start = min($tnr); $middle = $finish = NULL;
		foreach($tnr as $i=>$t){
			if($t <= $line && !in_array($t-1, $tnr)){ $start = $t; }
			if($t >= $line && !in_array($t+1, $tnr) && $finish === NULL){ $finish = $t; }
			if($t >= $start && ($finish === NULL ? TRUE : $t <= $finish) && in_array($t, $hdr)){ $middle = $t;}
		}
		if($middle === NULL){ $middle = $start; }
		if(is_bool($typ)){ return ($typ === TRUE ? ($line < $middle ? 'th' : 'td') : ($middle != $start ? $middle : NULL) ); }
		else{ return (isset($$typ) ? $$typ : FALSE); }
	}
	function _table_decode_line($line, $align=array(), $el='td'){
		$str = $line;
		$str = preg_replace('#\s?[\|]\s*$#', '', $str);
		$str = preg_replace('#^\s*[\|]\s?#', '', $str);
		preg_match_all('#([^\|]+)(\s?[\|]{1,}\s?|$)#', $str, $buffer);
		//*debug*/ print '<!-- '.$str.' #='.print_r($buffer, TRUE).' -->';
		$add = 0;
		foreach($buffer[1] as $i=>$block){
			$debug = NULL;
			//$debug = '<!-- '.$buffer[1][$i].' -->'.'<!-- '.$buffer[2][$i].' -->'.'<!-- '.$align[$i].' -->';
			$str = str_replace($buffer[0][$i], '<'.$el.($align[$i+$add] !== NULL ? ' align="'.$align[$i+$add].'"' : NULL).(strlen(trim($buffer[2][$i])) >= 2 ? ' colspan="'.strlen(trim($buffer[2][$i])).'"' : NULL).'>'.trim($block).$debug.'</'.$el.'>', $str);
			$add += (strlen(trim($buffer[2][$i])) >= 2 ? strlen(trim($buffer[2][$i]))-1 : 0);
		}
		return $str;
	}
	function _table_decode_middle($line){
		$align = array();
		$line = preg_replace('#^\s*[\|]?\s?([:]?[\-]{3,}[\:]?\s?([\|]\s?[:]?[\-]{3,}[\:]?\s?)*)[\|]?\s*$#', '\\1', $line);
		$split = explode('|', $line);
		foreach($split as $i=>$s){
			if(preg_match('#^\s*[:][\-]{3,}[\:]\s*$#', $s)){ $align[$i] = 'center'; }
			elseif(preg_match('#^\s*[:][\-]{3,}\s*$#', $s)){ $align[$i] = 'left'; }
			elseif(preg_match('#^\s*[\-]{3,}[\:]\s*$#', $s)){ $align[$i] = 'right'; }
			else{ $align[$i] = NULL; }
		}
		//print '<!-- '.print_r($align, TRUE).' -->';
		return $align;
	}
	
	function encode_lists($str=NULL){ return $str; }
	function decode_lists($str=NULL){
		$str = self::_decode_prefixed_line($str, '[\*\-\+]\s', 'li', 'ul');
		$str = self::_decode_prefixed_line($str, '[0-9]+[\.]\s', 'li', 'ol');
		return $str;
	}
	
	function encode_task_done($str=NULL){ return $str; }
	function decode_task_done($str=NULL){
		//y$str = str_replace('@done', '<i class="fa fa-fw fa-check text-success"></i>', $str);
		return $str;
		
		
		$str = str_replace('</li>', '¤', $str);
		if(preg_match_all('#<li([^>]*)>([^¤]+)[¤]#', $str, $buffer) > 0){
			foreach($buffer[0] as $a=>$line){
				if(preg_match('#@done(\(([^\)]+)\))?#', $line, $b)){
					$extra = $buffer[1][$a];
					// class="task-done"
					// data-done = $b[2];
					$str = str_replace($line, '<li'.$extra.'>'.str_replace($b[0], '<i class="fa fa-fw fa-check text-success"></i>', $buffer[2][$a]).'¤', $str);
				}
			}
		}
		$str = str_replace('¤', '</li>', $str);
		return $str;
	}
	
	function encode_p_br($str=NULL){
		$str = str_replace('</p>', '¤', $str);
		if(preg_match_all('#(^|\s+)\<p\>([^¤]+)¤(\s+|$)#i', $str, $buffer) > 0){
			foreach($buffer[2] as $i=>$line){
				$str = str_replace($buffer[0][$i], (!(preg_match_all('#\n#', $buffer[1][$i]) >= 2 || strlen($buffer[1][$i]) == 0 ) ? "\n" : NULL).$buffer[1][$i].$line.$buffer[3][$i].(!(preg_match_all('#\n#', $buffer[3][$i]) >= 2 || strlen($buffer[3][$i]) == 0 ) ? "\n" : NULL), $str);
			}
		}
		if(preg_match_all('#\<br\/\>(\s+|$)#', $str, $buffer)){
			foreach($buffer[1] as $i=>$s){
				$str = str_replace($buffer[0][$i], $buffer[1][$i].(!(preg_match_all('#\n#', $buffer[1][$i]) >= 2 || strlen($buffer[1][$i]) == 0 ) ? "\n" : NULL), $str);
			}
		}
		$str = str_replace('¤', '</p>', $str);
		return $str;
	}
	function decode_p_br($str=NULL){
		$lines = explode("\n", $str); $open = TRUE;
		foreach($lines as $i=>$line){
			if(preg_match('#^\s*$#', $line)){ $open = TRUE; }
			else{
				preg_match('#^(\s*)([\<]([a-z0-9]+)([^\>]+)?[\>])?#', $line, $buffer);
				switch(strtolower($buffer[3])){
					case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6': case 'hr': case 'ol': case 'ul': case 'li': case 'blockquote': case 'pre': case 'table': case 'tr': case 'th': case 'td': case 'style': case 'script': break;
					default: //!isset($lines[$i-1]) || preg_match('#^\s*$#', $lines[$i-1]) ||  
						//&& (!isset($lines[$i+1]) || preg_match('#^\s*$#', $lines[$i+1]))
						if(!($open === FALSE) ){
							$lines[$i] = $buffer[1].($open === TRUE ? '<p>' : NULL).preg_replace('#^'.$buffer[1].'#', '', $lines[$i]).((!isset($lines[$i+1]) || preg_match('#^\s*$#', $lines[$i+1])) ? '</p>' : '<br/>');
							$open = (!((!isset($lines[$i+1]) || preg_match('#^\s*$#', $lines[$i+1]))) ? NULL : FALSE);
						}
				}
			}
		}
		$str = implode("\n", $lines);
		return $str;
	}
	
	/* Helper Functions */
	function _encode_tag_only($str=NULL, $tag='', $prefix='', $postfix='', $options=array(), $newline=FALSE){
		//$options = array('i', 'em');
		if(!is_array($options) || count($options) == 0){ $options = array($tag); }
		foreach($options as $i=>$t){
			$str = str_replace('</'.$t.'>', '¤', $str);
			$str = preg_replace('#\<'.$t.'\>([^¤]*)[¤]#', $prefix.'\\1'.$postfix, $str);
			$str = str_replace('¤', '</'.$t.'>', $str);
		}
		return $str;
	}
	function _decode_tag_only($str=NULL, $tag='', $prefix='', $postfix='', $options=array(), $newline=FALSE){
		$pattern = '#'.\Morpheus::escape_preg_chars($prefix).'([^'.\Morpheus::escape_preg_chars($postfix).($newline === FALSE ? '\n' : NULL).']+)'.\Morpheus::escape_preg_chars($postfix).'#i';
		$str = preg_replace($pattern, '<'.$tag.'>\\1</'.$tag.'>', $str);
		return $str;
	}/*
	function _decode_tag_only($str=NULL, $tag='', $prefix='', $postfix='', $options=array(), $newline=FALSE){
		$str = str_replace($postfix, '¤', $str);
		$pattern = '#'.\Morpheus::escape_preg_chars($prefix).'([^¤'.($newline === FALSE ? '\r\n' : NULL).']+)[¤]#i';
		$str = preg_replace($pattern, '<'.$tag.'>\\1</'.$tag.'>', $str);
		$str = str_replace('¤', $postfix, $str);
		return $str;
	}*/
	function _decode_prefixed_line($str=NULL, $prefix=NULL, $tag=NULL, $group=NULL){
		$lines = explode("\n", $str); $depth = 0;
		foreach($lines as $i=>$line){
			if(preg_match('#^(\s*)'.$prefix.'(.*)(\s*)$#', $line, $buffer)){
				/*fix*/ $b1 = str_replace(str_repeat(' ', 3), "\t", $buffer[1]);
				$nl = (!($tag===NULL) ? '<'.$tag.' depth="'.$depth.'" b="'.(strlen($b1)+1).'">' : $buffer[1]).$buffer[2].(!($tag===NULL) ? '</'.$tag.'>' : NULL).$buffer[3];
				if((strlen($b1)+1) != $depth){
					if((strlen($b1)+1) > $depth){
						$lines[$i] = preg_replace('#^(\s*)(.*)$#', '\\1'.str_repeat('<'.$group.' depth="'.$depth.'" b="'.(strlen($b1)+1).'">', ( (strlen($b1)+1) - $depth ) ).'\\2', $nl);
					}
					else{
						$lines[$i-1] = preg_replace('#$(.*)(\s*)$#', '\\1'.str_repeat('</'.$group.'>', ( $depth - (strlen($b1)+1) ) ).'\\2', $lines[$i-1]);
						$lines[$i] = $nl;
					}
					$depth = (strlen($b1) + 1);
				}
				else{
					$lines[$i] = $nl;
				}
			}
			else{
				$lines[$i-1] = preg_replace('#^(.*)(\s*)$#', '\\1'.str_repeat('</'.$group.'>', ($depth - $spacer)).'\\2', $lines[$i-1]);
				$depth = 0;
			}
		}
		/*fix last line*/ if(!($group===NULL) && $depth > 0){ $lines[$i] = preg_replace('#^(.*)(\s*)$#', '\\1'.str_repeat('</'.$group.'>', $depth).'\\2', $lines[$i]); }
		$str = implode("\n", $lines);
		return $str;
	}
	
	function strip_all_html($str=NULL){
		$str = preg_replace('#\<[\/]?[^\>]+\>#i', '', $str);
		return $str;
	}
	function encode_clean($str=NULL){
		return $str;
		return str_replace(array('�',"\r",'¤'), array('','',''), $str);
	}
	
	/*OTHER*/
	function str(){ return $this->get_template(); }
	function TOC($str=NULL){ //$str=NULL
		$toc = NULL; $depth = 0; $spacer = 0;
		//$toc = 'TABLE OF CONTENTS';
		//print_r($this);
		//if($str === NULL && isset($this)){
			$str = $this->get_template();
			$str = $this->decode_headers($str);
		//} else { $str = '<h5>error</h5>'; }
		//$toc .= ' ('.strlen($str).')';
		if(preg_match_all('#<h([1-6])( id="([^"]+)")?>([^<]+)</h[1-6]>#i', $str, $buffer) > 0){
			//$toc .= '<br/>opties: <br/>';
			foreach($buffer[4] as $a=>$h){
				if($depth == 0){ $spacer = $depth = ($buffer[1][$a] - 1); }
				if($buffer[1][$a] != $depth){
					if($buffer[1][$a] > $depth){
						$toc .= str_repeat('<ul>', ( $buffer[1][$a] - $depth ) );
					}
					else{
						$toc .= str_repeat('</ul>', ( $depth - $buffer[1][$a] ) );
					}
					$depth = $buffer[1][$a];
				}
				$toc .= '<li><a href="#'.$buffer[3][$a].'" onclick="ganaar(\''.$buffer[3][$a].'\', \'none\', 80);">'.$h.'</a></li>';
			}
			if($depth != 0){ $toc .= str_repeat('</ul>', ($depth - $spacer) ); }
		}
		return $toc;
	}
}
?>