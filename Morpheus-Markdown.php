<?php
namespace Morpheus;
class Markdown extends \Morpheus {
	function Markdown($str=NULL){}
	
	function _encode_order(){ return array_reverse(self::_decode_order()); }
	function _decode_order(){ return array('bold','italic','strikethrough', 'underline'); }
	
	/* Encode: HTML to Markdown*/
	function encode($html=NULL){}
	
	/* Decode: Markdown to HTML */
	function decode($md=NULL){}
	
	/* ELEMENTS */
	function encode_bold($str=NULL){ return self::_encode_tag_only($str, 'strong', '**', '**', array('b','strong') ); }
	function decode_bold($str=NULL){
		$tag = 'strong'; //b|strong
		$str = preg_replace('#(^|\s)[\*]{2}([^\*]+)[\*]{2}(\s|$)#', '\\1<'.$tag.'>\\2</'.$tag.'>\\3', $str);
		return $str;
	}
	
	function encode_italic($str=NULL){ return self::_encode_tag_only($str, 'em', '*', '*', array('i','em') ); }
	function decode_italic($str=NULL){
		$tag = 'em'; //i|em
		$str = preg_replace('#(^|\s)[\*]{1}([^\*]+)[\*]{1}(\s|$)#', '\\1<'.$tag.'>\\2</'.$tag.'>\\3', $str);
		return $str;
	}
	
	function encode_strikethrough($str=NULL){ return self::_encode_tag_only($str, 's', '~', '~' ); }
	function decode_strikethrough($str=NULL){
		$tag = 's'; //s
		$str = preg_replace('#(^|\s)[\~]{1}([^\~]+)[\~]{1}(\s|$)#', '\\1<'.$tag.'>\\2</'.$tag.'>\\3', $str);
		return $str;
	}
	
	function encode_underline($str=NULL){ return self::_encode_tag_only($str, 'u', '_', '_'); }
	function decode_underline($str=NULL){ return self::_decode_tag_only($str, 'u', '_', '_'); }

	
	/* Helper Functions */
	function _encode_tag_only($str=NULL, $tag='', $prefix='', $postfix='', $options=array()){
		//$options = array('i', 'em');
		if(!is_array($options) || count($options) == 0){ $options = array($tag); }
		foreach($options as $i=>$t){
			$str = str_replace('</'.$t.'>', '¤', $str);
			$str = preg_replace('#(^|\s)<'.$t.'>([^¤]+)[¤](\s|$)#', '\\1'.$prefix.'\\2'.$postfix.'\\3', $str);
			$str = str_replace('¤', '</'.$t.'>', $str);
		}
		return $str;
	}
	function _decode_tag_only($str=NULL, $tag='', $prefix='', $postfix='', $options=array()){
		$str = preg_replace('#(^|\s)'.\Morpheus::escape_preg_chars($prefix).'([^\_]+)'.\Morpheus::escape_preg_chars($postfix).'(\s|$)#', '\\1<'.$tag.'>\\2</'.$tag.'>\\3', $str);
		return $str;
	}
}
?>