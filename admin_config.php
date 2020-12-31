<?php

// Generated e107 Plugin Admin Area 

require_once('../../class2.php');
if (!getperms('P')) 
{
	e107::redirect('admin');
	exit;
}

// e107::lan('multisite',true);
define('e_MULTISITE_CONFIG', e_SYSTEM_BASE."multisite.json");
define('e_MULTISITE_SQL', e_SYSTEM_BASE.'multisite.sql');


class multisite_adminArea extends e_admin_dispatcher
{

	protected static $multisiteData = array();


	protected $modes = array(	
	
		'main'	=> array(
			'controller' 	=> 'multisite_ui',
			'path' 			=> null,
			'ui' 			=> 'multisite_form_ui',
			'uipath' 		=> null
		),
		

	);	
	
	
	protected $adminMenu = array(
			
	//	'main/prefs' 		=> array('caption'=> LAN_PREFS, 'perm' => 'P'),

		'main/configure'	=> array('caption'=> LAN_CONFIGURE, 'perm' => 'P'),
		'main/create'	=> array('caption'=> "Create New Site", 'perm' => 'P')
	);

	protected $adminMenuAliases = array(
		'main/edit'	=> 'main/list'				
	);	
	
	protected $menuTitle = 'Multi Site';



	function init()
	{

		if(file_exists(e_MULTISITE_CONFIG) && $tmp = file_get_contents(e_MULTISITE_CONFIG))
		{
			self::$multisiteData = e107::unserialize($tmp);

			$c = 0;

			$links = array();

			foreach(self::$multisiteData as $val)
			{
				if($val['haystack'] !== 'host')
				{
					continue;
				}

				$url = str_replace($_SERVER['HTTP_HOST'],$val['match'],e_REQUEST_SELF);
				$links[$c] =  array('caption'=> $val['name'], 'perm' => 'P', 'url'=>$url, 'query'=>'');


				$c++;

			}

			if(!empty($links))
			{
				$this->adminMenu['div2'] = array( 'divider'=> true);
				$this->adminMenu['div1'] = array('header'=>'Jump To:');
			}


			foreach($links as $k=>$l)
			{
				$this->adminMenu['other/'.$k] = $l;

			}


		}




	}

	public static function getMultisiteData()
	{
		return self::$multisiteData;
	}




}




				
class multisite_ui extends e_admin_ui
{


		protected $pluginTitle		= 'Multiple Site';
		protected $pluginName		= 'multisite';
	//	protected $eventName		= 'multisite-'; // remove comment to enable event triggers in admin. 		
		protected $table			= '';
		protected $pid				= '';
		protected $perPage			= 10; 
		protected $batchDelete		= true;
		protected $batchExport     = true;
		protected $batchCopy		= true;
	//	protected $sortField		= 'somefield_order';
	//	protected $orderStep		= 10;
	//	protected $tabs				= array('Tabl 1','Tab 2'); // Use 'tab'=>0  OR 'tab'=>1 in the $fields below to enable. 
		
	//	protected $listQry      	= "SELECT * FROM `#tableName` WHERE field != '' "; // Example Custom Query. LEFT JOINS allowed. Should be without any Order or Limit.
	
		protected $listOrder		= ' DESC';
	
		protected $fields 		= array(

		//	'database'              => array('title' => LAN_LZ_THEMEPREF_03, 'type'=>'text', 'writeParms'=>array('size'=>'xxlarge'),'help'=>''),
			'newdb'     => array('title' => "New Site Database", 'type'=>'dropdown', 'data'=>'str', 'writeParms'=>array()),
		//	'cdn'   		        => array('title' => 'CDN', 'type'=>'dropdown', 'writeParms'=>array('optArray'=>array( 'cdnjs' => 'CDNJS (Cloudflare)', 'jsdelivr' => 'jsDelivr')))



		);
		
		protected $fieldpref = array();
		

	//	protected $preftabs        = array('General', 'Other' );
		protected $prefs = array(
		//	'active'		=> array('title'=> 'Active', 'tab'=>0, 'type'=>'boolean', 'data' => 'str', 'help'=>''),
		);



	
		public function init()
		{
			// Set drop-down values (if any).
			$emptyDBs = $this->getDatabases('empty');
			if(!empty($emptyDBs))
			{
				$this->fields['newdb']['writeParms']['optArray'] =  $this->getDatabases('empty');
				$this->fields['newdb']['writeParms']['default'] = 'blank';
			}
			else
			{
				$this->fields['newdb']['help'] = 'No empty databases found.';
			}





			if(!empty($_POST['etrigger_submit']) && !empty($_POST['newdb']))
			{
				if($error = $this->createSite($_POST['newdb'])) // no errors.
				{
					e107::getMessage()->addError($error);
				}
				else
				{
					e107::getMessage()->addSuccess('New Site Created!');
				}

			}


			if(!empty($_POST['multisite_submit']) && !empty($_POST['ms']))
			{

				$this->saveResults($_POST['ms']);
				$this->redirectAction('configure');

			}

			e107::getDebug()->log(defset('e_MULTISITE_IN_USE'));

			$data = file_get_contents(e_BASE."e107_config.php");

			if(strpos($data,'multisite/multisite.php') === false)
			{
				$text = '<p>Please add the following line to the end of your <b>e107_config.php</b> file. (before <b>/?></b> if it exists.)</p>';
				$text .='<pre style="padding:20px">require_once($PLUGINS_DIRECTORY."multisite/multisite.php");</pre>';
				e107::getMessage()->addInfo( $text);
			}





		}


		private function createSite($db)
		{
			if(!file_exists(e_MULTISITE_SQL))
			{
				return ("Please place a full database dump of a working e107 installation into a new file: ".e_MULTISITE_SQL);
			}

			$dump = file_get_contents(e_MULTISITE_SQL);

			if(empty($dump))
			{
				return e_MULTISITE_SQL." is empty!";
			}

			$sql = e107::getDb();

			$error = '';

			$sql->database($db);

			if(!$sql->db_Query($dump))
			{
				$error = $sql->getLastErrorText();
			}

			$defaultDB = e107::getMySQLConfig('defaultdb');
			$sql->database($defaultDB);

			return !empty($error) ? $error : false;
		}





		private function saveResults($data)
		{
			$arr= array();
			foreach($data as $k=>$val)
			{
				if(empty($val['match']) || empty($val['haystack']))
				{
					continue;
				}

				$val['active'] = empty($val['active']) ? 0 : 1;

				$val['match'] = trim(str_replace('/','',$val['match']));

				$arr[] = $val;
			}

			if(!empty($arr))
			{
				$json = e107::serialize($arr,'json');


			//	e107::getMessage()->addDebug(print_a($json,true));

				if(!file_put_contents(e_MULTISITE_CONFIG, $json))
				{
					e107::getMessage()->addError("Unable to save! ".e_SYSTEM." is not writable!");
				}
				else
				{
					e107::getMessage()->addSuccess(LAN_SETSAVED);
				}

			}


		}

		private function getDatabases($mode=null)
		{
			$sql = e107::getDb();

			$result = $sql->retrieve("SHOW DATABASES",true);

			$dbs = array();
			foreach($result as $val)
			{


				$key = $val['Database'];

				if($key === 'mysql' || $key === 'information_schema' || $key === 'performance_schema' || $key === 'phpmyadmin')
				{
					continue;
				}

				$sql->database($val['Database']);
				$tabs = $sql->retrieve("SHOW TABLES",true);

				if($mode === 'empty')
				{

					if(!empty($tabs))
					{
						continue;
					}
				}
				else
				{
					if(empty($tabs))
					{
						continue;
					}

				}


				$dbs[$key] = $val['Database'];

			}


			$defaultDB = e107::getMySQLConfig('defaultdb');
			$sql->database($defaultDB);

			return $dbs;

		}




		public function configurePage()
		{
			$frm        = $this->getUI();
			$dbs        = $this->getDatabases();
			$options    = array('host'=>'HTTP_HOST','url'=>'SUBDIR');

			$curval = multisite_adminArea::getMultisiteData();

			$text = $frm->open('multi','post');
			$text .= "<table class='table table-striped table-bordered'>
				<tr><th>Name</th><th>Search</th><th>Match</th><th colspan='2'>Database</th><th class='center'>In Use</th><th class='center'>Active</th></tr>";

			for ($i = 0; $i <= 10; $i++)
			{
				$database = !empty($curval[$i]['mysql']['database']) ? $curval[$i]['mysql']['database'] : '';
				$prefix = !empty($curval[$i]['mysql']['prefix']) ? $curval[$i]['mysql']['prefix'] : '';
				$active = !empty($curval[$i]['active']) ? $curval[$i]['active'] : 0;
				$inuse = (!empty($database) && $database === defset('e_MULTISITE_IN_USE')) ? ADMIN_TRUE_ICON : "";

			    $text .= "<tr>

					<td>".$frm->text('ms['.$i.'][name]',$curval[$i]['name'], 80,'size=block-level')."</td>
					<td style='width:200px'>".$frm->select('ms['.$i.'][haystack]',$options, $curval[$i]['haystack'], 'size=block-level&default=blank' )."</td>
					<td>".$frm->text('ms['.$i.'][match]',$curval[$i]['match'], 80,array('size'=>'block-level', 'pattern'=>''))."<div class='field-help'>eg. sub.mydomain.com or mydomain.com or regex to match sub-directory</td>
					<td style='width:100px'>".$frm->text('ms['.$i.'][mysql][prefix]',  $prefix, 25, 'size=block-level&placeholder=e107_' )."<div class='field-help'>Database Prefix. Leave blank to use 'e107_'</td>
					<td style='width:200px'>".$frm->select('ms['.$i.'][mysql][database]',$dbs, $database, 'size=block-level&default=blank' )."</td>
					<td class='center' style='width:100px'>".$inuse."</td>
					<td class='center' style='width:10%'>".$frm->radio_switch('ms['.$i.'][active]',$active, strtoupper(LAN_ON), strtoupper(LAN_OFF),array('switch'=>'small'))."</td>
					</tr>";
			}

			$text .= "</table>";
			$text .= "<div class='buttons-bar center'>".$frm->button('multisite_submit',1,'submit',LAN_SAVE)."</div>";
			$text .= $frm->close();

			return $text;



		}
		// ------- Customize Create --------
		
		public function beforeCreate($new_data,$old_data)
		{
			return $new_data;
		}
	
		public function afterCreate($new_data, $old_data, $id)
		{
			// do something
		}

		public function onCreateError($new_data, $old_data)
		{
			// do something		
		}		
		
		
		// ------- Customize Update --------
		
		public function beforeUpdate($new_data, $old_data, $id)
		{
			return $new_data;
		}

		public function afterUpdate($new_data, $old_data, $id)
		{
			// do something	
		}
		
		public function onUpdateError($new_data, $old_data, $id)
		{
			// do something		
		}		
		
			
	/*	
		// optional - a custom page.  
		public function customPage()
		{
			$text = 'Hello World!';
			$otherField  = $this->getController()->getFieldVar('other_field_name');
			return $text;
			
		}
	*/
			
}
				


class multisite_form_ui extends e_admin_form_ui
{

}		
		

		
		
new multisite_adminArea();

require_once(e_ADMIN."auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN."footer.php");
exit;

