<?php

class XXX_MPC_PresenterContext
{
	const CLASS_NAME = 'XXX_MPC_PresenterContext';
	
	public $settings = array
	(
		'automaticallyResetSettings' => false,
		'automaticallyResetVariables' => false
	);
	
	public $destinations = array();
		
	public $paths = array();
	public $variables = array();
	public $elements = array();
	
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
		
		public function getPathPrefixes ($destinationType = 'current')
		{
			$result = false;
			
			$destination = $this->getDestination($destinationType);
			
			if ($destination !== false)
			{
				$result = $destination->getPathPrefixes($key);
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
		
		public function getURIPathPrefix ($key = '', $suffix = '', $destinationType = 'current')
		{
			$result = false;
			
			$destination = $this->getDestination($destinationType);
			
			if ($destination !== false)
			{
				$result = $destination->getURIPathPrefix($key);
				$result .= $suffix;
				
				$result = XXX_Static_Publisher::prefixAndMapFile($result);
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
	
	public function find ($presenter = '', $presentersPathPrefix = '')
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
		else if ($presentersPathPrefix == 'projectPresenters')
		{
			$presentersPathPrefix = $this->getPathPrefix('projectPresenters');
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
		if (!XXX_String::endsWith($presenter, '.js'))
		{
			$alternatives[] = $presenter . '.js';
		}
		if (!XXX_String::endsWith($presenter, '.css'))
		{
			$alternatives[] = $presenter . '.css';
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
			$result = $presenterFilePath;
		}
		else
		{
			trigger_error('Path prefix: "' . $presentersPathPrefix . '" Presenter "' . $presenter . '" unable to find');		
		}
		
		return $result;
	}
	
	
	public function getRawFileContent ($presenter = '', $presentersPathPrefix = '')
	{
		$result = false;
		
		$foundPresenter = $this->find($presenter, $presentersPathPrefix);
		
		if ($foundPresenter)
		{
			$presenterFilePath = $foundPresenter;
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
		$presenterResult = false;
		
		if (!XXX_Type::isArray($arguments))
		{
			$argument = $arguments;
		}
		
		// Convert array keys to local variables
		if (XXX_Type::isArray($arguments))
		{
			extract($arguments, EXTR_SKIP);
		}
		
		if ($returnOutput)
		{
			XXX_Client_Output::startBuffer(false);
		}
		
		$foundPresenter = $this->find($presenter, $presentersPathPrefix);
		
		if ($foundPresenter)
		{
			$presenterFilePath = $foundPresenter;
		}
				
		if ($foundPresenter)
		{
			include $presenterFilePath;
			
			if ($returnOutput)
			{
				$presenterResult = XXX_Client_Output::getBufferContent();
			}
			else
			{
				$presenterResult = true;
			}
		}
		
		if ($this->settings['automaticallyResetVariables'])
		{
			$this->resetVariables();
		}
		
		if ($this->settings['automaticallyResetSettings'])
		{
			$this->resetSettings();
		}
		
		return $presenterResult;
	}
	// TODO static for images, js etc., normal for redirects etc.
	public function composeJS ()
	{
		$pathPrefixes = $this->getPathPrefixes();
		
		$filteredPathPrefixes = array();
		
		foreach ($pathPrefixes as $key => $pathPrefix)
		{
			if (XXX_String::hasPart($key, 'URI'))
			{
				$filteredPathPrefixes[$key] = $pathPrefix;
			}
		}
		
		$result = '';
		
		$result .= 'XXX_MPC_PresenterContext.pathPrefixes = ' . XXX_String_JSON::encode($filteredPathPrefixes) . ';' . XXX_OperatingSystem::$lineSeparator;
		
		return $result;
	}
}

?>