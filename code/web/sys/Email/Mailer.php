<?php

class Mailer {
	protected $settings;      // settings for PEAR Mail object

	/**
	 * Send an email message.
	 *
	 * @access  public
	 * @param string $to Recipient email address
	 * @param string $subject Subject line for message
	 * @param string $body Message body
	 * @param string $replyTo Someone to reply to
	 * @param bool $htmlMessage True to send the email as html
	 *
	 * @return  boolean
	 */
	public function send($to, $subject, $body, $replyTo = null, $htmlMessage = false) {
		require_once ROOT_DIR . '/sys/Email/SendGridSetting.php';
		require_once ROOT_DIR . '/sys/Email/AmazonSesSetting.php';
		require_once ROOT_DIR . '/sys/CurlWrapper.php';
		//TODO: Do validation of the address
		$amazonSesSettings = new AmazonSesSetting();
		if ($amazonSesSettings->find(true)){
			return $this->sendViaAmazonSes($amazonSesSettings, $to, $replyTo, $subject, $htmlMessage, $body);
		}else{
			$sendGridSettings = new SendGridSetting();
			if ($sendGridSettings->find(true)){
				return $this->sendViaSendGrid($sendGridSettings, $to, $replyTo, $subject, $htmlMessage, $body);
			}else{
				return false;
			}
		}
	}

	/**
	 * @param SendGridSetting $sendGridSettings
	 * @param string $to
	 * @param string|null $replyTo
	 * @param string $subject
	 * @param bool $htmlMessage
	 * @param string $body
	 * @return bool
	 */
	protected function sendViaSendGrid(SendGridSetting $sendGridSettings, string $to, ?string $replyTo, string $subject, bool $htmlMessage, string $body)
	{
		//Send the email
		$curlWrapper = new CurlWrapper();
		$headers = [
			'Authorization: Bearer ' . $sendGridSettings->apiKey,
			'Content-Type: application/json'
		];
		$curlWrapper->addCustomHeaders($headers, false);

		$apiBody = new stdClass();
		$apiBody->personalizations = [];
		$toAddresses = explode(';', $to);
		foreach ($toAddresses as $tmpToAddress) {
			$personalization = new stdClass();
			$personalization->to = [];

			$toAddress = new stdClass();
			$toAddress->email = trim($tmpToAddress);
			$personalization->to[] = $toAddress;

			$apiBody->personalizations[] = $personalization;
		}
		$apiBody->from = new stdClass();
		$apiBody->from->email = $sendGridSettings->fromAddress;
		$apiBody->reply_to = new stdClass();
		$apiBody->reply_to->email = (($replyTo != null) ? $replyTo : $sendGridSettings->replyToAddress);
		$apiBody->subject = $subject;
		$apiBody->content = [];
		$content = new stdClass();
		if ($htmlMessage) {
			$content->type = 'text/html';
		} else {
			$content->type = 'text/plain';
		}
		$content->value = $body;
		$apiBody->content[] = $content;

		$response = $curlWrapper->curlPostPage('https://api.sendgrid.com/v3/mail/send', json_encode($apiBody));
		if ($response != '') {
			global $logger;
			$logger->log('Error sending email via SendGrid ' . $curlWrapper->getResponseCode() . ' ' . $response, Logger::LOG_ERROR);
			return false;
		} else {
			return true;
		}
	}

	private function sendViaAmazonSes(AmazonSesSetting $amazonSesSettings, string $to, ?string $replyTo, string $subject, bool $htmlMessage, string $body) : bool
	{
		require_once ROOT_DIR . '/sys/Email/AmazonSesMessage.php';
		$message = new AmazonSesMessage();
		$message->setTo($to);
		if (!empty($replyTo)){
			$message->addReplyTo($replyTo);
		}
		$message->setSubject($subject);
		if ($htmlMessage){
			$message->setMessageFromString(null, $body);
		}else{
			$message->setMessageFromString($body, null);
		}

		$response = $amazonSesSettings->sendEmail($message, false, false);
		if ($response == false){
			return false;
		}else{
			return true;
		}
	}
}