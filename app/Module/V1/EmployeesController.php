<?php declare(strict_types = 1);

namespace App\Module\V1;

use Apitte\Core\Annotation\Controller as Apitte;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use App\Domain\Api\Facade\EmployeeFacade;
use App\Domain\Api\Response\EmployeeResponseDto;
use App\Model\Exception\IXmlDriverException;

/**
 * @Apitte\Path("/employees")
 * @Apitte\Tag("Employees")
 */
class EmployeesController extends BaseV1Controller
{

	private EmployeeFacade $employeesFacade;


	public function __construct(EmployeeFacade $employeesFacade)
	{
		$this->employeesFacade = $employeesFacade;
	}


	/**
	 * @Apitte\OpenApi("
	 *   summary: List employees.
	 * ")
	 * @Apitte\Path("/")
	 * @Apitte\Method("GET")
	 * @return EmployeeResponseDto[]
	 */
	public function index(ApiRequest $request): array
	{
		try {
			return $this->employeesFacade->findAll();
		} catch (IXmlDriverException $e) {
			throw ServerErrorException::create()
				->withMessage('Cannot get employees.')
				->withPrevious($e);
		}
	}

}
