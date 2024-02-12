<?php declare(strict_types = 1);

namespace App\Model\Exception\Logic;

use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\LogicException;
use Throwable;

final class EntityXmlFileNotFoundException extends LogicException implements IXmlDriverException
{

	public function __construct(string $fileName, int $code = 0, ?Throwable $previous = null)
	{
		$message = sprintf('Entity XML file "%s" not found.', $fileName);

		parent::__construct($message, $code, $previous);
	}

}
