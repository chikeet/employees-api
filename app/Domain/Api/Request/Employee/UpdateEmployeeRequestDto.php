<?php declare(strict_types = 1);

namespace App\Domain\Api\Request\Employee;

use Symfony\Component\Validator\Constraints\NotBlank;

final class UpdateEmployeeRequestDto
{

	#[NotBlank]
	public string $name;

	#[NotBlank]
	public int $age;

}
