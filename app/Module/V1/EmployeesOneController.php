<?php declare(strict_types = 1);

namespace App\Module\V1;

use Apitte\Core\Annotation\Controller as Apitte;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use App\Domain\Api\Facade\EmployeeFacade;
use App\Domain\Api\Response\EmployeeResponseDto;
use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\Runtime\Database\EntityNotFoundException;
use App\Model\Utils\Caster;
use Nette\Http\IResponse;

/**
 * @Apitte\Path("/employees")
 * @Apitte\Tag("Employees")
 */
class EmployeesOneController extends BaseV1Controller
{

	private EmployeeFacade $employeesFacade;

	public function __construct(EmployeeFacade $employeesFacade)
	{
		$this->employeesFacade = $employeesFacade;
	}

	/**
	 * @Apitte\OpenApi("
	 *   summary: Get employee by name.
	 * ")
	 * @Apitte\Path("/name")
	 * @Apitte\Method("GET")
	 * @Apitte\RequestParameters({
	 *      @Apitte\RequestParameter(name="name", in="query", type="string", description="Employee name")
	 * })
	 */
	public function byName(ApiRequest $request): EmployeeResponseDto
	{
		try {
			return $this->employeesFacade->findOneBy(['name' => Caster::toString($request->getParameter('name'))]);
		} catch (EntityNotFoundException $e) {
			throw ClientErrorException::create()
				->withMessage('Employee not found.')
				->withCode(IResponse::S404_NotFound);
		} catch (IXmlDriverException $e) {
			throw ServerErrorException::create()
				->withMessage('Cannot get employee.')
				->withPrevious($e);
		}
	}

	/**
	 * @Apitte\OpenApi("
	 *   summary: Get employee by id.
	 * ")
	 * @Apitte\Path("/{id}")
	 * @Apitte\Method("GET")
	 * @Apitte\RequestParameters({
	 *      @Apitte\RequestParameter(name="id", in="path", type="int", description="Employee ID")
	 * })
	 */
	public function byId(ApiRequest $request): EmployeeResponseDto
	{
		try {
			return $this->employeesFacade->findOne(Caster::toInt($request->getParameter('id')));
		} catch (EntityNotFoundException $e) {
			throw ClientErrorException::create()
				->withMessage('Employee not found.')
				->withCode(IResponse::S404_NotFound);
		} catch (IXmlDriverException $e) {
			throw ServerErrorException::create()
				->withMessage('Cannot get employee.')
				->withPrevious($e);
		}
	}

}
