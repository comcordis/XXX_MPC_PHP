<?php

class XXX_MPC_Destination
{	
	public $currentPartIndex = 0;
	
	public $rawRoute = '';
	public $rawRouteParts = '';
	
	public $rewrittenRoute = '';
	public $rewrittenRouteParts = array();
	
	public $canonicalRoute = '';
	public $canonicalRouteParts = array();
	
	public $canonicalModuleName = '';
	public $canonicalModulePathParts = array();
	public $canonicalModulePathPrefix = '';
	public $parsedModule = false;
	
	public $canonicalControllerName = '';
	public $parsedController = false;
	
	public $canonicalActionName = '';
	public $parsedAction = false;
	
	public $argumentsString = '';
	public $arguments = array();
	public $parsedArguments = false;
	
	public $relative = false;
	
	public $presenterContext = false;
	
	public $fullyTraversedRouteParts = false;
	
	public $executed = false;
	
	public $pathPrefixes = array();
	
	public $error = false;
	
	public function __construct ($route = '', $presenterContext = false)
	{
		$this->rawRoute = $route;
		$this->rawRouteParts = XXX_String::splitToArray($this->rawRoute, '/');
		
		$this->rewrittenRoute = XXX_MPC_Router::processRouteRewrites($route);
		$this->rewrittenRouteParts = XXX_String::splitToArray($this->rewrittenRoute, '/');
		
		$this->presenterContext = $presenterContext;
		
		$this->canonicalModulePathPrefix = XXX::$deploymentInformation['deployPathPrefix'];
	}
	
	public function traverseNextRoutePart ()
	{		
		while (true)
		{			
			$this->findAndInitializeModule();
			
			if ($this->parsedModule)
			{
				$this->findAndLoadController();
				
				if ($this->parsedController)
				{
					$this->findAction();
					
					if ($this->parsedAction)
					{
						$this->findArguments();
					}
				}
			}
			
			// Something went wrong or we're already done
			if ($this->error || $this->fullyTraversedRouteParts)
			{
				break;
			}
		}
		
		if (!$this->executed)
		{
			if ($this->error)
			{
				if (XXX_MPC_Router::$invalidRouteRoute == '')
				{
					trigger_error($this->error);
				}
				else if (XXX_MPC_Router::$invalidRouteRoute != $this->rawRoute)
				{				
					XXX_MPC_Router::executeRoute(XXX_MPC_Router::$invalidRouteRoute, false);
				}
				
				$this->executed = true;
			}
			else if ($this->fullyTraversedRouteParts)
			{
				$this->execute();
			}
		}
	}
	
	public function tryRewritingRouteRemainder ($where = '')
	{
		// Determine the remainder
		$tempRewrittenRoutePartsRemainder = array_slice($this->rewrittenRouteParts, $this->currentPartIndex);
		$tempRewrittenRouteRemainder = implode('/', $tempRewrittenRoutePartsRemainder);
		
		// Pop the origin remainder
		$this->rewrittenRouteParts = array_slice($this->rewrittenRouteParts, 0, $this->currentPartIndex);
		$this->rewrittenRoute = implode('/', $this->rewrittenRouteParts);
		
		// Do the callbacks 
		$tempRewrittenRouteRemainder = XXX_MPC_Router::processRouteCallbacks($tempRewrittenRouteRemainder, $this->canonicalRouteParts);		
		
		// Do the rewriting
		$tempRewrittenRouteRemainder = XXX_MPC_Router::processRouteRewrites($tempRewrittenRouteRemainder, $this->canonicalRouteParts);
		$tempRewrittenRoutePartsRemainder = explode('/', $tempRewrittenRouteRemainder);
		
		// Append the new remainder
		if ($tempRewrittenRouteRemainder != '')
		{
			if ($this->rewrittenRoute == '')
			{
				$this->rewrittenRoute = $tempRewrittenRouteRemainder;
			}
			else
			{
				$this->rewrittenRoute .= '/' . $tempRewrittenRouteRemainder;
			}
		
			foreach ($tempRewrittenRoutePartsRemainder as $tempRewrittenRoutePartRemainder)
			{
				$this->rewrittenRouteParts[] = $tempRewrittenRoutePartRemainder;
			}
		}
	}
	
	public function findAndInitializeModule ()
	{
		if (!$this->parsedModule)
		{
			$this->tryRewritingRouteRemainder('module');
			
			$currentPart = $this->rewrittenRouteParts[$this->currentPartIndex];
			
			$traversedModule = false;
			
			$alternatives = array();
			// As is
			$alternatives[] = $currentPart;
			// Aliassed
			$alternatives[] = XXX_MPC_Router::processModuleAliasses($currentPart, $this->canonicalRouteParts);
			
			foreach ($alternatives as $alternative)
			{
				if (XXX_FileSystem_Local::doesDirectoryExist(XXX_Path_Local::extendPath($this->canonicalModulePathPrefix, XXX_MPC_Router::$directoryNames['modules'] . XXX_OperatingSystem::$directorySeparator . $alternative)))
				{
					$traversedModule = true;
					
					$this->canonicalModuleName = $alternative;
					$this->canonicalModulePathPrefix .= XXX_MPC_Router::$directoryNames['modules'] . XXX_OperatingSystem::$directorySeparator . $this->canonicalModuleName . XXX_OperatingSystem::$directorySeparator;
					$this->canonicalModulePathParts[] = $this->canonicalModuleName;
					$this->canonicalRouteParts[] = $this->canonicalModuleName;
					
					++$this->currentPartIndex;
					break;
				}
			}
			
			
			
			// Most likely at the final module	
			if (!$traversedModule)
			{
				$this->parsedModule = true;
			}
			// A module in the path to the destination module, try to load the initializer
			else
			{
				$beforeCurrentPartIndex = $this->currentPartIndex;
				
				$mimicModuleInitializer = false;
				
				$tempModule = XXX_Array::joinValuesToString($this->canonicalModulePathParts, XXX_OperatingSystem::$directorySeparator);
								
				if (!XXX_Array::hasValue(XXX_MPC_Router::$initializedModules, $tempModule))
				{
					// This should be before the include because otherwise in the subroute it will never find it as loaded...
					XXX_MPC_Router::$initializedModules[] = $tempModule;
										
					$moduleInitializer = 'initialize.php';
					
					if (XXX_MPC_Router::$defaultModuleInitializer != '')
					{
						$moduleInitializer = XXX_MPC_Router::$defaultModuleInitializer;
					}
					
					if ((include_once XXX_Path_Local::extendPath($this->canonicalModulePathPrefix, $moduleInitializer)))
					{
						// The initializer didn't continue the route
						if ($beforeCurrentPartIndex == $this->currentPartIndex)
						{
							$mimicModuleInitializer = true;
						}
					}
					else
					{
						if (!XXX_MPC_Router::$requireModuleInitializer)
						{
							$mimicModuleInitializer = true;
						}
						else
						{
							$this->error = 'invalidInitializer';
							
							trigger_error('No initializer for ' . $this->canonicalModulePathPrefix);
						}
					}
										
					if ($mimicModuleInitializer)
					{
						XXX_Path_Local::addDefaultIncludePathsForPath($this->canonicalModulePathPrefix);
							
						// Keep traversing the route
					}
				}
			}
			
			$this->canonicalRoute = XXX_Array::joinValuesToString($this->canonicalRouteParts, '/');
		}
	}
	
	public function findAndLoadController ()
	{
		if (!$this->parsedController)
		{
			$this->tryRewritingRouteRemainder('controller');
			
			$currentPart = $this->rewrittenRouteParts[$this->currentPartIndex];
			
			// TODO if the part or aliassed part is a subDirectory? Traverse it untill it's not, is this benificial? Only for presenters probably, but then it's ok.
			
			$partAlternatives = array();
			$partAlternatives[] = $currentPart;
			$partAlternatives[] = XXX_MPC_Router::processControllerAliasses($currentPart, $this->canonicalRouteParts);
			if (XXX_MPC_Router::$defaultController != '')
			{
				$partAlternatives[] = XXX_MPC_Router::$defaultController;
			}
			
			
			$alternatives = array();
			
			foreach ($partAlternatives as $partAlternative)
			{			
				// As is
				$alternatives[] = $partAlternative;
				
				$tempPrefix =  XXX::$deploymentInformation['project'] . '_Controller_';
				if (!XXX_String::beginsWith($partAlternative, $tempPrefix))
				{
					// Capitalized name, e.g. Www
					$alternatives[] = $tempPrefix . XXX_String::capitalizeFirstWord($partAlternative);
					// Upper case name, e.g. WWW
					$alternatives[] = $tempPrefix . XXX_String::convertToUpperCase($partAlternative);
									
					// Capitalized module name, e.g. Www
					$alternatives[] = $tempPrefix . XXX_String::capitalizeFirstWord($this->canonicalModuleName);
					// Upper case module name, e.g. WWW
					$alternatives[] = $tempPrefix . XXX_String::convertToUpperCase($this->canonicalModuleName);
					
					// Same principe with parent modules included in the namespace
					if (XXX_Array::getFirstLevelItemTotal($this->canonicalModulePathParts) > 0)
					{
						$tempPart = '';
						for ($i = 0, $iEnd = XXX_Array::getFirstLevelItemTotal($this->canonicalModulePathParts); $i < $iEnd; ++$i)
						{
							if ($i > 0)
							{
								$tempPart .= '_';
							}
							
							$tempPart .= XXX_String::capitalizeFirstWord($this->canonicalModulePathParts[$i]);
						}
						
						// Capitalized parent module, e.g. FirstParentModule_SecondParentModule_Www
						$alternatives[] = $tempPrefix . ($tempPart != '' ? $tempPart . '_' : '') . XXX_String::capitalizeFirstWord($partAlternative);
						// Upper case parent module, e.g. FirstParentModule_SecondParentModule_WWW
						$alternatives[] = $tempPrefix . ($tempPart != '' ? $tempPart . '_' : '') . XXX_String::convertToUpperCase($partAlternative);
						
						// Capitalized module name, e.g. FirstParentModule_SecondParentModule_Module
						$alternatives[] = $tempPrefix . $tempPart;
						// Upper case module name, e.g. FirstParentModule_SecondParentModule_Module
						$alternatives[] = $tempPrefix . $tempPart;
					}
				}
			}
			
			foreach ($alternatives as $alternative)
			{
				if (XXX_FileSystem_Local::doesFileExist(XXX_Path_Local::extendPath($this->canonicalModulePathPrefix, XXX_MPC_Router::$directoryNames['controllers'] . XXX_OperatingSystem::$directorySeparator . $alternative . '.php')))
				{
					$this->parsedController = true;
					
					$this->canonicalControllerName = $alternative;
					
					$this->canonicalRouteParts[] = $this->canonicalControllerName;
					
					include_once XXX_Path_Local::extendPath($this->canonicalModulePathPrefix, XXX_MPC_Router::$directoryNames['controllers'] . XXX_OperatingSystem::$directorySeparator . $this->canonicalControllerName . '.php');
					
					++$this->currentPartIndex;
					break;
				}
			}
						
			$this->canonicalRoute = XXX_Array::joinValuesToString($this->canonicalRouteParts, '/');
			
			if (!$this->parsedController)
			{
				$this->error = 'invalidController';
			}
		}
	}
	
	public function findAction ()
	{
		if (!$this->parsedAction)
		{
			$this->tryRewritingRouteRemainder('action');
			
			$currentPart = $this->rewrittenRouteParts[$this->currentPartIndex];
			
			$alternatives = array();
			// As is
			$alternatives[] = $currentPart;
			// Aliassed
			$alternatives[] = XXX_MPC_Router::processActionAliasses($currentPart, $this->canonicalRouteParts);
			if (XXX_MPC_Router::$defaultAction != '')
			{
				// Default
				$alternatives[] = XXX_MPC_Router::$defaultAction;
			}
			
			foreach ($alternatives as $alternative)
			{
				if (method_exists($this->canonicalControllerName, $alternative))
				{
					$this->parsedAction = true;
					
					$this->canonicalActionName = $alternative;
					
					$this->canonicalRouteParts[] = $this->canonicalActionName;
					
					++$this->currentPartIndex;
					break;
				}
			}
						
			$this->canonicalRoute = XXX_Array::joinValuesToString($this->canonicalRouteParts, '/');
			
			if (!$this->parsedAction)
			{
				$this->error = 'invalidAction';
			}
		}
	}
	
	public function findArguments ()
	{
		if (!$this->parsedArguments)
		{
			$j = 0;
			
			for ($i = $this->currentPartIndex, $iEnd = XXX_Array::getFirstLevelItemTotal($this->rewrittenRouteParts); $i < $iEnd; ++$i)
			{
				if ($j > 0)
				{
					$this->argumentsString .= '/';
				}
				
				$this->argumentsString .= $this->rewrittenRouteParts[$i];
				
				$this->arguments[] = $this->rewrittenRouteParts[$i];
				
				$this->canonicalRouteParts[] = $this->rewrittenRouteParts[$i];
				
				++$j;
			}
			
			$this->parsedArguments = true;
			
			$this->fullyTraversedRouteParts = true;
			
			$this->canonicalRoute = XXX_Array::joinValuesToString($this->canonicalRouteParts, '/');
			
			$this->composePaths();
		}
	}
	
	public function composePaths ()
	{
		$this->pathPrefixes['globalModulePathPrefix'] = XXX_Path_Local::$deploymentSourcePathPrefix;
		$this->pathPrefixes['globalControllersPathPrefix'] = XXX_Path_Local::$deploymentSourcePathPrefix . XXX_MPC_Router::$directoryNames['controllers'] . XXX_OperatingSystem::$directorySeparator;
		$this->pathPrefixes['globalModelsPathPrefix'] = XXX_Path_Local::$deploymentSourcePathPrefix . XXX_MPC_Router::$directoryNames['models'] . XXX_OperatingSystem::$directorySeparator;
		$this->pathPrefixes['globalPresentersPathPrefix'] = XXX_Path_Local::$deploymentSourcePathPrefix . XXX_MPC_Router::$directoryNames['presenters'] . XXX_OperatingSystem::$directorySeparator;
		$this->pathPrefixes['globalModulesPathPrefix'] = XXX_Path_Local::$deploymentSourcePathPrefix . XXX_MPC_Router::$directoryNames['modules'] . XXX_OperatingSystem::$directorySeparator;
		
		$this->pathPrefixes['modulePathPrefix'] = $this->canonicalModulePathPrefix;
		$this->pathPrefixes['controllersPathPrefix'] = $this->canonicalModulePathPrefix . XXX_MPC_Router::$directoryNames['controllers'] . XXX_OperatingSystem::$directorySeparator;
		$this->pathPrefixes['modelsPathPrefix'] = $this->canonicalModulePathPrefix . XXX_MPC_Router::$directoryNames['models'] . XXX_OperatingSystem::$directorySeparator;		
		$this->pathPrefixes['presentersPathPrefix'] = $this->canonicalModulePathPrefix . XXX_MPC_Router::$directoryNames['presenters'] . XXX_OperatingSystem::$directorySeparator;
		$this->pathPrefixes['modulesPathPrefix'] = $this->canonicalModulePathPrefix . XXX_MPC_Router::$directoryNames['modules'] . XXX_OperatingSystem::$directorySeparator;
		
		
		$this->pathPrefixes['globalPresentersURIPathPrefix'] = XXX_URI::$staticURIPathPrefix . XXX::$deploymentInformation['project'] . '/' . XXX_MPC_Router::$directoryNames['presenters'] . '/';
		$this->pathPrefixes['presentersURIPathPrefix'] = XXX_URI::$staticURIPathPrefix . XXX::$deploymentInformation['project'] . '/' . XXX_MPC_Router::$directoryNames['modules'] . '/' . implode('/' . XXX_MPC_Router::$directoryNames['modules'] . '/', $this->canonicalModulePathParts) . '/' . XXX_MPC_Router::$directoryNames['presenters'] . '/';
				
		// Strip the arguments off of the raw route parts, remaining the raw route parts up to the action
			
		$argumentsTotal = XXX_Array::getFirstLevelItemTotal($this->arguments);
		
		$tempRawRouteParts = $this->rawRouteParts;
		
		for ($i = 0, $iEnd = $argumentsTotal; $i < $iEnd; ++$i)
		{
			array_pop($tempRawRouteParts);
		}
		
		if (XXX_PHP::$executionEnvironment == 'httpServer')
		{
			$actionPrefix = '';
			$controllerPrefix = '';
			
			if (XXX_Array::getFirstLevelItemTotal($tempRawRouteParts))
			{
				$actionPrefix = XXX_Array::joinValuesToString($tempRawRouteParts, '/');
				
				array_pop($tempRawRouteParts);
				
				if (XXX_Array::getFirstLevelItemTotal($tempRawRouteParts))
				{
					$controllerPrefix = XXX_Array::joinValuesToString($tempRawRouteParts, '/');
				}
			}
		}
	}
	
	public function execute ()
	{
		if (!$this->executed)
		{
			if ($this->parsedModule && $this->parsedController && $this->parsedAction && $this->fullyTraversedRouteParts)
			{
				// TODO controller factory ???
				$controllerInstance = new $this->canonicalControllerName();
				$controllerInstance->setDestination($this);
				$controllerInstance->setPresenterContext($this->presenterContext);
							
				call_user_func(array($controllerInstance, $this->canonicalActionName), $this->arguments);
				
				$this->executed = true;
			}
		}
	}
	
	public function getRoute ()
	{
		return $this->rewrittenRoute;
	}
	
	public function getRouteParts ()
	{
		return $this->rewrittenRouteParts;
	}
	
	public function hasPart ($part = '')
	{
		return XXX_Array::hasValue($this->rewrittenRouteParts, $part);
	}
	
	public function getModule ()
	{
		return $this->canonicalModuleName;
	}
	
	public function getModuleDepth ()
	{
		return XXX_Array::getFirstLevelItemTotal($this->canonicalModulePathParts);
	}
	
	public function getController ()
	{
		return $this->canonicalControllerName;
	}
	
	public function getAction ()
	{
		return $this->canonicalActionName;
	}
	
	public function getArguments ()
	{
		return $this->arguments;
	}
	
	public function getFirstArgument ()
	{
		return $this->arguments[0];
	}
		
	/*
	
	- current
		- module
		- controllers
		- models
		- presenters
		- modules
		- navigationAction
		- navigationController
	- global
		- globalModule
		- globalControllers
		- globalModels
		- globalPresenters
		- globalModules
	*/
	
	public function getPathPrefix ($key = '')
	{
		return $this->pathPrefixes[$key . 'PathPrefix'];
	}
	
	public function getURIPathPrefix ($key = '')
	{
		return $this->pathPrefixes[$key . 'URIPathPrefix'];
	}
}

?>