<?php

/**
 * Class OneRouter
 * @uses OnePHP
 */
class OneRouter
{
	// Request methods
	const Method_Get = 'GET';
	const Method_Post = 'POST';
	const Method_Put = 'PUT';
	const Method_Patch = 'PATCH';
	const Method_Delete = 'DELETE';
	const Method_Options = 'OPTIONS';
	const Method_CatchAll = 'ALL';

	// Internal constants
	const DefaultPathDelimiters = [
		'/',
		'.',
		'_',
		'-'
	];

	/**
	 * @var array
	 */
	private $routeMap = [];

	/**
	 * @var OneRouterRouteParser
	 */
	private $routeParser;

	/**
	 * OneRouter constructor.
	 * @param array $pathDelimiters
	 * @throws OneRouterException
	 */
	public function __construct(Array $pathDelimiters = [])
	{
		if (!class_exists('OnePHP'))
			throw new OneRouterException('OneRouter requires OnePHP');

		$this->routeParser = new OneRouterRouteParser(empty($pathDelimiters) ? self::DefaultPathDelimiters : $pathDelimiters);
	}

	/**
	 * Execute route
	 * @param OneRouterRoute $route
	 */
	private function executeRoute(OneRouterRoute $route)
	{
		call_user_func_array($route->Action, [$route]);
		/*
		$routeClosure = $route[OneRouteParser::Route];
		$routeParameters = $route[OneRouteParser::RouteParameters];
		$routeReflection = new ReflectionFunction($routeClosure);
		$parameterArray = [];

		foreach ($routeReflection->getParameters() as $routeParameter) {
			var_dump($routeParameter->getClass()); // TODO
			$parameterName = $routeParameter->getName();
			if (array_key_exists($parameterName, $routeParameters)) {
				array_push($parameterArray, $routeParameters[$parameterName]);
				continue;
			}

			if ($routeParameter->isDefaultValueAvailable())
				continue;

			throw new OneRouteException('Matched route but invalid parameters supplied');
		}

		call_user_func_array($routeClosure, $parameterArray);*/
	}

	/**
	 * Add route for GET requests
	 * @param string $uri
	 * @param callable $do
	 */
	public function Get($uri, $do)
	{
		return $this->Match([self::Method_Get], $uri, $do);
	}

	/**
	 * Add route for POST requests
	 * @param string $uri
	 * @param callable $do
	 */
	public function Post($uri, $do)
	{
		return $this->Match([self::Method_Post], $uri, $do);
	}

	/**
	 * Add route for PUT requests
	 * @param string $uri
	 * @param callable $do
	 */
	public function Put($uri, $do)
	{
		return $this->Match([self::Method_Put], $uri, $do);
	}

	/**
	 * Add route for PATCH requests
	 * @param string $uri
	 * @param callable $do
	 */
	public function Patch($uri, $do)
	{
		return $this->Match([self::Method_Patch], $uri, $do);
	}

	/**
	 * Add route for DELETE requests
	 * @param string $uri
	 * @param callable $do
	 */
	public function Delete($uri, $do)
	{
		return $this->Match([self::Method_Delete], $uri, $do);
	}

	/**
	 * Add route for OPTIONS requests
	 * @param string $uri
	 * @param callable $do
	 */
	public function Options($uri, $do)
	{
		return $this->Match([self::Method_Options], $uri, $do);
	}

	/**
	 * Add route for all type of requests
	 * @param string $uri
	 * @param callable $do
	 */
	public function CatchAll($uri, $do)
	{
		return $this->Match([self::Method_CatchAll], $uri, $do);
	}

	/**
	 * Add route for any request type passed in $requestMethods array
	 * @param array $requestMethods
	 * @param string $uri
	 * @param callable $do
	 */
	public function Match(Array $requestMethods, $uri, $do)
	{
		foreach($requestMethods as $requestMethod) {
			if (!array_key_exists($requestMethod, $this->routeMap))
				$this->routeMap[$requestMethod] = [];

			$this->routeMap[$requestMethod] = array_replace_recursive($this->routeMap[$requestMethod], [$uri => $do]);
		}
	}

	/**
	 * Perform routing on specified requestString with request method
	 * @param string $requestString
	 * @param string $method GET|POST|PUT|PATCH|DELETE|OPTIONS
	 */
	public function Route($requestString, $method)
	{
		$routeMap = OnePHP::ValueIfExists($method, $this->routeMap, []);
		$catchAllRouteMap = OnePHP::ValueIfExists(self::Method_CatchAll, $this->routeMap, []);

		$matchingRoute = $this->routeParser->GetMatchingRoute($requestString, $routeMap)
			?: $this->routeParser->GetMatchingRoute($requestString, $catchAllRouteMap);

		if ($matchingRoute) {
			$this->executeRoute($matchingRoute);
			return;
		}

		echo "Hittade ingen rutt som matchade din request";
	}
}

class OneRouterException extends Exception
{
}