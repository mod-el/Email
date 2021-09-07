<?php namespace Model\Email;

use PHPMailer\PHPMailer\PHPMailer;

class Email
{
	/** @var array */
	protected $options;
	/** @var PHPMailer */
	protected $message;
	/** @var bool */
	protected $main_address_set = false;

	public function __construct(string $subject, string $text, array $options = [])
	{
		require(INCLUDE_PATH . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'Email' . DIRECTORY_SEPARATOR . 'config.php');
		$this->options = array_merge($config, $options);

		/** Retrocompatibilità **/
		if (defined('EMAILS_DEFAULT_NAME'))
			$this->options['from_name'] = EMAILS_DEFAULT_NAME;
		if (defined('EMAILS_DEFAULT_SENDER'))
			$this->options['from_mail'] = EMAILS_DEFAULT_SENDER;
		if (defined('EMAILS_SMTP'))
			$this->options['smtp'] = (bool)EMAILS_SMTP;
		if (defined('EMAILS_SMTP_PORT'))
			$this->options['port'] = EMAILS_SMTP_PORT;
		if (defined('EMAILS_HEADER'))
			$this->options['header'] = EMAILS_HEADER;
		if (defined('EMAILS_FOOTER'))
			$this->options['footer'] = EMAILS_FOOTER;
		if (defined('EMAILS_SMTP_USERNAME'))
			$this->options['username'] = EMAILS_SMTP_USERNAME;
		if (defined('EMAILS_SMTP_PASSWORD'))
			$this->options['password'] = EMAILS_SMTP_PASSWORD;
		if (defined('EMAILS_ENCTYPTION'))
			$this->options['encryption'] = EMAILS_ENCTYPTION;
		/** **/

		$this->message = new PHPMailer();
		if ($this->options['smtp']) {
			$this->message->IsSMTP();
			$this->message->SMTPOptions = [
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true,
				],
			];
			if ($this->options['encryption'])
				$this->message->SMTPSecure = $this->options['encryption'];
			$this->message->Host = $this->options['smtp'];
			$this->message->Port = $this->options['port'];
			if ($this->options['username']) {
				$this->message->SMTPAuth = true;
				$this->message->Username = $this->options['username'];
				if ($this->options['password'])
					$this->message->Password = $this->options['password'];
			}
			if (DEBUG_MODE and $this->options['debug'])
				$this->message->SMTPDebug = 2;
		}

		$this->message->IsHTML(true);
		$this->message->CharSet = $this->options['charset'];

		$this->message->setFrom($this->options['from_mail'], $this->options['from_name']);
		$this->message->AddReplyTo($this->options['from_mail'], $this->options['from_name']);
		$this->message->Subject = $subject;

		$this->setText($text);
	}

	public function __destruct()
	{
		unset($this->message);
	}

	public function addAddress(array $send_to)
	{
		foreach ($send_to as $em) {
			if (!$this->main_address_set) {
				$this->message->AddAddress($em);
				$this->main_address_set = true;
			} else {
				$this->message->AddBCC($em);
			}
		}
	}

	public function setText(string $text)
	{
		$text = $this->options['header'] . $text . $this->options['footer'];
		$this->message->Body = $text;
		$plain = html_entity_decode(trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/si', '', $text))), ENT_QUOTES, 'utf-8');
		$this->message->AltBody = $plain;
	}

	public function send($emails = null)
	{
		if ($emails !== null) {
			if (!is_array($emails))
				$emails = [$emails];

			$this->addAddress($emails);
		}

		if (!$this->message->Send())
			throw new \Exception($this->message->ErrorInfo);

		if ($this->options['smtp'])
			$this->message->SmtpClose();
	}

	public function attachFile(string $path, string $name)
	{
		$this->message->AddAttachment($path, $name);
	}
}
