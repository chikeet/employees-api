# Employees API

Jednoduché API pro správu zaměstnanců.

## Instalace

Nejjednodušeji s využitím připravených Make příkazů.

- `make install` spustí klasický `composer install`.
- `make init` zkopíruje `config/local.neon.example` do `config/local.neon`
- `make setup` vytvoří složky `var/tmp` a `var/log` a nastaví jim práva pro zápis
- `make fixtures` zkopíruje datové xml soubory `user.xml` a `employee.xml` ze složky `data/xml/fixtures` do `data/xml`.

## Spuštění projektu

Příkazem `make dev`, který spustí PHP server na adrese http://localhost:8000/ .

Ukázkový endpoint pro seznam zaměstnanců je dostupný na adrese http://localhost:8000/api/v1/employees/?_access_token=admin (GET request).

Kompletní seznam endpointů pak najdete:
- buď v OpenAPI JSON formátu na adrese http://localhost:8000/api/public/v1/openapi/meta
- nebo v [Postman](https://www.postman.com/) kolekci https://api.postman.com/collections/24797339-86494b6e-0341-426a-b5e8-f636e1b926a1?access_key=PMAT-01HPFJ3590ZN0CXY2FEXHX8E65
- (nejsnadněji lze v desktopové aplikaci Postmana importovat kolekci přes její URL a následně volat jednotlivé requesty).

## QA

Nástroje pro zajištění kvality kódu jsou dostupné prostřednictvím následujících Make příkazů:
- `make tests` - unit, integrační a end-to-end testy
- `make phpstan` - PhpStan
- `make cs` - code sniffer, `make csf` - code sniffer s automatickou opravou chyb

## Popis řešení

Jako kostru aplikace jsem zvolila [Apitte skeleton](https://github.com/contributte/apitte-skeleton), který je základem API nad Nette a zahrnuje všechny
základní funkce včetně jednoduché autentizace. Protože jsem pro vývoj zvolila nejnovější PHP 8.3,
bylo třeba před vlastním vývojem několik drobných úprav jak ve skeletonu, tak v knihovně `Faker` použité pro generování fixtures.

Následně jsem implementovala `EntityManager` a `XmlDriver` s využitím tříd `DOMDocument` a `DOMXPath` a použila je namísto `Doctrine ORM`,
které je součástí skeletonu, ale je vhodné pouze pro práci s relačními databázemi.
Při implementaci `EntityManageru` jsem využila návrhový vzor `IdentityMap` pro snadnou práci s entitami
a zamezení vzniku duplicitních instancí entit téhož XML záznamu.

Pro typovou konverzi dat mezi XML souborem a PHP entitou jsem implementovala statickou třídu `TypeConverter`,
která pokrývá datové typy použité v XML a v PHP entitách a definuje možnosti konverzí mezi nimi.

XML soubor s daty uživatelů jsem vytvořila exportem z původní databázové tabulky uživatelů ze skeletonu.
Následně jsem pouze upravila formát `datetime` a `nullable` elementů pro kompatibilitu s XML.

Pro validaci jsem použila `Symfony\Validator` ze skeletonu, který jsem rozšířila o vlastní typ `Gender` (pohlaví zaměstnance).

ID v XML záznamech jsou integerová, o autoinkrementaci ID při vytvoření nového záznamu se stará `EntityManager`.
Při vývoji `EntityMangeru` jsem se rámcově inspirovala prací s entitami v Doctrine (především pokud jde o funkčnost `IdentityMap`
a princip persistence entit).

Pro samotnou práci s XML, získávání, ukládání a konverze dat slouží třída `XmlDriver`. Ke každému XML souboru se
při práci s daty generuje XSD schéma, které `XmlDriver` automaticky přegeneruje při každé změně ve třídě příslušné entity,
což ulehčuje přidávání nových atributů.

V rámci aplikace je `XmlDriver` a `EntityManager` zapouzdřený v repozitáři (`AbstractRepository`), který nabízí klasické metody pro získávání,
vytváření, updatování a mazání entit. Každá entita (`User`, `Employee`) má vlastní repozitář (`UserRepository`, `EmployeeRepository`).

Ze strany API endpointů se v aplikaci pracuje s controllery (např. `EmployeesControler`) a s fasádami (např. `EmployeeFacade`),
ze kterých se volají metody repozitářů.

Aplikace má potenciál pro zlepšení ve směru přidání testů nebo větší konfigurovatelnosti, což jsem z časových důvodů nerealizovala.

## Postup přidání nové property (atributu) k Employee entitě

- __Přidat property v `App\Domain\Employee` entitě__. Property musí mít __atribut__ `Property`, který definuje datový typ v XML,
zda je hodnota nullable a zda má být unikátní v rámci XML souboru. Toto je jediný nutný krok, ostatní kroky jsou volitené podle potřeby.
- Pokud má být property nastavitelná přes API, je třeba ji přidat také do __kostruktoru entity__
(volaného při vytváření entity přes API v metodě `EmployeeFacade::create`) a/nebo na ni __napsat setter__ (a přidat jeho volání
do metody `EmployeeFacade::update`).
- Pokud property nemá defaultní hodnotu a není `nullable`, je třeba ručně doplnit XML elementy k existujícím entitám v XML souboru.
XSD schéma není třeba upravovat, přegeneruje se automaticky.
- Kromě toho je třeba přidat property do `Request` a `Response` DTO objektů k dané entitě:
  - `CreateEmployeeRequestDto` pro nastavení hodnoty při vytváření zaměstnance
  - `UpdateEmployeeRequestDto` pro nastavení při updatu zaměstnance
  - Pokud má property specifický typ, je třeba na něj napsat validátor a validační constraint po vzoru `App\Validator\Constraint\GenderValidator`
  a `App\Validator\Constraint\Gender` a přidat konverzi v obou směrech (z PHP do XML a zpět) do třídy `App\Model\Database\TypeConverter`
  a typ přidat do enumu `App\Model\Database\PhpType`.
  - `EmployeeResponseDto`, pokud má být nová property vracena v rámci dat zaměstnance dostupných přes GET endpointy
