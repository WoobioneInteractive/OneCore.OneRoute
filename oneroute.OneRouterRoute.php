<?php

class OneRouterRoute
{
	/**
	 * @var OneRouterRoutePart[]
	 */
	private $parts = [];

	/**
	 * @var array
	 */
	private $parameters = [];

	/**
	 * @var array
	 */
	private $usedParameters = [];

	/**
	 * @var callable
	 */
	public $Action;

	/**
	 * OneRouterRoute constructor.
	 * @param callable $action
	 */
	public function __construct(callable $action)
	{
		$this->Action = $action;
	}

	/**
	 * Add route part to route
	 * @param OneRouterRoutePart $routePart
	 */
	public function AddPart(OneRouterRoutePart $routePart)
	{
		array_push($this->parts, $routePart);
	}

	/**
	 * Get full score for route
	 * @return int
	 */
	public function GetScore()
	{
		$score = 0;
		foreach ($this->parts as $key => $routePart) {
			$score += $routePart->GetScore();
		}
		return $score;
	}

	/**
	 * Get all parameters found in the route
	 * @return array
	 */
	public function GetParameters()
	{
		if (empty($this->parameters)) {
			foreach ($this->parts as $routePart) {
				$name = $routePart->GetName();
				if ($name)
					$this->parameters[$name] = $routePart->GetValue();
			}
		}

		return $this->parameters;
	}

	/**
	 * Get single parameter by name
	 * @param string $parameterName
	 * @return mixed
	 */
	public function GetParameter($parameterName)
	{
		$parameter = OnePHP::ValueIfExists($parameterName, $this->GetParameters());
		array_push($this->usedParameters, $parameterName);
		return $parameter;
	}

	/**
	 * Get parameters that are still unused
	 * @return array
	 */
	public function GetUnusedParameters()
	{
		$parameters = $this->GetParameters();
		foreach ($this->usedParameters as $parameterName) {
			unset($parameters[$parameterName]);
		}
		return $parameters;
	}
}