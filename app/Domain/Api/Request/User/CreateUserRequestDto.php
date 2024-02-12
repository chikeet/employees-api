<?php declare(strict_types = 1);

namespace App\Domain\Api\Request\User;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserRequestDto
{

	#[Assert\NotBlank]
	#[Assert\Email]
	public string $email;

	#[Assert\NotBlank]
	public string $name;

	#[Assert\NotBlank]
	public string $surname;

	#[Assert\NotBlank]
	public string $username;

	public ?string $password = null;

}