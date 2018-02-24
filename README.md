# multisite
Multisite plugin for e107 v2.1.5 or higher

##Installation

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
    
    