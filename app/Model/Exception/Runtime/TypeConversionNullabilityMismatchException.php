<?php declare(strict_types = 1);

namespace App\Model\Exception\Runtime;

use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\RuntimeException;

final class TypeConversionNullabilityMismatchException extends RuntimeException implements IXmlDriverException
{

}
