<?php
namespace Morpheus;

require_once(dirname(__FILE__).'/Morpheus.php');

class Mail_Message extends \Morpheus {
	var $subject = NULL;
	var $_header = array();
	function Mail_Message(){
		$this->set_header('MIME-Version','1.0');
		$this->set_header('Content-type','text/html; charset=iso-8859-1');
	}
	function set_header($name, $value, $reform_to_array=TRUE){
		$this->_header[$name] = ( $reform_to_array === TRUE && isset($this->_header[$name]) ? array_merge((is_array($this->_header[$name]) ? $this->_header[$name] : array($this->_header[$name])), (is_array($value) ? $value : array($value) )) : $value );
		return TRUE;
	}
	function _generate_headers($blob=FALSE, $exclude=array()){
		if($blob === FALSE || !is_array($blob)){ $blob = $this->_header; }
		$str = NULL;
		foreach($blob as $name=>$value){
			if(!in_array(strtolower($name), $exclude)){
				if(is_array($value)){
					switch(strtolower($name)){
						case 'to': case 'cc': case 'bcc': case 'reply-to': case 'from':
							$str .= $name.': '.implode(', ', $value)."\r\n";
							break;
						default:
							foreach($value as $i=>$v){
								$str .= $name.': '.$v."\r\n";
							}
					}
				}
				else{ $str .= $name.': '.$value."\r\n"; }
			}
		}
		return $str;
	}
	function _write_mailaddress($email, $name=NULL){
		if($name === NULL){
			return $email;
		} else {
			return (preg_match('#\s#', $name) ? '"'.$name.'"' : $name).' <'.$email.'>';
		}
	}
	
	function to($email, $name=NULL){ $this->set_header('To', $this->_write_mailaddress($email, $name) ); }
	function cc($email, $name=NULL){ $this->set_header('Cc', $this->_write_mailaddress($email, $name) ); }
	function bcc($email, $name=NULL){ $this->set_header('Bcc', $this->_write_mailaddress($email, $name) ); }
	function reply_to($email, $name=NULL){ $this->set_header('Reply-To', $this->_write_mailaddress($email, $name), FALSE ); }
	function from($email, $name=NULL){ $this->set_header('From', $this->_write_mailaddress($email, $name), FALSE ); }
	function subject($subject=NULL){ $this->subject = $subject; }
	
	function send(){
		$headers = $this->_generate_headers(FALSE, array('to'));
		$to = $this->_header['To'];
		$subject = $this->_subject;
		$body = $this->__toString();
		print 'to: '; print_r($to); print "\nsubject: "; print_r($subject); print "\nbody: "; print_r($body); print "\nheaders: "; print_r($haders); print "\n\n";
		//return mail($to, $subject, $body, $headers);
		return TRUE;
	}
}
?>