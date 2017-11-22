<?php namespace Model\Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\POP3;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Email{
	protected $options;
	protected $messaggio;
	protected $main_address_set;

	public function __construct($subject, $text, array $opt = [], $check_retro = null){
		if($check_retro!==null){
			throw new \Exception('Email class has now only 3 parameters! Address in constructor is deprecated.');
		}

		$this->options = array_merge([
			'from_name' => defined('EMAILS_DEFAULT_NAME') ? EMAILS_DEFAULT_NAME : '',
			'from_mail' => defined('EMAILS_DEFAULT_SENDER') ? EMAILS_DEFAULT_SENDER : '',
			'smtp' => defined('EMAILS_SMTP') ? EMAILS_SMTP : false,
			'port' => defined('EMAILS_SMTP_PORT') ? EMAILS_SMTP_PORT : 25,
			'header' => defined('EMAILS_HEADER') ? EMAILS_HEADER : '',
			'footer' => defined('EMAILS_FOOTER') ? EMAILS_FOOTER : '',
			'debug' => false,
			'username' => defined('EMAILS_SMTP_USERNAME') ? EMAILS_SMTP_USERNAME : false,
			'password' => defined('EMAILS_SMTP_PASSWORD') ? EMAILS_SMTP_PASSWORD : false,
			'encryption' => defined('EMAILS_ENCTYPTION') ? EMAILS_ENCTYPTION : false,
			'charset' => 'UTF-8',
		], $opt);
		$this->main_address_set = false;

		require_once(__DIR__.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.'phpmailer'.DIRECTORY_SEPARATOR.'PHPMailer.php');
		require_once(__DIR__.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.'phpmailer'.DIRECTORY_SEPARATOR.'POP3.php');
		require_once(__DIR__.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.'phpmailer'.DIRECTORY_SEPARATOR.'SMTP.php');
		require_once(__DIR__.DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR.'phpmailer'.DIRECTORY_SEPARATOR.'Exception.php');

		$this->messaggio = new PHPMailer();
		if($this->options['smtp']) {
			$this->messaggio->IsSMTP();
			$this->messaggio->SMTPOptions = array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true,
				)
			);
			if($this->options['encryption'])
				$this->messaggio->SMTPSecure = $this->options['encryption'];
			$this->messaggio->Host = $this->options['smtp'];
			$this->messaggio->Port = $this->options['port'];
			if($this->options['username']){
				$this->messaggio->SMTPAuth = true;
				$this->messaggio->Username = $this->options['username'];
				if($this->options['password'])
					$this->messaggio->Password = $this->options['password'];
			}
			if(DEBUG_MODE and $this->options['debug'])
				$this->messaggio->SMTPDebug  = 2;
		}

		$this->messaggio->IsHTML(true);
		$this->messaggio->CharSet = $this->options['charset'];

		$this->messaggio->From = $this->options['from_mail'];
		$this->messaggio->FromName = $this->options['from_name'];
		$this->messaggio->AddReplyTo($this->options['from_mail']);
		$this->messaggio->Subject = $subject;
		$this->messaggio->Sender = $this->options['from_mail'];

		$this->setText($text);
	}

	public function __destruct(){
		unset($this->messaggio);
	}

	public function addAddress($send_to){
		if(!is_array($send_to))
			$send_to = [$send_to];

		foreach($send_to as $c_em => $em){
			if(!$this->main_address_set){
				$this->messaggio->AddAddress($em);
				$this->main_address_set = true;
			}else{
				$this->messaggio->AddBCC($em);
			}
		}
	}

	public function setText($text){
		$text = $this->options['header'].$text.$this->options['footer'];
		$this->messaggio->Body = $text;
		$plain = html_entity_decode(trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/si', '', $text))), ENT_QUOTES, 'utf-8');
		$this->messaggio->AltBody = $plain;
	}

	public function send($emails = null){
		if($emails!==null)
			$this->addAddress($emails);

		if(!$this->messaggio->Send()){
			$sent = false;
			if(DEBUG_MODE)
				echo $this->messaggio->ErrorInfo;
		}else{
			$sent = true;
		}
		if($this->options['smtp'])
			$this->messaggio->SmtpClose();
		return $sent;
	}

	public function allegaFile($path, $name){
		$this->messaggio->AddAttachment($path, $name);
	}
}
