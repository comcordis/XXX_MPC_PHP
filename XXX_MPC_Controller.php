<?php

/*

A controller can have sub routes, sharing the same presenter, or having a standalone presenter?

Presenters are not 1:1 with controllers

Presenters are either:
- Mirrored directory in 

- Should be included within the presenterContext class, so $this-> references to the presenter itself?

- Presenter can have sub presenters?

- Presenters are never classes??? Is this true, most of the time yes

- Encapsulation, in the initializer of a module call a before/after part


- inheritPresenterContext true | false

controller
	- destination
	- presenterContext

the forms situation

	main controller and form controller
		- inherit presenterContext
		- different destination
	

Within a controller:
	- Load models
	- Load sub controller
		- global
		- currentModule
		- subPath
	- Load presenter
		- global
		- currentModule
		- subPath

Initialize all modules function for translations, static publisher etc code etc.
	blocker

*/

class XXX_MPC_Controller
{
	protected $models = array();
	
	protected $destination = false;	
	
	protected $presenterContext = false;
	
	public function __construct ()
	{
	}
	
	public function setDestination ($destination = false)
	{
		$this->destination = $destination;
		
		if ($this->presenterContext !== false)
		{
			$this->presenterContext->setDestination($this->destination);
		}
	}
	
	public function getDestination ($destination)
	{
		return $this->destination;
	}
	
	public function setPresenterContext ($presenterContext = false)
	{
		if ($presenterContext)
		{
			$this->presenterContext = $presenterContext;
		}
		else
		{
			$this->presenterContext = new XXX_MPC_PresenterContext();
		}
		
		$this->presenterContext->setDestination($this->destination);
	}
	
	public function getPresenterContext ()
	{
		return $this->presenterContext;
	}
	
	public function getArguments ()
	{
		return $this->destination->getArguments();
	}
	
	public function getFirstArgument ()
	{
		return $this->destination->getFirstArgument();
	}
	
	public function getSecondArgument ()
	{
		return $this->destination->getSecondArgument();
	}
	
	public function getThirdArgument ()
	{
		return $this->destination->getThirdArgument();
	}
		
	public function index ()
	{
		// TODO reroute to error, if this is empty....
		echo 'Default index';
	}
}

?>