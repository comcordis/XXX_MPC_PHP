<?php

abstract class XXX_MPC_Model_Factory
{
	public static $modelInstances = array();
	
	public static function findAndCreateInstance ($modelsPathPrefix = '', $model = 'XXX_MPC_Model', $useCachedVersion = true)
	{
		$result = false;
		
		if ($useCachedVersion)
		{		
			if (XXX_Array::getFirstLevelItemTotal(self::$modelInstances))
			{
				foreach (self::$modelInstances as $name => $instance)
				{
					if ($name == $model)
					{
						$result = $instance;
						
						break;
					}
				}
			}
		}
		
		if ($result !== false)
		{
			$modelExists = self::tryModelExistence($modelsPathPrefix, $model);
			
			if ($modelExists)
			{
				$instance = new $model();
				
				self::$models[$model] = $instance;
				
				$result = $instance;
			}
			else
			{
				trigger_error('Model "' . $model . '" doesn\'t exist.');
			}
		}
		
		return $result;
	}
	
	public static function findAndLoad ($modelsPathPrefix = '', $model = 'XXX_MPC_Model')
	{
		return self::tryModelExistence($modelsPathPrefix, $model);
	}
	
	public static function tryModelExistence ($modelsPathPrefix = '', $model = 'XXX_MPC_Model')
	{
		$result = false;
		
		if (class_exists($model))
		{
			$result = true;
		}
		else
		{
			if ($modelsPathPrefix == '')
			{
				$modelsPathPrefix = XXX_MPC_Router::$directoryNames['models'] . XXX_OperatingSystem::$directorySeparator;
			}
			
			$modelFile = XXX_Path_Local::extendPath(XXX::$deploymentInformation['deployPathPrefix'], $modelsPathPrefix . $model . '.php');
			
			if (XXX_FileSystem_Local::doesFileExist($modelFile))
			{
				include_once($modelFile);
				
				if (class_exists($model))
				{				
					$result = true;
				}
			}
		}
		
		return $result;
	}
}

?>