# Multisite plugin
Compatible with e107 v2.1.5 or higher

## Installation

### Modify e107_config.php 

#### e107_config e107 v2.4.x schema:
 
    $config = ['database' => [] etc ]; // modify 'return' to '$config ='
    require_once("e107_plugins/multisite/multisite.php"); // add this line
    return multisite::load($config); // add this line 

#### e107_config e107 v1-2.x. 

    $mySQLserver    = 'localhost';
    $mySQLuser      = 'user';
    $mySQLpassword  = 'pwd';
    $mySQLdefaultdb = 'database';
    $mySQLprefix    = 'e107_';
    $mySQLcharset   = 'utf8';
    
    /* etc... */

    $ADMIN_DIRECTORY     = 'e107_admin/';
    $FILES_DIRECTORY     = 'e107_files/';
    $IMAGES_DIRECTORY    = 'e107_images/';
    $THEMES_DIRECTORY    = 'e107_themes/';
    $PLUGINS_DIRECTORY   = 'e107_plugins/';
    
    /* etc... */
    
    require_once($PLUGINS_DIRECTORY."multisite/multisite.php"); // Add this line.

## Setup Options
### Option 1. Subdirectory sites
If you plan to have multiple 'sites' as subdirectories. 
(eg. ```mydomain.com/site1/```, ```mydomain/site2/```) it is recommended 
that you install e107 in a subdirectory of your domain and NOT have e107 installated in the root directory. 

    public_html/default/ (e107 root directory)
    public_html/ (should be empty, other than the 'default' directory) ) 

Then, create an .htaccess file and place it in ```public_html```. It should contain the following:

    RewriteEngine on
    RewriteRule ^([a-z0-9_]*)\/(.*)$ default/$2 [NC]

### Option 2. Seperate Domain sites
If you plan to use separate domains for each site, then you should install e107 in the root directory. 
eg. 

    public_html/ (e107 root)
    
    