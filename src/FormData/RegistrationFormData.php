<?php
namespace Szurubooru\FormData;
use Szurubooru\IValidatable;
use Szurubooru\Validator;

class RegistrationFormData implements IValidatable
{
    public $userName;
    public $password;
    public $email;

    public function __construct($inputReader = null)
    {
        if ($inputReader !== null)
        {
            $this->userName = trim($inputReader->userName);
            $this->password = $inputReader->password;
            $this->email = trim($inputReader->email);
        }
    }

    public function validate(Validator $validator)
    {
        $validator->validateUserName($this->userName);
        $validator->validatePassword($this->password);
        $validator->validateEmail($this->email);
    }
}
