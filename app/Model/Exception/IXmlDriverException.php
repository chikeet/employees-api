<?php declare(strict_types = 1);

namespace App\Model\Exception;

use Throwable;

/**
 * Wrapper for all low-level XML/entity-related exceptions (aka "something fucked up somewhere deep inside").
 */
interface IXmlDriverException extends Throwable // TODO @improve: use more sophisticated exceptions hierarchy
{

}
