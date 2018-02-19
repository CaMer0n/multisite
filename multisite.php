<?php


	/**
	 * e107 website system
	 *
	 * Copyright (C) 2008-2017 e107 Inc (e107.org)
	 * Released under the terms and conditions of the
	 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
	 * Developed by CaMer0n of e107.org
	 */



class multisite
{

	private static $debug = false;

	public static function load($mySQLdefaultdb,$systemDir)
	{
		if(defined('e_DEBUG') && e_DEBUG===true && (basename($_SERVER["SCRIPT_NAME"]) !== 'thumb.php') && !defined('e_AJAX_REQUEST') && empty($_POST) && empty($_GET['e_ajax']))
		{
			self::$debug = true;
			echo "<!-- DB: ".$mySQLdefaultdb." -->\n";
			echo "<!-- Host: ".$_SERVER['HTTP_HOST']." -->\n";
			echo "<!-- URI: ".$_SERVER['REQUEST_URI']." -->\n";
		}

		$path = '';

		while (!file_exists("{$path}class2.php"))
		{
			$path .= "../";
		}

		$systemDir = $path.$systemDir;

		if(empty($systemDir) || !file_exists($systemDir."multisite.json"))
		{
			echo (self::$debug === true) ? "<!-- SystemDir: ".$systemDir." -->" : null;
			return array('database'=>$mySQLdefaultdb);
		}


		if($data = file_get_contents($systemDir."multisite.json"))
		{
			$config = json_decode($data,true);

			if($mysqlData = self::detect($config))
			{
				define('e_MULTISITE_IN_USE',$mysqlData['database']);
				return $mysqlData;
			}

			if(self::$debug === true)
			{
				echo "<!-- Multisite: No Match -->\n";

			}
		}
		else
		{
			echo (self::$debug === true) ? "<!-- Multisite: Couldn't load json: ".$systemDir."multisite.json -->\n" : null;

		}

		return array('database'=>$mySQLdefaultdb);

	}

	private static function detect($config)
	{
		foreach($config as $site)
		{
			if(empty($site['active']) || empty($site['mysql']['database']) || empty($site['match']))
			{
				if(self::$debug === true)
				{
					echo "<!-- Skipping ".$site['name']." active: ".$site['active']." -->\n";

				}
				continue;
			}

			if($site['haystack'] === 'host' && ($_SERVER['HTTP_HOST'] === $site['match']))
			{
				return $site['mysql'];
			}
			elseif($site['haystack'] === 'url')
			{
				$regex = '/^\/'.$site['match'].'\//';

				if(preg_match($regex, $_SERVER['REQUEST_URI'], $m))
				{
					define('e_HTTP',$m[0]);
					define('e_SELF_OVERRIDE',true);
					define('e_MULTISITE_MATCH', $site['match']);
				//	define('THEME','bootstrap3/');
					return $site['mysql'];
				}

				if(self::$debug === true)
				{
					echo "<!-- Match Failed -->\n";
					echo "<!-- Regex: ".$regex." -->\n";
					echo "<!-- Hackstack ".$_SERVER['REQUEST_URI']." -->";
				}
			}

		}


		return false;
	}





}

$multiMySQL = multisite::load($mySQLdefaultdb, $SYSTEM_DIRECTORY);
$mySQLdefaultdb = $multiMySQL['database'];

if(!empty($multiMySQL['prefix']))
{
	$mySQLprefix = $multiMySQL['prefix'];
}

unset($multiMySQL);
