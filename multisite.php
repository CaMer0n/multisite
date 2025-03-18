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

    /**
     * Legacy method - retains full backward compatibility.
     *
     * @param string $mySQLdefaultdb The default database name.
     * @param string $systemDir The system directory.
     * @return array The detected or default database configuration.
     */
    public static function loadLegacy($mySQLdefaultdb, $systemDir)
    {
        if (defined('e_DEBUG') && e_DEBUG === true && (basename($_SERVER["SCRIPT_NAME"]) !== 'thumb.php') && !defined('e_AJAX_REQUEST') && empty($_POST) && empty($_GET['e_ajax']))
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

        if (empty($systemDir) || !file_exists($systemDir."multisite.json"))
        {
            echo (self::$debug) ? "<!-- SystemDir: ".$systemDir." -->" : null;
            return array('database' => $mySQLdefaultdb);
        }

        if ($data = file_get_contents($systemDir."multisite.json"))
        {
            $config = json_decode($data, true);

            if ($mysqlData = self::detectLegacy($config))
            {
                define('e_MULTISITE_IN_USE', $mysqlData['database']);
                return $mysqlData;
            }

            if (self::$debug)
            {
                echo "<!-- Multisite: No Match -->\n";
            }
        }
        else
        {
            echo (self::$debug) ? "<!-- Multisite: Couldn't load json: ".$systemDir."multisite.json -->\n" : null;
        }

        return array('database' => $mySQLdefaultdb); // Old behavior preserved.
    }

    /**
     * New method - works directly with the new structured schema.
     *
     * Accepts the new `$config` schema, modifies it as needed, and returns
     * the complete updated `$config` array.
     *
     * @param array $config The new configuration schema.
     * @return array The complete modified configuration array.
     */
    public static function load(array $config)
    {
        if (defined('e_DEBUG') && e_DEBUG === true && (basename($_SERVER["SCRIPT_NAME"]) !== 'thumb.php') && !defined('e_AJAX_REQUEST') && empty($_POST) && empty($_GET['e_ajax']))
        {
            self::$debug = true;
            echo "<!-- Debug Enabled for Multisite (New Schema) -->\n";
        }

        // Determine the system directory based on the new schema paths.
        $systemDir = $config['paths']['system'] ?? 'e107_system/';

        $path = '';
        while (!file_exists("{$path}class2.php"))
        {
            $path .= "../";
        }

        $systemDir = $path.$systemDir;

        // Check if the multisite.json file exists.
        if (empty($systemDir) || !file_exists($systemDir."multisite.json"))
        {
            echo (self::$debug) ? "<!-- SystemDir: ".$systemDir." -->" : null;
            return $config; // Return the default config unchanged.
        }

        // Load and parse the multisite.json file.
        if ($data = file_get_contents($systemDir."multisite.json"))
        {
            $multisiteConfig = json_decode($data, true);

            // Attempt to detect a matching site in the multisite.json settings.
            if ($mysqlData = self::detectNew($config, $multisiteConfig))
            {
                define('e_MULTISITE_IN_USE', $mysqlData['database']);
                // Update the `$config['database']` with matched data.
                $config['database'] = array_merge($config['database'], $mysqlData);
            }
            else if (self::$debug)
            {
                echo "<!-- Multisite: No Match Found in multisite.json (New Schema) -->\n";
            }
        }
        else
        {
            echo (self::$debug) ? "<!-- Multisite: Couldn't load json: ".$systemDir."multisite.json -->\n" : null;
        }

        // Return the complete modified configuration array.
        return $config;
    }

    /**
     * Legacy detection logic for the old load() method.
     *
     * @param array $config The multisite configuration from multisite.json.
     * @return array|false The matching database info or false if not found.
     */
    private static function detectLegacy($config)
    {
        foreach ($config as $site)
        {
            if (empty($site['active']) || empty($site['mysql']['database']) || empty($site['match']))
            {
                if (self::$debug)
                {
                    echo "<!-- Skipping ".$site['name']." active: ".$site['active']." -->\n";
                }
                continue;
            }

            if ($site['haystack'] === 'host' && ($_SERVER['HTTP_HOST'] === $site['match']))
            {
                return $site['mysql'];
            }
            elseif ($site['haystack'] === 'url')
            {
                $regex = '/^\/'.$site['match'].'\//';

                if (preg_match($regex, $_SERVER['REQUEST_URI'], $m))
                {
                    define('e_HTTP', $m[0]);
                    define('e_SELF_OVERRIDE', true);
                    define('e_MULTISITE_MATCH', $site['match']);
                    return $site['mysql'];
                }

                if (self::$debug)
                {
                    echo "<!-- Match Failed -->\n";
                    echo "<!-- Regex: ".$regex." -->\n";
                    echo "<!-- URI: ".$_SERVER['REQUEST_URI']." -->\n";
                }
            }
        }

        return false;
    }

    /**
     * New detection logic for the loadNew() method.
     *
     * @param array $config The main new structured config schema.
     * @param array $multisiteConfig The config entries in multisite.json.
     * @return array|false The matching database info or false if not found.
     */
    private static function detectNew(array $config, array $multisiteConfig)
    {
        foreach ($multisiteConfig as $site)
        {
            if (empty($site['active']) || empty($site['mysql']['database']) || empty($site['match']))
            {
                if (self::$debug)
                {
                    echo "<!-- Multisite Skipped: Inactive or Invalid Configuration (New Schema) -->\n";
                }
                continue;
            }

            // Match by HTTP Host.
            if ($site['haystack'] === 'host' && ($_SERVER['HTTP_HOST'] === $site['match']))
            {
                return $site['mysql']; // Return the matching database configuration.
            }

            // Match by URL Pattern.
            if ($site['haystack'] === 'url')
            {
                $regex = '/^\/'.$site['match'].'\//';

                if (preg_match($regex, $_SERVER['REQUEST_URI'], $m))
                {
                    define('e_HTTP', $m[0]);
                    define('e_SELF_OVERRIDE', true);
                    define('e_MULTISITE_MATCH', $site['match']);
                    return $site['mysql']; // Return the matching database configuration.
                }

                if (self::$debug)
                {
                    echo "<!-- Match Failed Using New Schema -->\n";
                    echo "<!-- Regex: ".$regex." -->\n";
                    echo "<!-- URI: ".$_SERVER['REQUEST_URI']." -->\n";
                }
            }
        }

        return false;
    }
}

// Post-processing logic for legacy version.
if(!empty($mySQLdefaultdb) && !empty($SYSTEM_DIRECTORY))
{
	$multiMySQL = multisite::loadLegacy($mySQLdefaultdb, $SYSTEM_DIRECTORY);
	$mySQLdefaultdb = $multiMySQL['database'];

	if (!empty($multiMySQL['prefix']))
	{
	    $mySQLprefix = $multiMySQL['prefix'];
	}

	unset($multiMySQL);
}

// Modern approach requires `load()` and returns the updated `$config` array directly.
