<?php

class XXX_MPC_Destination
{
	public $project = '';
	public $deployIdentifier = 'latest';
	
	public $isStatic = false;
	
	public $currentPartIndex = 0;
	
	public $rawRoute = '';
	public $rawRouteParts = array();
	
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
	
	public function __construct ($project = '', $deployIdentifer = '', $route = '', $presenterContext = false)
	{
		if ($project == false || $project == '')
		{
			$project = XXX::$deploymentInformation['project'];
		}
		
		$this->project = $project;
		
		if ($deployIdentifier == false || $deployIdentifier == '')
		{
			$deployIdentifier = XXX::$deploymentInformation['deployEnvironment'];
		}
		
		$this->deployIdentifier = $deployIdentifier;
		
		$this->rawRoute = $route;
		$this->rawRouteParts = XXX_String::splitToArray($this->rawRoute, '/');
		
		$this->rewrittenRoute = XXX_MPC_Router::processRouteRewrites($route);
		$this->rewrittenRouteParts = XXX_String::splitToArray($this->rewrittenRoute, '/');
		
		$this->presenterContext = $presenterContext;
		
		$this->canonicalModulePathPrefix = XXX_Path_Local::composeOtherProjectDeploymentSourcePathPrefix($this->project, $this->deployIdentifier);
		
	}
	
	public function traverseNextRoutePart ()
	{		
		while (true)
		{
			
			$this->findAndInitializeModule();
			
			if ($this->parsedModule)
			{
				$this->findAndInitializeStatic();
		
				if (!$this->isStatic)
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
					trigger_error($this->error, E_USER_ERROR);
				}
				else if (XXX_MPC_Router::$invalidRouteRoute != $this->rawRoute)
				{
					XXX_MPC_Router::executeRoute(false, false, XXX_MPC_Router::$invalidRouteRoute, false);
				}
				else
				{
					trigger_error($this->error, E_USER_ERROR);
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
	
	public function findAndInitializeStatic ()
	{
		if (!$this->parsedStatic)
		{
			// Strip prefixes first
			$this->tryRewritingRouteRemainder('static');
			
			if ($this->rewrittenRouteParts[0] == 'httpServer')
			{
				//if ($this->rewrittenRouteParts[1] == 'www')
				//{
					if ($this->rewrittenRouteParts[2] == 'static')
					{
						switch ($this->rewrittenRouteParts[3])
						{
							case 'file':
								$route = '';
								
								for ($i = 4, $iEnd = (XXX_Array::getFirstLevelItemTotal($this->rewrittenRouteParts)); $i < $iEnd; ++$i)
								{
									$route .= $this->rewrittenRouteParts[$i];
									
									if ($i < $iEnd - 1)
									{
										$route .= '/';	
									}
								}
								
								$compress = XXX_HTTPServer_Client_Input::getURIVariable('compress', 'boolean');
								
								XXX_Static_HTTPServer::singleFile($route, $compress);
								
								$this->fullyTraversedRouteParts = true;
								
								$this->executed = true;
								
								$this->isStatic = true;
								break;
							case 'combinedFiles':
								$files = XXX_HTTPServer_Client_Input::getURIVariable('files');
								$fileType = XXX_HTTPServer_Client_Input::getURIVariable('fileType');
								$compress = XXX_HTTPServer_Client_Input::getURIVariable('compress', 'boolean');
								
								XXX_Static_HTTPServer::combinedFiles($files, $fileType, $compress);
								
								$this->fullyTraversedRouteParts = true;
								
								$this->executed = true;
								
								$this->isStatic = true;
								break;
						}
					}
				//}
			}
			
			$this->parsedStatic = true;
		}
	}
	
	public function findAndInitializeModule ()
	{
		if (!$this->parsedModule)
		{
			// Strip prefixes first
			$this->tryRewritingRouteRemainder('module');
			
			// If no more parts, try default
			if ($this->currentPartIndex == XXX_Array::getFirstLevelItemTotal($this->rewrittenRouteParts))
			{
				if (XXX_MPC_EntryPointRoute::$defaultEntryPointRoute != '')
				{
					$defaultEntryPointRoute = XXX_MPC_Router::cleanRoute(XXX_MPC_EntryPointRoute::$defaultEntryPointRoute);
					
					$this->rawRoute = XXX_MPC_Router::cleanRoute($this->rawRoute);
					$this->rawRoute .= '/' . $defaultEntryPointRoute;
					
					$this->rewrittenRoute = XXX_MPC_Router::cleanRoute($this->rewrittenRoute);
					$this->rewrittenRoute .= '/' . $defaultEntryPointRoute;
					
					$additionalRouteParts = XXX_String::splitToArray($defaultEntryPointRoute, '/');
					
					foreach ($additionalRouteParts as $additionalRoutePart)
					{
						$this->rawRouteParts[] = $additionalRoutePart;
						$this->rewrittenRouteParts[] = $additionalRoutePart;
					}
				}
			}
			
			// Strip & rewrite prefixes
			$this->tryRewritingRouteRemainder('module');
			
			$currentPart = $this->rewrittenRouteParts[$this->currentPartIndex];
			
			$traversedModule = false;
			
			$alternatives = array();
			// As is
			$alternatives[] = $currentPart;
			// Aliassed
			$unaliassedCurrentPart = XXX_MPC_Router::processModuleAliasses($currentPart, $this->canonicalRouteParts);
			if ($unaliassedCurrentPart != $currentPart)
			{
				$alternatives[] = $unaliassedCurrentPart;
			}
			
			foreach ($alternatives as $alternative)
			{
				if (XXX_FileSystem_Local::doesDirectoryExist(XXX_Path_Local::extendPath($this->canonicalModulePathPrefix, XXX_MPC_Router::$directoryNames['modules'] . XXX_OperatingSystem::$directorySeparator . $alternative)))
				{
					$traversedModule = true;
					
					$this->canonicalModuleName = $alternative;
					$this->canonicalModulePathPrefix .= XXX_MPC_Router::$directoryNames['modules'] . XXX_OperatingSystem::$directorySeparator . $this->canonicalModuleName . XXX_OperatingSystem::$directorySeparator;
					$this->canonicalModulePathParts[] = $this->canonicalModuleName;
					$this->canonicalRouteParts[] = $this->canonicalModuleName;
					$this->canonicalRoute = XXX_Array::joinValuesToString($this->canonicalRouteParts, '/');
					
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
					
					$moduleInitializerFilePath = XXX_Path_Local::extendPath($this->canonicalModulePathPrefix, $moduleInitializer);
					
					XXX_Path_Local::addDefaultIncludePathsForPath($this->canonicalModulePathPrefix);
					
					if ((include_once $moduleInitializerFilePath))
					{
						// The initializer didn't continue the route
						if ($beforeCurrentPartIndex == $this->currentPartIndex)
						{
						}
					}
					else
					{
						if (!XXX_MPC_Router::$requireModuleInitializer)
						{
						}
						else
						{
							$this->error = 'invalidInitializer';
							
							trigger_error('No initializer for ' . $this->canonicalModulePathPrefix);
						}
					}
				}
			}
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
			$unaliassedCurrentPart = XXX_MPC_Router::processControllerAliasses($currentPart, $this->canonicalRouteParts);
			if ($unaliassedCurrentPart != $currentPart)
			{
				$partAlternatives[] = $unaliassedCurrentPart;
			}
			/*if (XXX_MPC_Router::$defaultController != '' && XXX_MPC_Router::$defaultController != $currentPart)
			{
				$partAlternatives[] = XXX_MPC_Router::$defaultController;
			}
			*/
			
			
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
					$this->canonicalRoute = XXX_Array::joinValuesToString($this->canonicalRouteParts, '/');
					
					include_once XXX_Path_Local::extendPath($this->canonicalModulePathPrefix, XXX_MPC_Router::$directoryNames['controllers'] . XXX_OperatingSystem::$directorySeparator . $this->canonicalControllerName . '.php');
					
					++$this->currentPartIndex;
					break;
				}
			}
			
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
			$unaliassedCurrentPart = XXX_MPC_Router::processActionAliasses($currentPart, $this->canonicalRouteParts);
			if ($unaliassedCurrentPart != $currentPart)
			{
				$alternatives[] = $unaliassedCurrentPart;
			}
			/*if (XXX_MPC_Router::$defaultAction != '' && XXX_MPC_Router::$defaultAction != $currentPart)
			{
				// Default
				$alternatives[] = XXX_MPC_Router::$defaultAction;
			}*/
			
			foreach ($alternatives as $alternative)
			{
				if (method_exists($this->canonicalControllerName, $alternative))
				{
					$this->parsedAction = true;
					
					$this->canonicalActionName = $alternative;
					
					$this->canonicalRouteParts[] = $this->canonicalActionName;
					$this->canonicalRoute = XXX_Array::joinValuesToString($this->canonicalRouteParts, '/');
					
					++$this->currentPartIndex;
					break;
				}
			}
						
			
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
				$this->canonicalRoute = XXX_Array::joinValuesToString($this->canonicalRouteParts, '/');
				
				++$j;
			}
			
			$this->parsedArguments = true;
			
			$this->fullyTraversedRouteParts = true;
			
			$this->composePaths();
		}
	}
	
	public function composePaths ()
	{
		$projectDeploymentSourcePathPrefix = XXX_Path_Local::composeOtherProjectDeploymentSourcePathPrefix($this->project, $this->deployIdentifier);
		
		$this->pathPrefixes['projectModulePathPrefix'] = $projectDeploymentSourcePathPrefix;
		$this->pathPrefixes['projectControllersPathPrefix'] = $projectDeploymentSourcePathPrefix . XXX_MPC_Router::$directoryNames['controllers'] . XXX_OperatingSystem::$directorySeparator;
		$this->pathPrefixes['projectModelsPathPrefix'] = $projectDeploymentSourcePathPrefix . XXX_MPC_Router::$directoryNames['models'] . XXX_OperatingSystem::$directorySeparator;
		$this->pathPrefixes['projectPresentersPathPrefix'] = $projectDeploymentSourcePathPrefix . XXX_MPC_Router::$directoryNames['presenters'] . XXX_OperatingSystem::$directorySeparator;
		$this->pathPrefixes['projectModulesPathPrefix'] = $projectDeploymentSourcePathPrefix . XXX_MPC_Router::$directoryNames['modules'] . XXX_OperatingSystem::$directorySeparator;
		
		$this->pathPrefixes['modulePathPrefix'] = $this->canonicalModulePathPrefix;
		$this->pathPrefixes['controllersPathPrefix'] = $this->canonicalModulePathPrefix . XXX_MPC_Router::$directoryNames['controllers'] . XXX_OperatingSystem::$directorySeparator;
		$this->pathPrefixes['modelsPathPrefix'] = $this->canonicalModulePathPrefix . XXX_MPC_Router::$directoryNames['models'] . XXX_OperatingSystem::$directorySeparator;		
		$this->pathPrefixes['presentersPathPrefix'] = $this->canonicalModulePathPrefix . XXX_MPC_Router::$directoryNames['presenters'] . XXX_OperatingSystem::$directorySeparator;
		$this->pathPrefixes['modulesPathPrefix'] = $this->canonicalModulePathPrefix . XXX_MPC_Router::$directoryNames['modules'] . XXX_OperatingSystem::$directorySeparator;
		
		
		
		
		$this->pathPrefixes['projectTranslationsURIPathPrefix'] = $this->project . '/' . 'i18n' . '/' . 'translations' . '/';
		$this->pathPrefixes['translationsURIPathPrefix'] = $this->project . '/' . XXX_MPC_Router::$directoryNames['modules'] . '/' . implode('/' . XXX_MPC_Router::$directoryNames['modules'] . '/', $this->canonicalModulePathParts) . '/'. 'i18n' . '/' . 'translations' . '/';
		
		
		$this->pathPrefixes['projectLocalizationsURIPathPrefix'] = $this->project . '/' . 'i18n' . '/' . 'localizations' . '/';
		$this->pathPrefixes['localizationsURIPathPrefix'] = $this->project . '/' . XXX_MPC_Router::$directoryNames['modules'] . '/' . implode('/' . XXX_MPC_Router::$directoryNames['modules'] . '/', $this->canonicalModulePathParts) . '/'. 'i18n' . '/' . 'localizations' . '/';
		
		$this->pathPrefixes['projectPresentersURIPathPrefix'] = $this->project . '/' . XXX_MPC_Router::$directoryNames['presenters'] . '/';
		$this->pathPrefixes['presentersURIPathPrefix'] = $this->project . '/' . XXX_MPC_Router::$directoryNames['modules'] . '/' . implode('/' . XXX_MPC_Router::$directoryNames['modules'] . '/', $this->canonicalModulePathParts) . '/' . XXX_MPC_Router::$directoryNames['presenters'] . '/';
		
		
		
				
		// Strip the arguments off of the raw route parts, leaving the raw route parts up to the action
		
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
				XXX::dispatchEventToListeners('beforeDestinationExecution');
			
				// TODO controller factory ??? No
				$controllerInstance = new $this->canonicalControllerName();
				$controllerInstance->setDestination($this);
				$controllerInstance->setPresenterContext($this->presenterContext);
				
				call_user_func(array($controllerInstance, $this->canonicalActionName), $this->arguments);
				
				$this->executed = true;
			}
		}
	}
	
	public function getProject ()
	{
		return $this->project;
	}
	
	public function getDeployIdentifier ()
	{
		return $this->deployIdentifier;
	}
	
	public function getRoute ()
	{
		return $this->canonicalRoute;
	}
	
	public function getRouteParts ()
	{
		return $this->canonicalRouteParts;
	}
	
	public function hasRawPart ($part = '')
	{
		return self::hasPart($part, 'raw');
	}
	
	public function hasRewrittenPart ($part = '')
	{
		return self::hasPart($part, 'rewritten');
	}
	
	public function hasCanonicalPart ($part = '')
	{
		return self::hasPart($part, 'canonical');
	}
	
	public function hasPart ($part = '', $type = 'canonical')
	{
		$result = false;
		
		$routeParts = array();
		
		switch ($type)
		{
			case 'raw':
				$routeParts = $this->rawRouteParts;
				break;
			case 'rewritten':
				$routeParts = $this->rewrittenRouteParts;
				break;
			case 'canonical':
				$routeParts = $this->canonicalRouteParts;
				break;
		}
		
		if (XXX_Type::isArray($part))
		{
			foreach ($part as $tempPart)
			{
				if (XXX_Array::hasValue($routeParts, $tempPart))
				{
					$result = true;
					
					break;
				}
			}
		}
		else
		{
			if (XXX_Array::hasValue($routeParts, $part))
			{
				$result = true;
			}
		}
		
		return $result;
	}
	
	public function getModulePathParts ()
	{
		return $this->canonicalModulePathParts;
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
	
	public function getSecondArgument ()
	{
		return $this->arguments[1];
	}
	
	public function getThirdArgument ()
	{
		return $this->arguments[2];
	}
	
	/*
	
	- current
		- module
		- controllers
		- models
		- presenters
		- modules
	- project
		- projectModule
		- projectControllers
		- projectModels
		- projectPresenters
		- projectModules
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