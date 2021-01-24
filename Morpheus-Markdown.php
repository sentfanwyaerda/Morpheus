<?php
namespace Morpheus;

require_once(dirname(__FILE__).'/Morpheus.php');

class Markdown extends \Morpheus {
	function Markdown($a=NULL, $b=array(), $c=FALSE){
		$this->__construct($a, $b, $c);
	}

	function __toString(){
		if(isset($this) && isset($this->_template) ){
			if($this->_template === NULL && file_exists($this->_src)){ $this->_template = $this->load_template($this->_src, FALSE, 0); }
			if(is_object($this->_template) && method_exists($this->_template, '__toString')){
				$this->inception($this->_template);
				$res = (string) $this->_template;
				$res = $this->decode($res);
				$res = self::strip_tags(self::parse($res, $this));
				\Morpheus::notify(__METHOD__.'.inception');
				return $res;
			}
			else{
				//$res = self::strip_tags(self::parse($this->_template, $this));
				$res = $this->_template;
				$res = $this->decode($res);
				$res = self::strip_tags(self::parse($res, $this));
				\Morpheus::notify(__METHOD__, array_merge((isset($this->_src) ? array('src'=>$this->_src) : array()), (isset($this->_domain) ? array('domain'=>$this->_domain) : array()), array('length'=>strlen($this->_template),'sha1'=>sha1($this->_template),'decoded:length'=>strlen($res),'decoded:sha1'=>sha1($res),'tags'=>count($this->_tag))) );
				return $res;
			}
			//return $this->mustache($this->_template, $this);
		} else {
			\Morpheus::notify(__METHOD__);
			return '';
		}
	}
	
	function _encode_order(){ return array_reverse(self::_decode_order()); }
	function _decode_order(){ return array('clean',
        'bold',
        'italic',
        'strikethrough',
        'form_marked',
        'form_simplified',
        'form_lists',
        'inline_code',
        'syntax_highlighting',
        /*'underline',*/
        'link',
        'image',
        'headers',
        'horizontal_rule',
        'blockquote',
        'lists',
        'task_done',
        'table',
        'p_br',
        'clean'); }
	
	/* Encode: HTML to Markdown*/
	function encode($html=FALSE, $set=array()){
		if($html === FALSE && isset($this)){ $html = $this->_template; }
		$md = $html;
		foreach(self::_encode_order() as $i=>$el){
			$cur = 'encode_'.strtolower($el);
			if(method_exists($this, $cur)){ $md = $this->$cur($md, $set); }
		}
		return $md;
	}
	
	/* Decode: Markdown to HTML */
	function decode($md=FALSE, $set=array()){
		if($md === FALSE && isset($this)){ $md = $this->_template; }
		$html = $md;
		foreach(self::_decode_order() as $i=>$el){
			$cur = 'decode_'.strtolower($el);
			if(method_exists($this, $cur)){ $html = $this->$cur($html, $set); }
		}
		return $html;
	}
	
	/* ELEMENTS */
	function encode_bold($str=NULL, $set=array()){ return self::_encode_tag_only($str, 'strong', '**', '**', array('b','strong') ); }
	function decode_bold($str=NULL, $set=array()){ return self::_decode_tag_only($str, 'strong', '**', '**', array('b','strong') ); }
	
	function encode_italic($str=NULL, $set=array()){ return self::_encode_tag_only($str, 'em', '*', '*', array('i','em') ); }
	function decode_italic($str=NULL, $set=array()){ return self::_decode_tag_only($str, 'em', '*', '*', array('i','em') ); }
	
	function encode_strikethrough($str=NULL, $set=array()){ return self::_encode_tag_only($str, 's', '~~', '~~' ); }
	function decode_strikethrough($str=NULL, $set=array()){ return self::_decode_tag_only($str, 's', '~~', '~~' ); }
	
	function encode_underline($str=NULL, $set=array()){ return self::_encode_tag_only($str, 'u', '_', '_'); }
	function decode_underline($str=NULL, $set=array()){ return self::_decode_tag_only($str, 'u', '_', '_'); }

	function encode_link($str=NULL, $set=array()){
		$str = preg_replace('#\<a href\=\"([^\"]+)\"\>([^\<]+)\<\/a\>#', '[\\2](\\1)', $str);
		return $str;
	}
	function decode_link($str=NULL, $set=array()){
		$str = preg_replace('#(^|[^\!])[\[]([^\]]+)[\]][\(]([^ \)]+)\s([\"]([^\"\)]+)[\"])[\)]#', '\\1<a href="\\3" title="\\5">\\2</a>', $str);
		$str = preg_replace('#(^|[^\!])[\[]([^\]]+)[\]][\(]([^\)]+)[\)]#', '\\1<a href="\\3">\\2</a>', $str);
		$str = preg_replace('#\<((http[s]?\:\/\/|mailto\:)([^ \>]+))\>#', '<a href="\\1">\\3</a>', $str);
		return $str;
	}
	
	function encode_image($str=NULL, $set=array()){
		$str = preg_replace('#\<img src\=\"([^\"]+)\" title=\"([^\"]+)\"\s*\/\>#', '![\\2](\\1)', $str);
		return $str;
	}
	function decode_image($str=NULL, $set=array()){
		$str = preg_replace('#[\!][\[]([^\]]+)[\]][\(]([^\)]+)[\)]#', '<img src="\\2" title="\\1" \/>', $str);
		return $str;
	}

	function encode_headers($str=NULL, $set=array()){
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
	function decode_headers($str=NULL, $set=array()){
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
	
	function encode_horizontal_rule($str=NULL, $set=array()){
		$str = preg_replace('#(\s*)<hr/>(\s*)#i', '\\1----------\\2', $str);
		return $str;
	}
	function decode_horizontal_rule($str=NULL, $set=array()){
		$str = preg_replace('#(^|\n)([\*][ ]?[\*][ ]?[\*][\* ]*)(\n|$)#', '\\1<hr/>\\3', $str);
		$str = preg_replace('#(^|\n)([-][ ]?[-][ ]?[-][ -]*)(\n|$)#', '\\1<hr/>\\3', $str);
		return $str;
	}
	
	function encode_inline_code($str=NULL, $set=array()){ return self::_encode_tag_only($str, 'code', '`', '`' ); }
	function decode_inline_code($str=NULL, $set=array()){ return self::_decode_tag_only($str, 'code', '`', '`' ); }
	
	function encode_syntax_highlighting($str=NULL, $set=array()){
		return $str;
	}
	function decode_syntax_highlighting($str=NULL, $set=array()){
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
	
	function encode_blockquote($str=NULL, $set=array()){ return $str; }
	function decode_blockquote($str=NULL, $set=array()){ return self::_decode_prefixed_line($str, '[\>]\s', NULL, 'blockquote'); }
	
	function encode_table($str=NULL, $set=array()){ return $str; }
	function decode_table($str=NULL, $set=array()){
		$tnr = $tstr = $hdr = array();
		$lines = explode("\n", $str);
		foreach($lines as $i=>$line){
			if(preg_match_all('#(^|\s|[\-\:])[\|]{1,}(\s|[\-\:]|$)#', $line, $buffer) >= 2){
				$tnr[] = $i; $tstr[$i] = $buffer[0];
				if(preg_match('#^\s*[\|]?\s?[:]?[\-]{3,}[\:]?\s?([\|]\s?[:]?[\-]{3,}[\:]?\s?)*[\|]?\s*$#', $line)){
					$hdr[$i] = $line;
				}
			}
		}
		//*debug*/ print '<!-- tnr='.print_r($tnr, TRUE).' tstr='.print_r($tstr, TRUE).' hdr='.print_r($hdr, TRUE).' -->';
		foreach($lines as $i=>$line){
			if(in_array($i, $tnr) && !isset($hdr[$i])){
				$lines[$i] = (!in_array($i-1, $tnr) || (isset($hdr[$i-1]) && !in_array($i-2, $tnr)) ? '<table>' : NULL).'<tr>'.self::_table_decode_line($line, self::_table_decode_middle($hdr[self::_table_line_type($i, $tnr, array_keys($hdr), FALSE)]), self::_table_line_type($i, $tnr, array_keys($hdr))).'</tr>'.(!in_array($i+1, $tnr) ? '</table>' : NULL);
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
		$str = $line; $resstr = NULL;
		//*fix*/ $str = preg_replace('#\s?[\|]\s*$#', '', $str);
		/*fix*/ $str = preg_replace('#^\s*[\|]\s?#', '', $str);
		preg_match_all('#([^\|]+)(\s?[\|]{1,}\s?|$)#', $str, $buffer);
		//*debug*/ print '<!-- '.$str.' #='.print_r($buffer, TRUE).' -->';
		$add = 0;
		foreach($buffer[1] as $i=>$block){
			$debug = NULL;
			//$debug = '<!-- '.$buffer[1][$i].' -->'.'<!-- '.$buffer[2][$i].' -->'.'<!-- '.$align[$i].' -->';
			//$str = str_replace($buffer[0][$i], '<'.$el.($align[$i+$add] !== NULL ? ' align="'.$align[$i+$add].'"' : NULL).(strlen(trim($buffer[2][$i])) >= 2 ? ' colspan="'.strlen(trim($buffer[2][$i])).'"' : NULL).'>'.trim($block).$debug.'</'.$el.'>', $str);
			$resstr .= '<'.$el.($align[$i+$add] !== NULL ? ' align="'.$align[$i+$add].'"' : NULL).(strlen(trim($buffer[2][$i])) >= 2 ? ' colspan="'.strlen(trim($buffer[2][$i])).'"' : NULL).'>'.trim($block).$debug.'</'.$el.'>';
			$add += (strlen(trim($buffer[2][$i])) >= 2 ? strlen(trim($buffer[2][$i]))-1 : 0);
		}
		return $resstr;
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
	
	function encode_lists($str=NULL, $set=array()){ return $str; }
	function decode_lists($str=NULL, $set=array()){
		$str = self::_decode_prefixed_line($str, '[\*\-\+]\s', 'li', 'ul');
		$str = self::_decode_prefixed_line($str, '[0-9]+[\.]\s', 'li', 'ol');
		return $str;
	}
	
	function encode_task_done($str=NULL, $set=array()){ return $str; }
	function decode_task_done($str=NULL, $set=array()){
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
	
	function encode_p_br($str=NULL, $set=array()){
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
	function decode_p_br($str=NULL, $set=array()){
		$lines = explode("\n", $str); $open = TRUE;
		foreach($lines as $i=>$line){
			if(preg_match('#^\s*$#', $line)){ $open = TRUE; }
			else{
				preg_match('#^(\s*)([\<]([a-z0-9]+)([^\>]+)?[\>])?#', $line, $buffer);
				if(isset($buffer[3])){switch(strtolower($buffer[3])){
					case 'h1': case 'h2': case 'h3': case 'h4': case 'h5': case 'h6': case 'hr': case 'ol': case 'ul': case 'li': case 'blockquote': case 'pre': case 'table': case 'tr': case 'th': case 'td': case 'style': case 'script': break;
					default: //!isset($lines[$i-1]) || preg_match('#^\s*$#', $lines[$i-1]) ||  
						//&& (!isset($lines[$i+1]) || preg_match('#^\s*$#', $lines[$i+1]))
						if(!($open === FALSE) ){
							$lines[$i] = $buffer[1].($open === TRUE ? '<p>' : NULL).preg_replace('#^'.$buffer[1].'#', '', $lines[$i]).((!isset($lines[$i+1]) || preg_match('#^\s*$#', $lines[$i+1])) ? '</p>' : '<br/>');
							$open = (!((!isset($lines[$i+1]) || preg_match('#^\s*$#', $lines[$i+1]))) ? NULL : FALSE);
						}
				}}
			}
		}
		$str = implode("\n", $lines);
		/*fix*/ $str = str_replace('<br/></p>', '</p>', $str);
		return $str;
	}
	/* Markdown FORM */
	
	function encode_form_marked($str=NULL, $set=array()){ return $str; }
    function decode_form_marked($md=NULL, $set=array()){
      //*debug*/ print_r($set);
      //syntax of marked-forms https://www.npmjs.com/package/marked-forms
      /**************************************
       * [text ?input?](name)
       * [Provide a Naem ??]()
       * [Different label ??]()
       * [Choos one ?select?](nme)
       * - option 1 "val1"
       * - option 2 "val2"
       * [?checklist?](name)
       * - check1
       * - check2
       * [?radiolist?M](name)
       * - check1
       * - check2
       * [Label Text ??H](foo)
       * [?submit? Submit text](- "class1 class2")
       **************************************/
      $buttontypes = 'submit|button|reset';
      $inputtypes = 'input|text|phone|url|email|password';
      preg_match_all('#\[([^\]\?]*)\?(|'.$inputtypes.'|'.$buttontypes.'|textarea|select|checklist|radiolist)\?(|\*|M|M\*|H)( [^\]\n]+)?\]\(([a-z-]+)?[ ]?(\"[^\"]+\")?\)((\n\- [^\n]+)*)#i', $md, $buffer);
      //*debug*/ print 'marked-forms: '; print_r($buffer);
      foreach($buffer[0] as $i=>$item){
        $mfstr = NULL;
        $mode = strtolower($buffer[2][$i]); /*fix*/ if(in_array($mode, array('input','',NULL))){ $mode = 'text'; }
        $name = (isset($buffer[5][$i]) && $buffer[5][$i] != "-" ? $buffer[5][$i] : preg_replace('#[^a-z0-9]#', '-', strtolower(trim($buffer[1][$i]).trim($buffer[4][$i]))) );
        $value = (isset($set[$name]) ? $set[$name] : NULL);
        //*debug*/ print '<pre>??: '.$name." = ".$value.'<pre>';
        if(strlen($buffer[1][$i])>0 && !in_array($mode, explode('|', $buttontypes))){
          $mfstr .= '<label for="'.$name.'">'.$buffer[1][$i].'</label>';
        }
        switch($mode){
          case 'checklist': case 'radiolist':
            $mfstr .= 'LIST';
            break;
          case 'select':
            $mfstr .= '<select id="'.$name.'" name="'.$name.'"></select>';
            break;
          case 'textarea':
            $mfstr .= '<textarea id="'.$name.'" name="'.$name.'">'.$value.'</textarea>';
            break;
          default:
            $mfstr .= '<input type="'.(in_array($mode, explode('|', $inputtypes.'|'.$buttontypes)) ?  $mode : 'text').'" id="'.$name.'" name="'.$name.'" value="'.(in_array($mode, explode('|', $buttontypes)) ? trim($buffer[4][$i]) : $value).'"'.(strlen($buffer[6][$i]) > 3 ? ' class="'.substr($buffer[6][$i],1,-1).'"' : NULL).' />';
        }
        if(strlen($buffer[4][$i])>0 && !in_array($mode, explode('|', $buttontypes))){ $mfstr .= '<label for="'.$name.'">'.$buffer[4][$i].'</label>'; }
        //$mfstr .= "\n";
        $md = str_replace($buffer[0][$i], $mfstr, $md);
      }
      return $md;
    }
	function encode_form_simplified($str=NULL, $set=array()){ return $str; }
    function decode_form_simplified($md=NULL, $set=array()){
      //syntax of (..)
      /**************************************
       * Naam*: ________
       * Email address: email*=________(email)
       * Choose One: {val1, (val2)}
       **************************************/
      preg_match_all('#(^|\n)([^:\n]+)[\:]\s+([a-z]+[\[]?[\]]?[\*]?\=)?([\_]{3,}|\{[^\}\n]+\})(\(([^\)\n]+)\))?([^\n]*)#i', $md, $buffer);
      //*debug*/ print 'inline form: '; print_r($buffer);
      foreach($buffer[2] as $i=>$label){
        $required = FALSE;
        $name = (isset($buffer[3][$i]) && substr($buffer[3][$i],-1) == '=' ? substr($buffer[3][$i],0,-1) : str_replace(' ','-', strtolower(trim($label))) );
        if(substr($label, -1) == '*'){ $required = TRUE; $label = substr($label, 0, -1).'<span class="required">*</span>'; }
        if(substr($name, -1) == '*'){ $name = substr($name, 0, -1); if($required !== TRUE){ $label = $label.'<span class="required">*</span>';} $required = TRUE; }
        $multiple = FALSE; if(substr($name, -2) == '[]'){ $name = substr($name, 0, -2); $multiple = TRUE; }
        $value = (isset($set[$name]) ? $set[$name] : NULL);
        //*debug*/ print '<pre>__: '.$name." = ".$value.'<pre>';
        $patch = "\n".'<label for="'.$name.'"><span class="label">'.$label.'</span> ';
        switch(substr($buffer[4][$i],0,1)){
          case '{':
            $slist = (isset($set[$name.'-options']) && is_array($set[$name.'-options']) ? $set[$name.'-options'] : explode(',', substr($buffer[4][$i],1,-1)) );
            $patch .= '<select id="'.$name.'" name="'.$name.'">'; //'.($required == TRUE ? ' required="REQUIRED"' : NULL).'
            foreach($slist as $j=>$sitem){
              /*fix*/ $sitem = trim($sitem);
              $selected = FALSE; if(substr($sitem,0,1)=='(' && substr($sitem,-1)==')'){ $selected = TRUE; $sitem = substr($sitem, 1, -1); }
              preg_match('#^([a-z]+\=)?(.*)$#i', $sitem, $sbuf);
              $ovalue = (isset($sbuf[1]) && substr($sbuf[1],-1)=='=' ? substr($sbuf[1],0,-1) : $sbuf[2]);
              if(isset($set[$name])){ $selected = FALSE; if($set[$name] == $ovalue){ $selected = TRUE; }}
              $patch .= '<option value="'.$ovalue.'"'.($selected ? ' selected="SELECTED"' : NULL).'>'.$sbuf[2].'</option>';
            }
            $patch .= '</select>';
            break;
          case '_': default:
            $patch .= '<input type="'.(isset($buffer[6][$i]) && strlen($buffer[6][$i]) > 0 ? $buffer[6][$i]  : 'text').'" id="'.$name.'" name="'.$name.'" value="'.$value.'"'.($required == TRUE ? ' required="REQUIRED"' : NULL).($multiple == TRUE ? ' multiple="MULTIPLE"' : NULL).'/>';
        }
        $patch .= '</label><br/>'."\n";
        $md = str_replace($buffer[0][$i], $patch, $md);
      }
      return $md;
    }
	function encode_form_lists($str=NULL, $set=array()){ return $str; }
    function decode_form_lists($md=NULL, $set=array()){
      //syntax of (..)
      /**************************************
       * - [ ] Option 1
       * - [*] Option 2
       * - option=[x](true) Option selected
       * - ( ) Option a
       **************************************/
      $pattern = '(^|\n)\- ([a-z]+=)?(\[|\()(| |x|\*)(\]|\))(\([^\)\n]+\))? ([^\n]+)';
      preg_match_all('#('.$pattern.'){1,}#i', $md, $buffer);
      //*debug*/ print 'inline checkbox and radio: '; print_r($buffer);
      foreach($buffer[0] as $i=>$group){
        $mode = ($buffer[4][$i] == '[' && $buffer[6][$i] == ']' ? 'checkbox' : ($buffer[4][$i] == '(' && $buffer[6][$i] == ')' ? 'radio' : NULL) );
        $setstr = NULL;
        if($mode !== NULL){
          $wellformed = TRUE;
          $name = $mode.'-'.$i;
          preg_match_all('#'.$pattern.'#', $group, $b);
          //*debug*/ print $i.' ('.$mode.' , '.$name.'): '; print_r($b);
          foreach($b[0] as $j=>$raw){
            if(strlen($b[2][$j]) > 1){ $name = substr($b[2][$j],0,-1); }
            switch($mode){
              case 'checkbox': if(!($b[3][$j] == '[' && $b[5][$j] == ']')){ $wellformed = FALSE; } break;
              case 'radio': if(!($b[3][$j] == '(' && $b[5][$j] == ')')){ $wellformed = FALSE; } break;
            }
            $value = (strlen($b[6][$j]) > 2 ? substr($b[6][$j],1,-1) : $b[7][$j]);
            $label = $b[7][$j];
            $setstr .= '<li class="'.$mode.'"><input id="'.$name.'-'.$j.'" type="'.$mode.'" ';
            $setstr .= 'name="'.$name.(count($b[0])>1 && $mode == 'checkbox' ? '[]' : NULL).'" ';
            $setstr .= 'value="'.$value.'"';
            //$setstr .= (in_array($b[4][$j], array('x','*')) ? ' checked="CHECKED"' : NULL);
            $setstr .= ((isset($set[$name]) ? (is_array($set[$name]) ? in_array($value, $set[$name]) : $set[$name] == $value) : in_array($b[4][$j], array('x','*')) ) ? ' checked="CHECKED"' : NULL);
            $setstr .= ' /><label class="'.$mode.'" for="'.$name.'-'.$j.'">'.$label.'</label></li>';
          }
          if($wellformed === TRUE){
            $setstr = '<ul id="'.$name.'" class="'.$mode.'list">'.$setstr.'</ul>';
            $md = str_replace($group, $setstr, $md);
          }
        }
      }
      return $md;
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
		$lines = explode("\n", $str); $depth = $spacer = 0;
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
				if(isset($lines[$i-1])){ $lines[$i-1] = preg_replace('#^(.*)(\s*)$#', '\\1'.str_repeat('</'.$group.'>', ($depth - $spacer)).'\\2', $lines[$i-1]); }
				$depth = 0;
			}
		}
		/*fix last line*/ if(!($group===NULL) && $depth > 0){ $lines[$i] = preg_replace('#^(.*)(\s*)$#', '\\1'.str_repeat('</'.$group.'>', $depth).'\\2', $lines[$i]); }
		$str = implode("\n", $lines);
		return $str;
	}
	
	function strip_all_html($str=NULL, $set=array()){
		$str = preg_replace('#\<[\/]?[^\>]+\>#i', '', $str);
		return $str;
	}
	function encode_clean($str=NULL, $set=array()){
		return $str;
		return str_replace(array('�',"\r",'¤'), array('','',''), $str);
	}
	
	/*OTHER*/
	function str(){ return $this->get_template(); }
	function TOC($str=NULL, $set=array()){
		$db = self::section_database($str);
		//return '<pre>'.print_r($db, TRUE).'</pre>';
		$toc_html = NULL; $toc_md = NULL; $depth = 0; $spacer = 0;
		foreach($db as $line=>$h){
			if($depth == 0){ $spacer = $depth = ($h['depth'] - 1); }
			if($h['depth'] != $depth){
				if($h['depth'] > $depth){
					$toc_html .= str_repeat('<ul>', ( $h['depth'] - $depth ) );
				}
				else{
					$toc_html .= str_repeat('</ul>', ( $depth - $h['depth'] ) );
				}
				$depth = $h['depth'];
			}
			$toc_html .= '<li><a href="#'.$h['hash'].'" onclick="ganaar(\''.$h['hash'].'\', \'none\', 80);">'.(isset($h['assigned']) ? $h['assigned'] : NULL).$h['title'].'</a></li>';
			$toc_md .= str_repeat("\t", ($h['depth'] - 1) ).'* ['.(isset($h['assigned']) ? $h['assigned'] : NULL).$h['title'].'](#H'.$h['hash'].')'."\n";
		}
		if($depth != 0){ $toc_html .= str_repeat('</ul>', ($depth - $spacer) ); }
		return self::decode("\n".$toc_md."\n");
		//return $toc_html;
	}
	function section_database($str=NULL, $set=array()){
		$db = array(); $line = 0; $prev = FALSE;
		if($str === NULL && isset($this)){
			$str = $this->get_template();
		}
		/*fix*/ $str = preg_replace('#(^|\n)([^\n]+)\n[\=]{3,}\n#', '\\1# \\2\n\n', $str);
		/*fix*/ $str = preg_replace('#(^|\n)([^\n]+)\n([\-]{3,}|\<hr\/\>)\n#', '\\1## \\2\n\n', $str);
		$buf = explode("\n", $str);
		foreach($buf as $nr=>$line){
			if(preg_match("#^\s*([\#]+)\s([0-9][0-9.]*[\.\)]\s)?(.*)#i", $line, $z)){
				$db[$nr] = array('line'=>$nr,'depth'=>strlen($z[1]),'title'=>$z[3],'hash'=>'H'.substr(md5($z[2].$z[3]), 0, 15));
				if(strlen($z[2]) > 0){ $db[$nr]['assigned'] = $z[2]; }
				if($prev !== FALSE && isset($db[$prev])){ $db[$prev]['end'] = $nr-1; }
				$prev = $nr;
			}
		}
		if($prev !== FALSE && isset($db[$prev])){ $db[$prev]['end'] = count($buf)-1; $prev = FALSE; }
		return $db;
	}
}

function Markdown_decode($str, $set=array()){
    $morph = new \Morpheus\Markdown();
    return $morph->decode($str, $set);
}
function Markdown_encode($str, $set=array()){
    $morph = new \Morpheus\Markdown();
    return $morph->encode($str, $set);
}
?>
