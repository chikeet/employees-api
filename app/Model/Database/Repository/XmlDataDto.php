<?php declare(strict_types = 1);

namespace App\Model\Database\Repository;

use Nette\Utils\ArrayHash;

/**
 * DTO for data transfer between XML and entity.
 * Used for new entity creation from XML data.
 * The only reason of existence of this class is better code readability than when using just ArrayHash.
 *
 * @phpstan-extends ArrayHash<mixed>
 */
class XmlDataDto extends ArrayHash
{

}
