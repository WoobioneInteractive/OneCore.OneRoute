<?php

class OneRouterRoutePart
{
	// Route variable parts
	const ParameterName = 'parameter';
	const Type = 'type';
	const DefaultValue = 'defaultValue';
	const Optional = 'optional';

	// Supported part types
	const Type_String = 'string';
	const Type_Int = 'int';
	const Type_Bool = 'bool';

	// Internal constants
	const RoutePartPrefix = '{';
	const RoutePartSuffix = '}';
	const RoutePartUsableTypes = [
		'string' => self::Type_String,
		'int' => self::Type_Int,
		'integer' => self::Type_Int,
		'bool' => self::Type_Bool,
		'boolean' => self::Type_Bool
	];

	private $routePart;
	private $uriPart;
	private $value;
	private $type = self::Type_String;
	private $isStatic = true;
	private $parameterName;
	private $defaultValue;
	private $isOptional = false;

	/**
	 * RoutePart constructor.
	 * @param string $routePart
	 */
	public function __construct($routePart)
	{
		$this->setRoutePart($routePart);
	}

	/**
	 * Set route part - this will automatically parse it
	 * @param string $routePart
	 */
	private function setRoutePart($routePart)
	{
		$this->routePart = $routePart;
		$this->parse($this->routePart);
	}

	/**
	 * Parse route part
	 * @param string $routePart
	 * @throws OneRouterException
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

		// Handle type
		$type = OnePHP::ValueIfExists(self::Type, $matches, false);
		if ($type) {
			$validatedType = $this->validateType($type);
			if ($validatedType)
				$this->type = $validatedType;
			else
				throw new OneRouterException("Invalid route type found when parsing route '$type'");
		}

		$this->defaultValue = $this->convertToType(OnePHP::ValueIfExists(self::DefaultValue, $matches, $this->defaultValue), $this->type);
		$this->isOptional = !!OnePHP::ValueIfExists(self::Optional, $matches, $this->isOptional);
	}

	/**
	 * Validate type against known types
	 * Return real type if matched - otherwise bool false
	 * @param string $routePartType
	 * @return mixed|bool
	 */
	private function validateType($routePartType)
	{
		return OnePHP::ValueIfExists($routePartType, self::RoutePartUsableTypes, false);
	}

	/**
	 * See if $string matches $this->type
	 * @param string $string
	 * @return bool
	 */
	private function matchesType($string)
	{
		switch ($this->type) {
			case self::Type_String:
				return true;
			case self::Type_Int:
				return is_numeric($string);
			case self::Type_Bool:
				return in_array(strtolower($string), ['true', 'false', '0', '1']);

			default:
				return false;
		}
	}

	/**
	 * Convert $string to $type
	 * @param string $string
	 * @param string $type
	 * @return mixed
	 */
	private function convertToType($string, $type)
	{
		switch ($type) {
			case 'int':
			case 'integer':
				return intval($string);
			case 'bool':
			case 'boolean':
				return boolval($string);

			default:
				return $string;
		}
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
	 * Get parameter type (found in constants: Type_xxx)
	 * @return string
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
		return $this->isOptional || $this->GetDefaultValue();
	}

	/**
	 * Get default value for route part
	 * @return mixed
	 */
	public function GetDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * Get value
	 * @return mixed
	 */
	public function GetValue()
	{
		if (!$this->value)
			$this->value = $this->uriPart ? $this->convertToType($this->uriPart, $this->type) : $this->GetDefaultValue();

		return $this->value;
	}

	/**
	 * Get score for route part
	 * @return int
	 */
	public function GetScore()
	{
		if ($this->IsStatic())
			return 10000;

		if ($this->GetType() != self::Type_String)
			return 5000;

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

		if ($uriPart && !$this->matchesType($uriPart))
			return false;

		$this->uriPart = $uriPart;
		return true;
	}
}