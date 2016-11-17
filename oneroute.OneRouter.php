<?php

class OneRouter implements IRouter
{
	private $request = null;

	public function __construct(IRequest $request)
	{
		$this->request = $request;
	}

	public function GetRequest()
	{
		return $this->request;
	}
}