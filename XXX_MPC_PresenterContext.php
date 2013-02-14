<?php

class XXX_MPC_PresenterContext
{
	const CLASS_NAME = 'XXX_MPC_PresenterContext';
	
	protected $settings = array
	(
		'automaticallyResetSettings' => false,
		'automaticallyResetVariables' => false
	);
	
	protected $destinations = array();
		
	protected $paths = array();
	protected $variables = array();
	protected $elements = array();
	
	public function __construct ()
	{
	}
	
	// Settings
	
		public function setSetting ($key = '', $value = '')
		{
			$this->settings[$key] = $value;
		}
		
		public function getSetting ($key = '')
		{
			return $this->settings[$key];
		}
		
		public function getSettings ()
		{
			return $this->settings;
		}
		
		public function resetSettings ()
		{
			$this->settings = array
			(
				'automaticallyResetSettings' => false,
				'automaticallyResetVariables' => false
			);
		}
			
	// Destination
	
		public function setDestination ($destination = false)
		{
			if ($destination !== false)
			{
				$this->destinations[] = $destination;
			}
		}
		
		public function getDestination ($type = 'current')
		{
			$result = false;
			
			if (XXX_Array::getFirstLevelItemTotal($this->destinations) > 0)
			{
				switch ($type)
				{
					case 'current':
						$result = $this->destinations[XXX_Array::getFirstLevelItemTotal($this->destinations) - 1];						
						break;
					case 'original':
						$result = $this->destinations[0];
						break;
					case 'previous':
						if (XXX_Array::getFirstLevelItemTotal($this->destinations) > 1)
						{
							$result = $this->destinations[XXX_Array::getFirstLevelItemTotal($this->destinations) - 2];						
						}
						break;
				}
			}
			
			return $result;
		}
		
		public function getPathPrefix ($key = '', $destinationType = 'current')
		{
			$result = false;
			
			$destination = $this->getDestination($destinationType);
			
			if ($destination !== false)
			{
				$result = $destination->getPathPrefix($key);
			}
			
			return $result;
		}
		
		public function getPublicWebURIPrefix ($key = '', $destinationType = 'current')
		{
			$result = false;
			
			$destination = $this->getDestination($destinationType);
			
			if ($destination !== false)
			{
				$result = $destination->getPublicWebURIPrefix($key);
			}
			
			return $result;
		}
		
	// Paths
		
		public function setPath ($key = '', $value = '')
		{
			$this->paths[$key] = $value;
		}
		
		public function getPath ($key = '')
		{
			return $this->paths[$key];
		}
		
		public function getPaths ()
		{
			return $this->paths;
		}
				
		public function resetPaths ()
		{
			$this->paths = array();
		}
	
	// Variables
		
		public function setVariable ($key = '', $value = '')
		{
			$this->variables[$key] = $value;
		}
		
		public function prependVariable ($key = '', $value = '')
		{
			$this->variables[$key] = $value . $this->variables[$key];
		}
		
		public function appendVariable ($key = '', $value = '')
		{
			$this->variables[$key] .= $value;
		}
		
		public function getVariable ($key = '')
		{
			return $this->variables[$key];
		}
		
		public function getVariables ()
		{
			return $this->variables;
		}
		
		public function resetVariables ()
		{
			$this->variables = array();
		}
	
	public function getElement ($key = '')
	{
		return $this->elements[$key];
	}
	
	public function getRawFileContent ($presenter = '', $presentersPathPrefix = '')
	{
		$result = false;
		
		if ($presentersPathPrefix == '')
		{
			$presentersPathPrefix = 'presenters';
		}
		
		if ($presentersPathPrefix == 'presenters')
		{
			$presentersPathPrefix = $this->getPathPrefix('presenters');
		}
		else if ($presentersPathPrefix == 'globalPresenters')
		{
			$presentersPathPrefix = $this->getPathPrefix('globalPresenters');
		}
		
		$foundPresenter = false;
		
		$alternatives = array();
		
		$alternatives[] = $presenter;
		if (!XXX_String::endsWith($presenter, '.php'))
		{
			$alternatives[] = $presenter . '.php';
		}
		if (!XXX_String::endsWith($presenter, '.html'))
		{
			$alternatives[] = $presenter . '.html';
		}
		if (!XXX_String::endsWith($presenter, '.htm'))
		{
			$alternatives[] = $presenter . '.htm';
		}
		if (!XXX_String::endsWith($presenter, '.css'))
		{
			$alternatives[] = $presenter . '.css';
		}
		if (!XXX_String::endsWith($presenter, '.js'))
		{
			$alternatives[] = $presenter . '.js';
		}
		
		foreach ($alternatives as $alternative)
		{
			if (XXX_FileSystem_Local::doesFileExist(XXX_Path_Local::extendPath(XXX::$deploymentInformation['deployPathPrefix'], $presentersPathPrefix . $alternative)))
			{
				$presenter = $alternative;
				$presenterFilePath = XXX_Path_Local::extendPath(XXX::$deploymentInformation['deployPathPrefix'], $presentersPathPrefix . $alternative);
				
				$foundPresenter = true;
				
				break;
			}
		}
		
		if ($foundPresenter)
		{
			$content = XXX_FileSystem_Local::getFileContent($presenterFilePath);
			
			if ($content !== false)
			{
				$result = $content;
			}
		}
		
		return $result;
	}
	
	public function findAndLoad ($presenter = '', $arguments = array(), $returnOutput = false, $presentersPathPrefix = '')
	{		
		$result = false;
		
		if (!XXX_Type::isArray($arguments))
		{
			$argument = $arguments;
		}
		
		if ($returnOutput)
		{
			XXX_Client_Output::startBuffer(false);
		}
				
		if ($presentersPathPrefix == '')
		{
			$presentersPathPrefix = 'presenters';
		}
		
		if ($presentersPathPrefix == 'presenters')
		{
			$presentersPathPrefix = $this->getPathPrefix('presenters');
		}
		else if ($presentersPathPrefix == 'globalPresenters')
		{
			$presentersPathPrefix = $this->getPathPrefix('globalPresenters');
		}
		
		$foundPresenter = false;
		
		$presenter = XXX_String::replace($presenter, '/', XXX_OperatingSystem::$directorySeparator);
		
		$alternatives = array();
		
		$alternatives[] = $presenter;
		if (!XXX_String::endsWith($presenter, '.php'))
		{
			$alternatives[] = $presenter . '.php';
		}
		if (!XXX_String::endsWith($presenter, '.html'))
		{
			$alternatives[] = $presenter . '.html';
		}
		if (!XXX_String::endsWith($presenter, '.htm'))
		{
			$alternatives[] = $presenter . '.htm';
		}
		if (!XXX_String::endsWith($presenter, '.css'))
		{
			$alternatives[] = $presenter . '.css';
		}
		if (!XXX_String::endsWith($presenter, '.js'))
		{
			$alternatives[] = $presenter . '.js';
		}
		
		foreach ($alternatives as $alternative)
		{
			if (XXX_FileSystem_Local::doesFileExist(XXX_Path_Local::extendPath($presentersPathPrefix, $alternative)))
			{
				$presenter = $alternative;
				$presenterFilePath = XXX_Path_Local::extendPath($presentersPathPrefix, $alternative);
				
				$foundPresenter = true;
				
				break;
			}
		}
		
		if ($foundPresenter)
		{
			include $presenterFilePath;
			
			if ($returnOutput)
			{
				$result = XXX_Client_Output::getBufferContent();
			}
			else
			{
				$result = true;
			}
			
			trigger_error('Path prefix: "' . $presentersPathPrefix . '" Presenter "' . $presenter . '" loaded');
		}
		else
		{
			trigger_error('Path prefix: "' . $presentersPathPrefix . '" Presenter "' . $presenter . '" unable to load');		
		}
		
		if ($this->settings['automaticallyResetVariables'])
		{
			$this->resetVariables();
		}
		
		if ($this->settings['automaticallyResetSettings'])
		{
			$this->resetSettings();
		}
		
		return $result;
	}
}

?>