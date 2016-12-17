<?php

class OneRouterRouteParser
{
	/**
	 * @var array
	 */
	private $pathDelimiters;

	/**
	 * OneRouteParser constructor.
	 * @param array $pathDelimiters
	 */
	public function __construct(Array $pathDelimiters = ['/'])
	{
		$this->pathDelimiters = $pathDelimiters;
	}

	/**
	 * Compare string to delimiters
	 * @param string $string
	 * @return bool
	 */
	private function isDelimiter($string)
	{
		return in_array($string, $this->pathDelimiters);
	}

	/**
	 * Parse route into pieces
	 * @param string $route
	 * @return string[]
	 */
	public function ParseRoute($route)
	{
		$route = trim($route, implode('', $this->pathDelimiters));
		$routeParts = preg_split('/([\\' . implode('\\', $this->pathDelimiters) . '])/', $route, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		return $routeParts;
	}

	/**
	 * Parse route part
	 * @param string $routePart
	 * @return OneRouterRoutePart
	 */
	public function ParseRoutePart($routePart)
	{
		return new OneRouterRoutePart($routePart);
	}

	/**
	 * Match $uri to $routeMap and return the best match.
	 * Otherwise return false.
	 * TODO Add support for wildcard catchall
	 * @param string $uri
	 * @param array $routeMap
	 * @return OneRouterRoute|false
	 */
	public function GetMatchingRoute($uri, $routeMap)
	{
		$parsedUri = $this->ParseRoute($uri);
		$matchingRoute = false;

		foreach($routeMap as $routeUri => $action) {
			$parsedRouteUri = $this->ParseRoute($routeUri);
			if (count($parsedRouteUri) < count($parsedUri))
				continue;

			$route = new OneRouterRoute($action);
			foreach ($parsedRouteUri as $key => $routePart) {
				$uriPart = OnePHP::ValueIfExists($key, $parsedUri, false);

				// Handle delimiter
				if ($this->isDelimiter($routePart)) {
					if ($routePart == $uriPart || !$uriPart)
						continue;
					else {
						$route = null;
						break;
					}
				}

				// Handle route part
				$parsedPart = $this->ParseRoutePart($routePart);
				if ($parsedPart->LinkWith($uriPart)) {
					$route->AddPart($parsedPart);
					continue;
				} else {
					$route = null;
					break;
				}
			}

			if (!is_null($route) && (!$matchingRoute || $matchingRoute->GetScore() < $route->GetScore()))
				$matchingRoute = $route;
		}

		return $matchingRoute;
	}
}