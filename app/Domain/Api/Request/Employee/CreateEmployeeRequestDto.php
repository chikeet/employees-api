<?php declare(strict_types = 1);

namespace App\Domain\Api\Request\Employee;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateEmployeeRequestDto
{
	#[Assert\NotBlank]
	public string $name;

	#[Assert\NotBlank]
	public int $age;

	#[Assert\NotBlank]
	#[AppAssert\Constraint\Gender\Gender]
	public string $gender;

}
