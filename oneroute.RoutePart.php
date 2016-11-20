<?php

class RoutePart
{
	// Route variable parts
	const ParameterName = 'parameter';
	const Type = 'type';
	const DefaultValue = 'defaultValue';
	const Optional = 'optional';

	// Internal constants
	const RoutePartPrefix = '{';
	const RoutePartSuffix = '}';

	private $parameterName = null;
	private $type = false;
	private $defaultValue = false;
	private $isOptional = false;
	private $isStatic = true;

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

	/**
	 * Get route part name
	 * @return string
	 */
	public function GetName()
	{
		return $this->parameterName;
	}
}