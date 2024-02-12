<?php declare(strict_types = 1);

namespace App\Domain\Api\Request\User;

final class UpdateUserReqDto
{

	/**
	 * @Assert\NotBlank
	 * @Assert\Email
	 */
	public string $email;

	/** @Assert\NotBlank */
	public string $name;

	/** @Assert\NotBlank */
	public string $surname;

}
