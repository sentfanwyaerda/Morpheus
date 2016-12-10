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
	function Mail_Message(){ $this->initialize(); }
	function initialize(){
		$this->mailer = new \PHPMailer;
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
	
	function to($email, $name=NULL){ $this->mailer->addAddress($email, $name); }
	function cc($email, $name=NULL){ $this->mailer->addCC($email, $name); }
	function bcc($email, $name=NULL){ $this->mailer->addBCC($email, $name); }
	function reply_to($email, $name=NULL){ $this->mailer->addReplyTo($email, $name); }
	function from($email, $name=NULL){ $this->mailer->setFrom($email, $name); }
	function subject($subject=NULL){ $this->subject = $subject; }
	
	function send($debug=FALSE){
		$this->mailer->Subject = $this->subject;
		$this->mailer->Body = $this->__toString();
		$this->mailer->AltBody = \Morpheus\Markdown::strip_all_html( $this->__toString() );
		$res = $this->mailer->send();
		return ($debug !== FALSE ? (!$res ? $this->mailer->ErrorInfo : TRUE) : $res);
	}
}
?>