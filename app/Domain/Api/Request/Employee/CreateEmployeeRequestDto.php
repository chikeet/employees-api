<?php declare(strict_types = 1);

namespace App\Domain\Api\Request\Employee;

use App\Validator\Constraint\Gender\Gender;
use Symfony\Component\Validator\Constraints\NotBlank;

final class CreateEmployeeRequestDto
{

	#[NotBlank]
	public string $name;

	#[NotBlank]
	public int $age;

	#[NotBlank]
	#[Gender]
	public string $gender;

}
