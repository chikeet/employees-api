<?php declare(strict_types = 1);

namespace App\Validator\Constraint\Gender;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
final class Gender extends Constraint
{

	public string $message = 'The string "{{ string }}" is not valid gender value.';

	/**
	 * All configurable options must be passed to the constructor.
	 */
	public function __construct(?string $message = null, ?array $groups = null, mixed $payload = null)
	{
		parent::__construct([], $groups, $payload);

		$this->message = $message ?? $this->message;
	}

	public function validatedBy(): string
	{
		return self::class . 'Validator';
	}

}
