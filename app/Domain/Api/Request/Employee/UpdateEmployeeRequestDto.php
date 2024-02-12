<?php declare(strict_types = 1);

namespace App\Domain\Api\Request\Employee;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateEmployeeRequestDto
{
	#[Assert\NotBlank]
	public string $name;

	#[Assert\NotBlank]
	public int $age;

}
