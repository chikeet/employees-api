<?php declare(strict_types = 1);

namespace App\Model\Exception\Logic;

use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\LogicException;

final class XmlDataDirNotFoundException extends LogicException implements IXmlDriverException
{

}
