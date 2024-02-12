<?php declare(strict_types = 1);

namespace App\Validator\Constraint\Gender;

use App\Model\Database\Entity\Enum\Gender as GenderEnum;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class GenderValidator extends ConstraintValidator
{
	public function validate(mixed $value, Constraint $constraint): void
	{
		if (!$constraint instanceof Gender) {
			throw new UnexpectedTypeException($constraint, Gender::class);
		}

		// custom constraints should ignore null and empty values to allow
		// other constraints (NotBlank, NotNull, etc.) to take care of that
		if (null === $value || '' === $value) {
			return;
		}

		if (!is_string($value)) {
			// throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
			throw new UnexpectedValueException($value, 'string');

			// separate multiple types using pipes
			// throw new UnexpectedValueException($value, 'string|int');
		}

		if (GenderEnum::tryFrom($value) instanceof GenderEnum) {
			return;
		}

		// the argument must be a string or an object implementing __toString()
		$this->context->buildViolation($constraint->message)
			->setParameter('{{ string }}', $value)
			->addViolation();
	}
}
