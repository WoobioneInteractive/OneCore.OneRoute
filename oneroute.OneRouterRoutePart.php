<?php

class OneRouterRoutePart
{
	// Route variable parts
	const ParameterName = 'parameter';
	const Type = 'type';
	const DefaultValue = 'defaultValue';
	const Optional = 'optional';

	// Internal constants
	const RoutePartPrefix = '{';
	const RoutePartSuffix = '}';
	const RoutePartTypes = [
		'int',
		'integer',
		'bool',
		'boolean'
	];

	private $parameterName = null;
	private $type = false;
	private $defaultValue = false;
	private $isOptional = false;
	private $isStatic = true;
	private $routePart;
	private $linkedUriPart = false;

	/**
	 * RoutePart constructor.
	 * @param string $routePart
	 */
	public function __construct($routePart)
	{
		$this->parse($routePart);
	}

	/**
	 * Parse route part
	 * @param string $routePart
	 */
	private function parse($routePart)
	{
		$this->routePart = $routePart;

		// No need for calculations if it is a static part
		if (!OnePHP::StringBeginsWith($routePart, self::RoutePartPrefix) || !OnePHP::StringEndsWith($routePart, self::RoutePartSuffix)) {
			return;
		}

		$typePattern = '((?<' . self::Type . '>[a-z]+)[:])?';
		$parameterNamePattern = '(?<' . self::ParameterName . '>[a-zA-Z0-9]+)';
		$defaultValuePattern = '([=](?<' . self::DefaultValue . '>[a-zA-Z0-9]+)?)';
		$optionalPattern = '(?<' . self::Optional . '>[\?])';
		$pattern = "/[{]{$typePattern}{$parameterNamePattern}({$defaultValuePattern}|{$optionalPattern})?[}]/A";

		$matchCount = preg_match($pattern, $routePart, $matches);
		if (!$matchCount)
			return;

		$this->isStatic = false;
		$this->parameterName = $matches[self::ParameterName];

		$type = OnePHP::ValueIfExists(self::Type, $matches, false);
		if ($type)
			$this->type = $type;

		$this->defaultValue = OnePHP::ValueIfExists(self::DefaultValue, $matches, $this->defaultValue);
		$this->isOptional = !!OnePHP::ValueIfExists(self::Optional, $matches, $this->isOptional);
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
	 * @return string|bool
	 */
	public function GetType()
	{
		return $this->type;
	}

	/**
	 * See if route part is static
	 * @return bool
	 */
	public function IsStatic()
	{
		return $this->isStatic;
	}

	/**
	 * See if route part is optional
	 * @return bool
	 */
	public function IsOptional()
	{
		return $this->isOptional || $this->defaultValue;
	}

	/**
	 * Get default value for route part
	 * @return string|bool
	 */
	public function GetDefaultValue()
	{
		return $this->defaultValue;
	}

	public function GetValue()
	{
		return $this->linkedUriPart ?: $this->GetDefaultValue();
	}

	/**
	 * Get route part name
	 * @return string
	 */
	public function GetName()
	{
		return $this->parameterName;
	}

	/**
	 * Get score for route part
	 * @return int
	 */
	public function GetScore()
	{
		if ($this->IsStatic())
			return 10000;

		return 1000;
	}

	/**
	 * Link route part to corresponding value of uri. Returns true if uri matches part
	 * @param string $uriPart
	 * @return bool
	 */
	public function LinkWith($uriPart)
	{
		if (!$uriPart && !$this->IsOptional())
			return false;

		if ($this->IsStatic() && $this->routePart != $uriPart)
			return false;

		$this->linkedUriPart = $uriPart;
		return true;
	}
}