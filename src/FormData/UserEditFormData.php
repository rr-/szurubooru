<?php
namespace Szurubooru\FormData;
use Szurubooru\Helpers\EnumHelper;
use Szurubooru\Helpers\MimeHelper;
use Szurubooru\IValidatable;
use Szurubooru\Validator;

class UserEditFormData implements IValidatable
{
    public $userName;
    public $email;
    public $password;
    public $accessRank;
    public $avatarStyle;
    public $avatarContent;
    public $browsingSettings;
    public $banned;

    public function __construct($inputReader = null)
    {
        if ($inputReader !== null)
        {
            $this->userName = $inputReader->userName;
            $this->email = $inputReader->email;
            $this->password = $inputReader->password;
            if ($inputReader->accessRank !== null)
                $this->accessRank = EnumHelper::accessRankFromString($inputReader->accessRank);
            if ($inputReader->avatarStyle !== null)
                $this->avatarStyle = EnumHelper::avatarStyleFromString($inputReader->avatarStyle);
            $this->avatarContent = $inputReader->readFile('avatarContent');
            $this->browsingSettings = json_decode($inputReader->browsingSettings);
            if ($inputReader->banned !== null)
                $this->banned = boolval($inputReader->banned);
        }
    }

    public function validate(Validator $validator)
    {
        if ($this->userName !== null)
            $validator->validateUserName($this->userName);

        if ($this->password !== null)
            $validator->validatePassword($this->password);

        if ($this->email !== null)
            $validator->validateEmail($this->email);

        if (strlen($this->avatarContent) > 1024 * 512)
            throw new \DomainException('Avatar content must have at most 512 kilobytes.');

        if ($this->avatarContent)
        {
            $avatarContentMimeType = MimeHelper::getMimeTypeFromBuffer($this->avatarContent);
            if (!MimeHelper::isImage($avatarContentMimeType))
                throw new \DomainException('Avatar must be an image (detected: ' . $avatarContentMimeType . ').');
        }

        if ($this->browsingSettings !== null)
        {
            if (!is_object($this->browsingSettings))
                throw new \InvalidArgumentException('Browsing settings must be valid JSON.');
            else if (strlen(json_encode($this->browsingSettings)) > 300)
                throw new \InvalidArgumentException('Stringified browsing settings can have at most 300 characters.');
        }
    }
}
