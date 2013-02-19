<?php

/*

Route rewrites should be in the base, such as 1 word expanding to something x modules deep to a certain action
Module aliasses should be in the parent of the module
Controller aliasses should be in the module itself
Action aliasses should be in the module itself

2 situations:
- server
- front
	- load module
		- initializer
			- configurations

i18n should only be loaded when needed


Rewrite router to cut off part, and let submodule handle the rest

TODO: Fix that a rewrite of emailAddressValidation to emailAddressValidation/emailAddressValidation is possible

*/

abstract class XXX_MPC_Router
{
	public static $directoryNames = array
	(
		'controllers' => 'controllers',
		'models' => 'models',
		'presenters' => 'presenters',
		'modules' => 'modules'
	);
	
	public static $requireModuleInitializer = false;
	public static $defaultModuleInitializer = 'initialize.php';	
	public static $defaultController = 'Main';
	public static $defaultAction = 'index';
	
	public static $invalidRouteRoute = '';
	
	public static $routeRewrites = array();
	
	public static $moduleAliasses = array();
	public static $controllerAliasses = array();
	public static $actionAliasses = array();
	
	public static $initializedModules = array();
	
	public static $destinations = array();
	
	public static function initialize ($route = '')
	{
		if (XXX_Array::getFirstLevelItemTotal(self::$destinations) == 0)
		{
			if ($route == '')
			{
				$route = XXX_MPC_EntryPointRoute::getEntryPointRoute();
			}
						
			self::executeRoute($route, false);
		}
		else
		{
			$tempDestination = self::getCurrentDestination();
			
			if ($tempDestination)
			{
				// Traverse 1 level deeper.
				$tempDestination->traverseNextRoutePart();
			}
		}
	}
	
	public static function executeRoute ($route = '', $relative = true, $inheritPresenterContext = false)
	{
		$result = false;
		
		if ($route != '')
		{
			if ($relative)
			{
				if ($relative !== true)
				{
					$tempDestination = $relative;
				}
				else
				{
					$tempDestination = self::getCurrentDestination();				
				}
								
				if ($tempDestination)
				{
					$tempParts = $tempDestination->canonicalModulePathParts;
					
					while (true)
					{
						if (XXX_String::beginsWith($route, '../'))
						{
							if (XXX_Array::getFirstLevelItemTotal($tempParts) > 0)
							{
								$route = XXX_String::getPart($route, 3);
								array_pop($tempParts);
							}
							else
							{
								// TODO trigger event, too deep relative path
								
								break;
							}
						}
						else
						{
							break;
						}
					}
					
					$tempRoute = '';
					
					$tempRoute .= XXX_Array::joinValuesToString($tempParts, '/');
					
					if ($tempRoute != '')
					{
						$tempRoute .= '/';
					}
					
					$route = $tempRoute . $route;
				}
			}
			
			$presenterContext = false;
			
			if ($inheritPresenterContext)
			{
				if ($inheritPresenterContext !== true)
				{
					$presenterContext = $inheritPresenterContext;
				}
				else
				{
					if (!$tempDestination)
					{
						$tempDestination = self::getEntryDestination();		
					}
					
					if ($tempDestination)
					{
						$presenterContext = $tempDestination->presenterContext;
					}
				}
			}
						
			$destination = new XXX_MPC_Destination($route, $presenterContext);
						
			self::$destinations[] = $destination;
			
				$destination->traverseNextRoutePart();
						
			$result = true;
		}
		
		return $result;
	}
	
		public static function getCurrentDestination ()
		{
			$tempDestination = self::$destinations[XXX_Array::getFirstLevelItemTotal(self::$destinations) - 1];
			return $tempDestination ? $tempDestination : false;
		}
		
		public static function getPreviousDestination ()
		{
			$tempDestination = self::$destinations[XXX_Array::getFirstLevelItemTotal(self::$destinations) - 2];
			return $tempDestination ? $tempDestination : false;
		}
	
		public static function getEntryDestination ()
		{
			$tempDestination = self::$destinations[0];
			return $tempDestination ? $tempDestination : false;
		}
		
		public static function getCurrentDestinationModulePathPrefix ()
		{
			$result = false;
			
			$currentDestination = self::getCurrentDestination();
			
			if ($currentDestination)
			{
				$result = $currentDestination->canonicalModulePathPrefix;
			}
			
			return $result;
		}
		
	public static function setInvalidRouteRoute ($invalidRouteRoute = '', $addExecutionEnvironmentPrefix = true)
	{
		$invalidRouteRoute = self::cleanRoute($invalidRouteRoute);
		
		if ($addExecutionEnvironmentPrefix)
		{
			$defaultEntryPointRoute = XXX_MPC_EntryPointRoute::addExecutionEnvironmentPrefixToRoute($defaultEntryPointRoute);
		}
		
		self::$invalidRouteRoute = $invalidRouteRoute;
	}
	
	public static function getCurrentParentCanonicalRouteParts ()
	{
		$result = false;
			
		$currentDestination = self::getCurrentDestination();
		
		if ($currentDestination)
		{
			$result = $currentDestination->canonicalRouteParts;
		}
		
		return $result;
	}
	
	public static function addRouteRewrite ($original = '', $rewrite = '', $type = 'stringBegin', $last = true, $parentCanonicalRouteParts = false)
	{
		$type = XXX_Default::toOption($type, array('stringBegin', 'string', 'pattern'), 'stringBegin');
		
		$rewrite = self::cleanRoute($rewrite);
		
		if ($parentCanonicalRouteParts == false)
		{
			$parentCanonicalRouteParts = self::getCurrentParentCanonicalRouteParts();
		}
				
		self::$routeRewrites[] = array
		(
			'parentCanonicalRouteParts' => $parentCanonicalRouteParts,
			'type' => $type,
			'original' => $original,
			'rewrite' => $rewrite,
			'last' => $last
		);
	}
	
	
	public static function addModuleAlias ($alias = '', $original = '', $last = true, $parentCanonicalRouteParts = false)
	{
		if ($parentCanonicalRouteParts == false)
		{
			$parentCanonicalRouteParts = self::getCurrentParentCanonicalRouteParts();
		}
			
		self::$moduleAliasses[] = array
		(
			'parentCanonicalRouteParts' => $parentCanonicalRouteParts,
			'original' => $original,
			'alias' => $alias,
			'last' => $last
		);
	}
	
	public static function addControllerAlias ($alias = '', $original = '', $last = true, $parentCanonicalRouteParts = false)
	{
		if ($parentCanonicalRouteParts == false)
		{
			$parentCanonicalRouteParts = self::getCurrentParentCanonicalRouteParts();
		}
			
		self::$controllerAliasses[] = array
		(
			'parentCanonicalRouteParts' => $parentCanonicalRouteParts,
			'original' => $original,
			'alias' => $alias,
			'last' => $last
		);
	}
	
	public static function addActionAlias ($alias = '', $original = '', $last = true, $parentCanonicalRouteParts = false)
	{
		if ($parentCanonicalRouteParts == false)
		{
			$parentCanonicalRouteParts = self::getCurrentParentCanonicalRouteParts();
		}
			
		self::$actionAliasses[] = array
		(
			'parentCanonicalRouteParts' => $parentCanonicalRouteParts,
			'original' => $original,
			'alias' => $alias,
			'last' => $last
		);
	}
	
	// false for every module on any level
	// array() for project root
	// array('httpServer', 'www') for httpServer/www
	public static function comparseParentCanonicalRouteParts ($original = array(), $comparison = array())
	{
		$result = false;
		
		if (XXX_Type::isArray($original))
		{
			if (XXX_Type::isArray($comparison))
			{
				$differences = array_diff($original, $comparison);
				
				if (XXX_Array::getFirstLevelItemTotal($differences) == 0 && XXX_Array::getFirstLevelItemTotal($original) == XXX_Array::getFirstLevelItemTotal($comparison))
				{
					$result = true;
				}
			}
		}
		else
		{
			$result = true;
		}
		
		return $result;
	}
	
	public static function processModuleAliasses ($module = '', $parentCanonicalRouteParts = array())
	{
		if ($module != '')
		{
			foreach (self::$moduleAliasses as $moduleAlias)
			{
				$matched = false;
				
				if (self::comparseParentCanonicalRouteParts($moduleAlias['parentCanonicalRouteParts'], $parentCanonicalRouteParts))
				{
					if ($module == $moduleAlias['alias'])
					{
						$module = $moduleAlias['original'];
					}
					
					if ($matched)
					{
						if ($moduleAlias['last'])
						{
							break;
						}
					}
				}
			}
		}
		
		return $module;
	}
	
	public static function processControllerAliasses ($controller = '', $parentCanonicalRouteParts = array())
	{
		if ($controller != '')
		{
			foreach (self::$controllerAliasses as $controllerAlias)
			{
				$matched = false;
				
				if (self::comparseParentCanonicalRouteParts($controllerAlias['parentCanonicalRouteParts'], $parentCanonicalRouteParts))
				{
					if ($controller == $controllerAlias['alias'])
					{
						$controller = $controllerAlias['original'];
					}
					
					if ($matched)
					{
						if ($controllerAlias['last'])
						{
							break;
						}
					}
				}
			}
		}
		
		return $controller;
	}
	
	public static function processActionAliasses ($action = '', $parentCanonicalRouteParts = array())
	{
		if ($action != '')
		{
			foreach (self::$actionAliasses as $actionAlias)
			{
				$matched = false;
				
				if (self::comparseParentCanonicalRouteParts($actionAlias['parentCanonicalRouteParts'], $parentCanonicalRouteParts))
				{
					if ($action == $actionAlias['alias'])
					{
						$action = $actionAlias['original'];
					}
					
					if ($matched)
					{
						if ($actionAlias['last'])
						{
							break;
						}
					}
				}
			}
		}
		
		return $action;
	}
	
	
	public static function processRouteRewrites ($route = '', $parentCanonicalRouteParts = array())
	{
		if ($route != '')
		{
			foreach (self::$routeRewrites as $routeRewrite)
			{
				$matched = false;
				
				if (self::comparseParentCanonicalRouteParts($routeRewrite['parentCanonicalRouteParts'], $parentCanonicalRouteParts))
				{
					switch ($routeRewrite['type'])
					{
						// Only once
						case 'stringBegin':						
							if (XXX_String::beginsWith($route, $routeRewrite['original']))
							{
								$originalPartCharacterLength = XXX_String::getCharacterLength($routeRewrite['original']);
								
								$route = $routeRewrite['rewrite'] . XXX_String::getPart($route, $originalPartCharacterLength);
								
								$matched = true;
							}
							break;
						// Can multiple times in multiple places in the path
						case 'string':
							if (XXX_String::findFirstPosition($route, $routeRewrite['rewrite']) !== false)
							{
								trigger_error('Route rewrite "' . $routeRewrite['rewrite'] . '" contains same part as original "' . $routeRewrite['original'] . '", and causes an infinite loop.');
							}
							else
							{
								while (true)
								{
									$firstPosition = XXX_String::findFirstPosition($route, $routeRewrite['original']);
									
									if ($firstPosition !== false)
									{
										$originalPartCharacterLength = XXX_String::getCharacterLength($routeRewrite['original']);
										
										$originalPrefix = '';
										if ($firstPosition > 0)
										{
											$originalPrefix = XXX_String::getPart($route, 0, $firstPosition);
										}
										$originalSuffix = XXX_String::getPart($route, $firstPosition + $originalPartCharacterLength);
										
										$route = $originalPrefix . $routeRewrite['rewrite'] . $originalSuffix;							
										
										$matched = true;
									}
									else
									{
										break;
									}
								}
							}
							break;
						case 'pattern':
							$originalRoute = $route;
							
							$route = XXX_String_Pattern::replace($route, $routeRewrite['original']['pattern'], $routeRewrite['original']['patternModifiers'], $routeRewrite['rewrite']);
							
							if (XXX_Type::isNull($route))
							{
								$route = $originalRoute;
							}
							
							if ($originalRoute != $route)
							{
								$matched = true;
							}
							break;
					}
					
					if ($matched)
					{
						if ($routeRewrite['last'])
						{
							break;
						}
					}
				}
			}
		}
		
		return $route;
	}
		
		public static function cleanRoute ($route)
		{
			while (true)
			{
				if (XXX_String::beginsWith($route, '/'))
				{
					$route = XXX_String::getPart($route, 1);
				}
				// TODO trigger event route manipulation
				else if (XXX_String::beginsWith($route, '../'))
				{
					//XXX::dispatchEventToListeners('maliciousRelativePathArgument');
					
					$route = XXX_String::getPart($route, 3);
				}
				else if (XXX_String::beginsWith($route, './'))
				{
					$route = XXX_String::getPart($route, 2);
				}
				else if (XXX_String::endsWith($route, '/'))
				{
					$route = XXX_String::getPart($route, 0, -1);
				}
				else if (XXX_String::hasPart($route, '//'))
				{
					$route = XXX_String::replace($route, '//', '/');
				}
				else
				{
					break;
				}
			}
			
			return $route;
		}
}

?>
