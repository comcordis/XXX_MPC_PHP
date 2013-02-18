<?php

abstract class XXX_MPC_EntryPointRoute
{
	public static $defaultEntryPointRoute = '';
	
	public static $bareEntryPointRoute = '';
	
	public static function initialize ()
	{
		self::$bareEntryPointRoute = self::getBareEntryPointRoute();
	}
	
	public static function getBareEntryPointRoute ()
	{
		global $argv;
		
		$result = false;
		
		switch (XXX_PHP::$executionEnvironment)
		{
			case 'httpServer':
				$result = $_GET['route'];
				break;
			case 'commandLine':
				$foundRoute = false;
			
				for ($i = 0, $iEnd = count($argv); $i < $iEnd; ++$i)
				{
					$tempArgument = $argv[$i];
					
					if (substr($tempArgument, 0, 2) == '--')
					{
						$tempArgument = substr($tempArgument, 2);
					}
					else if (substr($tempArgument, 0, 1) == '-')
					{
						$tempArgument = substr($tempArgument, 1);
					}
					
					$tempArgumentParts = explode('=', $tempArgument);
					
					if ($tempArgumentParts[0] == 'route')
					{
						if (count($tempArgumentParts) > 1)
						{
							$result = $tempArgumentParts[1];
						}
						else if ($argv[$i + 1] != '')
						{
							$result = $argv[$i + 1];
						}
						
						$foundRoute = true;
					}
					
					if ($foundRoute)
					{
						break;
					}
				}
				break;
		}
		
		return $result;
	}
	
	public static function getExecutionEnvironmentPrefix ()
	{
		$executionEnvironmentPrefix = '';
		
		$executionEnvironmentPrefix .= XXX_PHP::$executionEnvironment;
		$executionEnvironmentPrefix .= '/';
		
		switch (XXX_PHP::$executionEnvironment)
		{
			case 'httpServer':
				$subExecutionEnvironment = XXX_HTTPServer_Client::$parsedHost['subExecutionEnvironment'];
				
				if ($subExecutionEnvironment == '')
				{
					$subExecutionEnvironment = 'www';
				}
				break;
			case 'commandLine':
				$subExecutionEnvironment = XXX_CommandLine_Input::getArgumentVariable('subExecutionEnvironment');
				
				if ($subExecutionEnvironment == '')
				{
					$subExecutionEnvironment = 'manual';
				}
				break;
		}
		
		$executionEnvironmentPrefix .= $subExecutionEnvironment;
		$executionEnvironmentPrefix .= '/';
		
		return $executionEnvironmentPrefix;
	}
	
	public static function addExecutionEnvironmentPrefixToRoute ($route = '')
	{
		$executionEnvironmentPrefix = self::getExecutionEnvironmentPrefix();
		
		if ($executionEnvironmentPrefix)
		{
			$route = $executionEnvironmentPrefix . $route;
		}
		
		return $route;
	}
	
	public static function stripExecutionEnvironmentPrefixFromRoute ($route = '')
	{
		$executionEnvironmentPrefix = self::getExecutionEnvironmentPrefix();
		$executionEnvironmentPrefixCharacterLength = XXX_String::getCharacterLength($executionEnvironmentPrefix);
		
		if (XXX_String::beginsWith($route, $executionEnvironmentPrefix))
		{
			$route = XXX_String::getPart($route, $executionEnvironmentPrefixCharacterLength);
		}
		
		return $route;
	}
	
	public static function getEntryPointRoute ()
	{
		$route = '';
		
		if ($route == '')
		{
			$route = XXX_MPC_Router::cleanRoute(XXX_MPC_EntryPointRoute::$bareEntryPointRoute);
			
			// Can't be just the entryPoint file
			if ($route == basename($_SERVER['SCRIPT_FILENAME']))
			{
				$route = '';
			}
			
			if ($route != '')
			{
				$route = self::addExecutionEnvironmentPrefixToRoute($route);
			}
		}
		
		if ($route == '')
		{
			if (self::$defaultEntryPointRoute != '')
			{
				$route = XXX_MPC_Router::cleanRoute(self::$defaultEntryPointRoute);
			}
		}
		
		return $route;
	}
	
	public static function setDefaultEntryPointRoute ($defaultEntryPointRoute = '', $addExecutionEnvironmentPrefix = true)
	{
		$defaultEntryPointRoute = XXX_MPC_Router::cleanRoute($defaultEntryPointRoute);
		
		if ($addExecutionEnvironmentPrefix)
		{
			$defaultEntryPointRoute = self::addExecutionEnvironmentPrefixToRoute($defaultEntryPointRoute);
		}
				
		self::$defaultEntryPointRoute = $defaultEntryPointRoute;
	}
}

?>