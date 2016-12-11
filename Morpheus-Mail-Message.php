<?php
namespace Morpheus;

require_once(dirname(__FILE__).'/Morpheus.php');
require_once(dirname(__FILE__).'/Morpheus-Markdown.php'); //required for ::send( $AltBody )
if(!class_exists('PHPMailer')){
	require_once(dirname(dirname(__FILE__)).'/PHPMailer/PHPMailerAutoload.php');
}

class Mail_Message extends \Morpheus {
	var $subject = NULL;
	var $mailer;
	var $skin;
	function Mail_Message(){ $this->initialize(); }
	function initialize(){
		if(!is_object($this->mailer)){
			if(!defined('OAUTH')){ $this->mailer = new \PHPMailer; }
			else{
				$this->mailer = new \PHPMailerOAuth;
				$this->mailer->AuthType = 'XOAUTH2';
				if(defined('OAUTH_USER_EMAIL')){ $this->mailer->oauthUserEmail = OAUTH_USER_EMAIL; }
				if(defined('OAUTH_CLIENT_ID')){ $this->mailer->oauthClientId = OAUTH_CLIENT_ID; }
				if(defined('OAUTH_CLIENT_SECRET')){ $this->mailer->oauthClientSecret = OAUTH_CLIENT_SECRET; }
				if(defined('OAUTH_REFRESH_TOKEN')){ $this->mailer->oauthRefreshToken = OAUTH_REFRESH_TOKEN; }
			}
		}
		if(defined('SMTP_HOST')){
			$this->mailer->isSMTP();
			$this->mailer->Host = SMTP_HOST;
			if(defined('SMTP_USER')){
				$this->mailer->SMTPAuth = TRUE;
				$this->mailer->Username = SMTP_USER;
				if(defined('SMTP_PASSWORD')){ $this->mailer->Password = SMTP_PASSWORD; }
			}
			if(defined('SMTP_ENCRYPTION')){ $this->mailer->SMTPSecure = (in_array(strtolower(SMTP_ENCRYPTION), array('tls','ssl')) ? SMTP_ENCRYPTION : 'tls'); }
			if(defined('SMTP_PORT')){ $this->mailer->Port = SMTP_PORT; }
		}
	}
	
	function to($email, $name=NULL){
		if(!is_object($this->mailer)){ $this->initialize(); }
		$this->mailer->addAddress($email, $name);
	}
	function cc($email, $name=NULL){
		if(!is_object($this->mailer)){ $this->initialize(); }
		$this->mailer->addCC($email, $name);
	}
	function bcc($email, $name=NULL){
		if(!is_object($this->mailer)){ $this->initialize(); }
		$this->mailer->addBCC($email, $name);
	}
	function reply_to($email, $name=NULL){
		if(!is_object($this->mailer)){ $this->initialize(); }
		$this->mailer->addReplyTo($email, $name);
	}
	function from($email, $name=NULL){
		if(!is_object($this->mailer)){ $this->initialize(); }
		$this->mailer->setFrom($email, $name);
	}
	function attachment($src, $filename=NULL){
		if(!is_object($this->mailer)){ $this->initialize(); }
		$this->addAttachment($src, $filename);
	}
	function subject($subject=NULL){ $this->subject = $subject; }
	function body($str=NULL){ $this->set_template($str); }
	function set_skin($skin=NULL){ $this->skin = $skin; }
	
	function send($debug=FALSE){
		if(!is_object($this->mailer)){ $this->initialize(); }
		$this->mailer->Subject = (string) $this->subject;
		$this->mailer->isHTML(TRUE);
		$body = $this->__toString();
		if(is_object($this->skin)){ $this->skin->CONTENT = $body; }
		$this->mailer->Body = (is_object($this->skin) ? (string) $this->skin : $body );
		$this->mailer->AltBody = \Morpheus\Markdown::strip_all_html( $body );
		$res = $this->mailer->send();
		return ($debug !== FALSE ? (!$res ? $this->mailer->ErrorInfo : TRUE) : $res);
	}
}
?>