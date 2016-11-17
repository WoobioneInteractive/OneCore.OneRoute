<?php

/**
 * A fast router for OneCore
 * @author A.G. Netterwall <netterwall@gmail.com>
 */
class OneRoute implements IPlugin
{
	// Internal constants
	const RouterInterface = 'IRouter';
	const Router = 'OneRouter';

	public function __construct(DependencyInjector $di)
	{
		// Register this as implemented instance of IRouter
		$di->AddMapping(new DependencyMappingFromArray([
			self::RouterInterface => [
				DependencyInjector::Mapping_ResolveTo => self::Router
			]
		]));
	}
}