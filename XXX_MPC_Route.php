<?php

abstract class XXX_MPC_Route
{
	public static $route = '';
		
	public static function initialize ()
	{
		global $argv;
		
		switch (XXX_PHP::$executionEnvironment)
		{
			case 'httpServer':
				self::$route = $_GET['route'];
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
							self::$route = $tempArgumentParts[1];
						}
						else if ($argv[$i + 1] != '')
						{
							self::$route = $argv[$i + 1];
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
	}
}

?>