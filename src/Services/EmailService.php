<?php
namespace Szurubooru\Services;
use \Szurubooru\Config;
use \Szurubooru\Entities\Token;
use \Szurubooru\Entities\User;

class EmailService
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function sendPasswordResetEmail(User $user, Token $token)
    {
        if (!$user->getEmail())
            throw new \BadMethodCall('An activated e-mail addreses is needed to reset the password.');

        $mailSubject = $this->tokenize($this->config->mail->passwordResetSubject);
        $mailBody = $this->tokenizeFile(
            $this->config->mail->passwordResetBodyPath,
            [
                'link' => $this->config->basic->serviceBaseUrl . '#/password-reset/' . $token->getName(),
            ]);

        $this->sendEmail($user->getEmail(), $mailSubject, $mailBody);
    }

    public function sendActivationEmail(User $user, Token $token)
    {
        if (!$user->getEmailUnconfirmed())
            throw new \BadMethodCallException('An e-mail address is needed to activate the account.');

        $mailSubject = $this->tokenize($this->config->mail->activationSubject);
        $mailBody = $this->tokenizeFile(
            $this->config->mail->activationBodyPath,
            [
                'link' => $this->config->basic->serviceBaseUrl . '#/activate/' . $token->getName(),
            ]);

        $this->sendEmail($user->getEmailUnconfirmed(), $mailSubject, $mailBody);
    }

    public function sendEmail($recipientEmail, $subject, $body)
    {
        $mail = new \PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8';

        $mail->SMTPDebug  = 0;
        $mail->SMTPAuth   = true;
        $mail->Host       = $this->config->mail->smtpHost;
        $mail->Port       = $this->config->mail->smtpPort;
        $mail->Username   = $this->config->mail->smtpUserName;
        $mail->Password   = $this->config->mail->smtpUserPass;
        $mail->From       = $this->config->mail->smtpFrom;
        $mail->FromName   = $this->config->mail->smtpFromName;
        $mail->Subject    = $subject;
        $mail->Body       = str_replace("\n", '<br>', $body);
        $mail->AltBody    = $body;
        $mail->addAddress($recipientEmail);

        if (!$mail->send())
            throw new \Exception('Couldn\'t send mail to ' . $recipientEmail . ': ' . $mail->ErrorInfo);
    }

    private function tokenizeFile($templatePath, $tokens = [])
    {
        $text = file_get_contents($this->config->getDataDirectory() . DIRECTORY_SEPARATOR . $templatePath);
        return $this->tokenize($text, $tokens);
    }

    private function tokenize($text, $tokens = [])
    {
        $tokens['serviceBaseUrl'] = $this->config->basic->serviceBaseUrl;
        $tokens['serviceName'] = $this->config->basic->serviceName;

        foreach ($tokens as $key => $value)
            $text = str_ireplace('{' . $key . '}', $value, $text);

        return $text;
    }
}
