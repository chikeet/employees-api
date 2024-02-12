<?php declare(strict_types = 1);

namespace App\Domain\Api\Request\User;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

final class CreateUserRequestDto
{

	#[NotBlank]
	#[Email]
	public string $email;

	#[NotBlank]
	public string $name;

	#[NotBlank]
	public string $surname;

	#[NotBlank]
	public string $username;

	public ?string $password = null;

}
