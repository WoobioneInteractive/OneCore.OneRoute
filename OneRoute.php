<?php

/**
 * A fast router for OneCore
 * @author A.G. Netterwall <netterwall@gmail.com>
 */
class OneRoute implements IPlugin, IRouter
{
	// Configuration options
	const Config_PathDelimiters = 'oneroute.pathDelimiters';

	/**
	 * @var IConfiguration
	 */
	private $configuration;

	/**
	 * @var IRequest
	 */
	private $request;

	/**
	 * @var OneRouter
	 */
	private $router;

	/**
	 * OneRoute constructor.
	 * @param IConfiguration $configuration
	 * @param IRequest $request
	 * @param DependencyInjector $di
	 */
	public function __construct(IConfiguration $configuration, IRequest $request, DependencyInjector $di)
	{
		$this->configuration = $configuration;
		$this->request = $request;
		$this->router = new OneRouter();

		// Register this as implemented instance of IRouter
		$di->AddMapping(new DependencyMappingFromArray([
			IRouter::class => [ DependencyInjector::Mapping_RemoteInstance => $this ]
		]));
	}

	public function Get($uri, $do)
	{
		return $this->router->Get($uri, $do);
	}

	public function Post($uri, $do)
	{
		return $this->router->Post($uri, $do);
	}

	public function Put($uri, $do)
	{
		return $this->router->Put($uri, $do);
	}

	public function Patch($uri, $do)
	{
		return $this->router->Patch($uri, $do);
	}

	public function Delete($uri, $do)
	{
		return $this->router->Delete($uri, $do);
	}

	public function Options($uri, $do)
	{
		return $this->router->Options($uri, $do);
	}

	public function CatchAll($uri, $do)
	{
		return $this->router->CatchAll($uri, $do);
	}

	public function Match(Array $requestMethods, $uri, $do)
	{
		return $this->router->Match($requestMethods, $uri, $do);
	}

	public function Route()
	{
		$this->router->Route($this->request->GetRequestString(), $this->request->GetMethod());
	}
}