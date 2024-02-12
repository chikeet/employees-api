<?php declare(strict_types = 1);

namespace App\Domain\Api\Request\User;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

final class UpdateUserRequestDto
{

	#[NotBlank]
	#[Email]
	public string $email;

	#[NotBlank]
	public string $name;

	#[NotBlank]
	public string $surname;

}
