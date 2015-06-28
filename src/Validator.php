<?php
namespace Szurubooru;
use Szurubooru\Config;
use Szurubooru\IValidatable;

class Validator
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function validate(IValidatable $validatable)
    {
        $validatable->validate($this);
    }

    public function validateNumber($subject)
    {
        if (!preg_match('/^-?[0-9]+$/', $subject))
            throw new \DomainException($subject . ' does not look like a number.');
    }

    public function validateNonEmpty($subject, $subjectName = 'Object')
    {
        if (!$subject)
            throw new \DomainException($subjectName . ' cannot be empty.');
    }

    public function validateLength($subject, $minLength, $maxLength, $subjectName = 'Object')
    {
        $this->validateMinLength($subject, $minLength, $subjectName);
        $this->validateMaxLength($subject, $maxLength, $subjectName);
    }

    public function validateMinLength($subject, $minLength, $subjectName = 'Object')
    {
        if (strlen($subject) < $minLength)
            throw new \DomainException($subjectName . ' must have at least ' . $minLength . ' character(s).');
    }

    public function validateMaxLength($subject, $maxLength, $subjectName = 'Object')
    {
        if (strlen($subject) > $maxLength)
            throw new \DomainException($subjectName . ' must have at most ' . $maxLength . ' character(s).');
    }

    public function validateUserName($userName)
    {
        $minUserNameLength = intval($this->config->users->minUserNameLength);
        $maxUserNameLength = intval($this->config->users->maxUserNameLength);
        $this->validateNonEmpty($userName, 'User name');
        $this->validateLength($userName, $minUserNameLength, $maxUserNameLength, 'User name');

        if (preg_match('/[^a-zA-Z0-9_-]/', $userName))
        {
            throw new \DomainException('User name may contain only characters, numbers, underscore (_) and dash (-).');
        }
    }

    public function validateEmail($email)
    {
        if (!$email)
            return;

        if (!preg_match('/^[^@]+@[^@]+\.\w+$/', $email))
            throw new \DomainException('Specified e-mail appears to be invalid.');
    }

    public function validatePassword($password)
    {
        $minPasswordLength = intval($this->config->security->minPasswordLength);
        $this->validateNonEmpty($password, 'Password');
        $this->validateMinLength($password, $minPasswordLength, 'Password');

        if (preg_match('/[^\x20-\x7f]/', $password))
        {
            throw new \DomainException(
                'Password may contain only characters from ASCII range to avoid potential problems with encoding.');
        }
    }

    public function validatePostTags($tags)
    {
        if (empty($tags))
            throw new \DomainException('Tags cannot be empty.');

        //<> causes HTML injection and problems with Markdown.
        //\/?& causes problems with URLs.
        //#; causes problems with search argument parsing in JS frontend.
        //whitespace causes problems with search.
        $illegalCharacters = str_split("<>#;\\/?&\r\n\t " . chr(160));
        foreach ($tags as $tag)
        {
            if (empty($tag))
                throw new \DomainException('Tags cannot be empty.');

            //: causes problems with complex search (e.g. id:5).
            if (strpos($tag, ':') > 0)
                throw new \DomainException('Colon in tag may appear only at the beginning.');

            $this->validateMaxLength($tag, 64, 'Tag');

            foreach ($illegalCharacters as $char)
            {
                if (strpos($tag, $char) !== false)
                {
                    throw new \DomainException(
                        sprintf('Tags cannot contain any of following characters: %s.',
                        implode(', ', array_map(function($char)
                            {
                                if ($char === "\n") return "new line";
                                if ($char === "\r") return "carriage return";
                                if ($char === "\t") return "tab";
                                if ($char === " ") return "space";
                                if ($char === chr(160)) return "hard space";
                                return $char;
                            }, $illegalCharacters))));
                }
            }
        }
    }

    public function validatePostSource($source)
    {
        $this->validateMaxLength($source, 200, 'Source');
    }

    public function validateToken($token)
    {
        $this->validateNonEmpty($token, 'Token');
    }
}
