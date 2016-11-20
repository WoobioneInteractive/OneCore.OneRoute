<?php

/**
 * A fast router for OneCore
 * @author A.G. Netterwall <netterwall@gmail.com>
 */
class OneRoute implements IPlugin, IRouter
{
	// Configuration options
	const Config_PathDelimiters = 'oneroute.pathDelimiters';

	// Internal constants
	const RouterInterface = 'IRouter';
	const Method_CatchAll = 'CATCHALL';

	/**
	 * @var IConfigHandler
	 */
	private $configuration = null;

	/**
	 * @var IRequest
	 */
	private $request = null;

	/**
	 * @var OneRouteParser
	 */
	private $routeParser = null;

	/**
	 * @var array
	 */
	private $routeMap = [];

	/**
	 * OneRoute constructor.
	 * @param IRequest $request
	 * @param DependencyInjector $di
	 */
	public function __construct(IConfigHandler $configuration, IRequest $request, DependencyInjector $di)
	{
		$this->configuration = $configuration;
		$this->request = $request;
		$this->routeParser = new OneRouteParser($this->configuration->Get(self::Config_PathDelimiters, []));

		// Register this as implemented instance of IRouter
		$di->AddMapping(new DependencyMappingFromArray([
			self::RouterInterface => [ DependencyInjector::Mapping_RemoteInstance => $this ]
		]));
	}

	private function tryRoute($route)
	{
		$routeClosure = $route[OneRouteParser::Route];
		$routeParameters = $route[OneRouteParser::RouteParameters];
		$routeReflection = new ReflectionFunction($routeClosure);
		$parameterArray = [];

		foreach ($routeReflection->getParameters() as $routeParameter) {
			$parameterName = $routeParameter->getName();
			if (array_key_exists($parameterName, $routeParameters)) {
				array_push($parameterArray, $routeParameters[$parameterName]);
				continue;
			}

			if ($routeParameter->isDefaultValueAvailable())
				continue;

			throw new OneRouteException('Matched route but invalid parameters supplied');
		}

		call_user_func_array($routeClosure, $parameterArray);
	}

	public function Get($uri, $do)
	{
		return $this->Match([Request::Method_Get], $uri, $do);
	}

	public function Post($uri, $do)
	{
		return $this->Match([Request::Method_Post], $uri, $do);
	}

	public function Put($uri, $do)
	{
		return $this->Match([Request::Method_Put], $uri, $do);
	}

	public function Patch($uri, $do)
	{
		return $this->Match([Request::Method_Patch], $uri, $do);
	}

	public function Delete($uri, $do)
	{
		return $this->Match([Request::Method_Delete], $uri, $do);
	}

	public function Options($uri, $do)
	{
		return $this->Match([Request::Method_Options], $uri, $do);
	}

	public function CatchAll($uri, $do)
	{
		return $this->Match([self::Method_CatchAll], $uri, $do);
	}

	public function Match(Array $requestMethods, $uri, $do)
	{
		foreach($requestMethods as $requestMethod) {
			if (!array_key_exists($requestMethod, $this->routeMap))
				$this->routeMap[$requestMethod] = [];

			$this->routeMap[$requestMethod] = array_replace_recursive($this->routeMap[$requestMethod], [$uri => $do]);
		}
	}

	public function Route()
	{
		$requestUri = $this->request->GetRequestString();
		$method = $this->request->GetMethod();

		// Prioritize routes specified method routes
		if (array_key_exists($method, $this->routeMap)) {
			$methodRouteMap = $this->routeMap[$method];
			$matchingRoute = $this->routeParser->GetMatchingRoute($requestUri, $methodRouteMap);
			if ($matchingRoute) {
				$this->tryRoute($matchingRoute);
				return;
			}
		}

		// Then try CatchAll routes
		if (array_key_exists(self::Method_CatchAll, $this->routeMap)) {
			$methodRouteMap = $this->routeMap[self::Method_CatchAll];
			$matchingRoute = $this->routeParser->GetMatchingRoute($requestUri, $methodRouteMap);
			if ($matchingRoute) {
				$this->tryRoute($matchingRoute);
				return;
			}
		}

		echo "Hittade ingen rutt";
	}
}

/**
 * Class OneRouteException
 */
class OneRouteException extends Exception
{
}