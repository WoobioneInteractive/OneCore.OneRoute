<?php

class OneRouteParser
{
	// Route values
	const Route = 'route';
	const RouteParameters = 'parameters';

	// Internal constants
	const DefaultPathDelimiters = [
		'/',
		'.',
		'_',
		'-'
	];
	const RoutePartTypes = [
		'int',
		'integer',
		'bool',
		'boolean'
	];

	/**
	 * @var array
	 */
	private $pathDelimiters = [];

	/**
	 * OneRouteParser constructor.
	 * @param array $pathDelimiters
	 */
	public function __construct(Array $pathDelimiters = [])
	{
		$this->pathDelimiters = $pathDelimiters ?: self::DefaultPathDelimiters;
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
	 * @return array
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
	 * @return RoutePart
	 */
	public function ParseRoutePart($routePart)
	{
		return new RoutePart($routePart);
	}

	/**
	 * Validate type against known types
	 * @param string $type
	 * @return bool
	 */
	public function IsValidType($type)
	{
		return in_array($type, self::RoutePartTypes);
	}

	/**
	 * See if $string matches $type
	 * @param string $string
	 * @param string $type
	 * @return bool
	 */
	public function MatchesType($string, $type)
	{
		switch ($type) {
			case 'int':
			case 'integer':
				return is_numeric($string);
			case 'bool':
			case 'boolean':
				return in_array(strtolower($string), ['true', 'false', '0', '1']);
		}
	}

	/**
	 * Force $type from $string
	 * @param string $string
	 * @param string $type
	 * @return mixed
	 */
	public function ForceType($string, $type)
	{
		switch ($type) {
			case 'int':
			case 'integer':
				return intval($string);
			case 'bool':
			case 'boolean':
				return boolval($string);
		}
	}

	/**
	 * Match $uri to $routeMap and return the best match.
	 * Otherwise return false.
	 * TODO Make more effective and add point system so that every static value
	 * TODO will get more points than a variable
	 * TODO Add support for wildcard catchall
	 * @param string $uri
	 * @param array $routeMap
	 * @return Closure|bool
	 */
	public function GetMatchingRoute($uri, $routeMap)
	{
		$parsedUri = $this->ParseRoute($uri);
		$matchingRoute = false;

		foreach($routeMap as $routeUri => $route) {
			$parsedRoute = $this->ParseRoute($routeUri);
			if (count($parsedRoute) < count($parsedUri))
				continue;

			$matchingRoute = [self::Route => $route, self::RouteParameters => []];
			foreach ($parsedRoute as $key => $routePart) {
				$uriPart = OnePHP::ValueIfExists($key, $parsedUri, false);

				// Validate delimiter so they are the same
				if ($this->isDelimiter($routePart)) {
					if ($routePart == $uriPart || !$uriPart)
						continue;
					else {
						$matchingRoute = false;
						break;
					}
				}

				// Validate as route part
				$parsedPart = $this->ParseRoutePart($routePart);
				if ($parsedPart->IsStatic()) {
					if ($routePart == $uriPart)
						continue;
					else {
						$matchingRoute = false;
						break;
					}
				}

				if ($uriPart) {
					$matchingRoute[self::RouteParameters][$parsedPart->GetName()] = $uriPart;
					continue;
				}

				if ($parsedPart->IsOptional() && !$uriPart) {
					$defaultValue = $parsedPart->GetDefaultValue();
					if ($defaultValue)
						$matchingRoute[self::RouteParameters][$parsedPart->GetName()] = $defaultValue;
					continue;
				}

				$matchingRoute = false;
				break;
			}

			if ($matchingRoute)
				break;
		}

		return $matchingRoute;
	}
}