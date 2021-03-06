<?php
class EntriesController extends AppController {
	public $name = 'Entries';
    public $components = array(
		'Auth',
		'Email',
		'Image',
		'RequestHandler',
		'Session',
		'Validation'
	);
	public $helpers = array(
		'Ajax',
		'Csv',
		'Form',
		'Get',
		'Html',
		'Js',
		'Time'
	);

	private $countListPerPage = 15;
	private $frontEndFolder = '/front_ends/';
	private $backEndFolder = '/back_ends/';
	private $onlyActiveEntries = FALSE; // if it's in admin panel, show active/disabled, and if it's on the front, show only active pages !!

	function beforeFilter()
	{
		parent::beforeFilter();
		$this->Auth->allow(
			'index',
			'get_list_entry',
			'get_detail_entry',
			'save',
			'ajax_send_organization',
			'ajax_load_more'
		);
	}

	/**
	 * fork our target routes for entry view (pages, entry, list of entries) in Front End web
	 * @return void
	 * @public
	 **/
	function index() // front End view !!
	{
		// dpr($this->params);
		// exit;
		
		if($this->RequestHandler->isAjax())
		{
			$this->layout = 'ajax';
		}
		else // our frontEnd layout !!
		{
			$this->layout = 'front_end_default';
		}
		
		// this is for redirecting home !!
		if(empty($this->params['pass']))
		{
			$this->params['pass'][0] = 'home';
/*			if($this->Auth->isAuthorized())
			{
				$this->params['pass'][0] = 'home';
			}
			else
			{
				$this->redirect('/login');
			}*/
		}
		else if($this->params['pass'][0] == 'home')
		{
			$this->redirect('/');
		}

		// redirect some must logged in pages and must not logged in pages
		$logged_in_page = array(
		);

		$not_logged_in_page = array(
			"login",
			"register",
			"forget"
		);

		if (!$this->Auth->isAuthorized() and in_array($this->params["pass"][0], $logged_in_page))
			$this->redirect('/login');
		else if ($this->Auth->isAuthorized() and in_array($this->params["pass"][0], $not_logged_in_page))
			$this->redirect('/');

		// Tree of division beginsss !!
		$myRenderFile = '';
		$myChildListMarkFile = 'list';
		$myDetailEntryMarkFile = 'detail';
		$this->onlyActiveEntries = TRUE;

		$temp_lang = $this->get_lang_url();
		$language = $temp_lang['language'];
		$indent = $temp_lang['indent'];
		$this->set('language',strtolower($language));
		
		if(empty($this->params['pass'][$indent+1]))
		{
			// if this want to list all entries...
			if(substr($this->params['url']['url'], strlen($this->params['url']['url'])-1) == '/' && $this->params['pass'][$indent+0] != 'home')
			{
				$myTypeSlug = $this->params['pass'][$indent+0];
				$myType = $this->Type->findBySlug($myTypeSlug);
				// if this want to list all entries with certain EntryMeta key and value !!
				if(!empty($this->params['url']['key']) && !empty($this->params['url']['value']))
				{
					$myMetaKey = $this->params['url']['key'];
					$myMetaValue = $this->params['url']['value'];
					$this->_admin_default($myType, 0 , NULL , $myMetaKey , $myMetaValue ,NULL,NULL, $language);
				}
				else
				{
					$this->_admin_default($myType, 0 , NULL, NULL, NULL, NULL, NULL, $language);
				}
				$myRenderFile = $myTypeSlug;
			}
			else // if this want to view pages...
			{
				$myEntrySlug = $this->params['pass'][$indent+0];
				$myEntry = $this->Entry->findBySlug($myEntrySlug);
				$this->_admin_default_edit(NULL , $myEntry);
				$myRenderFile = $myEntrySlug;
			}
		}
		else if(empty($this->params['pass'][$indent+2]))
		{
			// if this want to view all child list from certain parent Entry...
			if(substr($this->params['url']['url'], strlen($this->params['url']['url'])-1) == '/')
			{
				$myTypeSlug = $this->params['pass'][$indent+0];
				$myType = $this->Type->findBySlug($myTypeSlug);

				$myEntrySlug = $this->params['pass'][$indent+1];
				$myEntry = $this->Entry->findBySlug($myEntrySlug);

				$this->_admin_default($myType, 0 , $myEntry , NULL, NULL, NULL, NULL, $language);
				$myRenderFile = $myEntrySlug.'_'.$myChildListMarkFile;
			}
			else
			{
				$myTypeSlug = $this->params['pass'][$indent+0];
				$myType = $this->Type->findBySlug($myTypeSlug);
				// if this want to list all entries with paging limitation
				if(is_numeric($this->params['pass'][$indent+1]))
				{
					$myPaging = $this->params['pass'][$indent+1];
					$this->_admin_default($myType, $myPaging, NULL, NULL, NULL, NULL, NULL, $language);
					$myRenderFile = $myTypeSlug;
				}
				else // if this want to view details of the entry...
				{
					$myEntrySlug = $this->params['pass'][$indent+1];
					$myEntry = $this->Entry->findBySlug($myEntrySlug);
					$this->_admin_default_edit($myType , $myEntry);
					$myRenderFile = $myEntry['Entry']['entry_type'].'_'.$myDetailEntryMarkFile;
				}
			}
		}
		else // MAX LEVEL...
		{
			$myTypeSlug = $this->params['pass'][$indent+0];
			$myType = $this->Type->findBySlug($myTypeSlug);

			$myParentEntrySlug = $this->params['pass'][$indent+1];
			$myParentEntry = $this->Entry->findBySlug($myParentEntrySlug);
			// if this want to list all CHILD entries with paging limitation
			if(is_numeric($this->params['pass'][$indent+2]))
			{
				$myPaging = $this->params['pass'][$indent+2];
				$this->_admin_default($myType, $myPaging , $myParentEntry , NULL, NULL, NULL, NULL, $language);
				$myRenderFile = $myParentEntrySlug.'_'.$myChildListMarkFile;
			}
			else // if this want to view details of the child entry...
			{
				$myEntrySlug = $this->params['pass'][$indent+2];
				$myEntry = $this->Entry->findBySlug($myEntrySlug);
				$this->_admin_default_edit($myType , $myEntry , $myParentEntry);
				$myRenderFile = $myEntry['Entry']['entry_type'].'_'.$myDetailEntryMarkFile;
			}
		}
		$this->onlyActiveEntries = FALSE;
		$this->render($this->frontEndFolder.$myRenderFile);
	}

	function get_lang_url()
	{
		// -------------- LANGUAGE URL POSITION ----------------------------- //
		$lang_pos = 1;
		// ----------- END OF LANGUAGE URL POSITION ------------------------- //
		$domain_lang = strtolower(substr($_SERVER['SERVER_NAME'], 0,2));
		$mySetting = $this->Setting->get_settings();
		foreach ($mySetting['sites']['language'] as $key => $value)
		{
			if($domain_lang == strtolower(substr($value, 0,2)))
			{
				$result['language'] = $domain_lang;
				$result['indent'] = 0;
				return $result;
			}
		}
		// NOW FOR SECOND CHECK !!
		$url_set = explode('/', strtolower($_SERVER['REQUEST_URI']));
		foreach ($mySetting['sites']['language'] as $key => $value)
		{
			if($url_set[$lang_pos] == strtolower(substr($value, 0,2)))
			{
				$result['language'] = $url_set[$lang_pos];
				$result['indent'] = $lang_pos;
				return $result;
			}
		}
		$result['language'] = strtolower(substr($mySetting['sites']['language'][0], 0,2));
		$result['indent'] = 0;
		return $result;
	}

	/**
	 * change entry status (active or disabled)
	 * @param integer $id contains id of the entry
	 * @return void
	 * @public
	 **/
	function change_status($id)
	{
		$this->autoRender = false;
		$data = $this->Entry->findById($id);
		$data_change = $data['Entry']['status']==0?1:0;
		$this->Entry->id = $id;
		$this->Entry->saveField('status', $data_change);

		if ($this->RequestHandler->isAjax())
		{
			echo $data_change;
		}
		else
		{
			header("Location: ".$_SESSION['now']);
			return;
		}
	}

	/**
	 * delete entry
	 * @param integer $id contains id of the entry
	 * @return void
	 * @public
	 **/
	function delete($id = null)
	{
		$this->autoRender = FALSE;
		if (!$id) {
			$this->Session->setFlash('Invalid id for entry', 'failed');
			header("Location: ".$_SESSION['now']);
			return;
		}

		$this->Entry->id = $id;
		$this->Entry->saveField('status' , -1);
		$title = $this->Entry->findById($id);

		// minus the count of parent Entry...
		if($title['Entry']['parent_id'] > 0)
		{
			$myParent = $this->Entry->findById($title['Entry']['parent_id']);
			$this->Entry->id = $myParent['Entry']['id'];
			$this->Entry->saveField('count' , $myParent['Entry']['count'] - 1);

			// minus the count of this ChildType in parent EntryMeta !!
			$minusChildType = $this->EntryMeta->find('first' , array(
				'conditions' => array(
					'EntryMeta.entry_id' => $title['Entry']['parent_id'],
					'EntryMeta.key' => 'count-'.$title['Entry']['entry_type']
				)
			));
			if(!empty($minusChildType))
			{
				$this->EntryMeta->id = $minusChildType['EntryMeta']['id'];
				$this->EntryMeta->saveField('value' , $minusChildType['EntryMeta']['value'] - 1);
			}
		}
		$this->Session->setFlash($title['Entry']['title'].' has been deleted', 'success');
		header("Location: ".$_SESSION['now']);
	}

	/**
	 * delete image from media library
	 * @param integer $id contains id of the image entry
	 * @return void
	 * @public
	 **/
	function deleteMedia($id = null)
	{
		$this->autoRender = FALSE;
		if ($id==NULL)
		{
			$this->Session->setFlash('Invalid ID Media','failed');
		}
		else
		{
			//////////// FIND MEDIA NAME BEFORE DELETED ////////////
			$media_name = $this->Entry->findById($id);
			if($this->Entry->deleteMedia($id))
			{
				$this->Session->setFlash('Media "'.$media_name['Entry']['title'].'" has been deleted','success');
			}
		}
		header("Location: ".$_SESSION['now']);
	}

	/**
	 * target route for querying to get list of entries.
	 * @return void
	 * @public
	 **/
	function admin_index()
	{
		// DEFINE THE ORDER...
		if(!empty($this->params['form']['order_by']))
		{
			switch ($this->params['form']['order_by'])
			{
				case 'latest_first':
					$_SESSION['order_by'] = 'modified DESC';
					break;
				case 'oldest_first':
					$_SESSION['order_by'] = 'modified ASC';
					break;
				default:
					$_SESSION['order_by'] = 'sort_order DESC';
					break;
			}
		}

		// END OF DEFINE THE ORDER...
		if($this->params['type'] == 'pages')
		{
			// manually set pages data !!
			$myType['Type']['name'] = 'Pages';
			$myType['Type']['slug'] = 'pages';
			$myType['Type']['parent_id'] = -1;
		}
		else
		{
			$myType = $this->Type->findBySlug($this->params['type']);
		}
		$myPage = (empty($this->params['page'])?1:$this->params['page']);

		// if this action is going to view the CHILD list...
		if(!empty($this->params['entry']))
		{
			$myEntry = $this->Entry->findBySlug($this->params['entry']);
			if(!empty($this->params['url']['type']))
			{
				$myChildTypeSlug = $this->params['url']['type'];
			}
			else
			{
				$myChildTypeSlug = $myType['Type']['slug'];
			}
		}

		// this general action is one for all...
		$this->_admin_default($myType , $myPage , $myEntry , NULL , NULL , $myChildTypeSlug , $this->params['form']['search_by'] , strtolower($this->params['url']['lang']));
		$myTypeSlug = (empty($myChildTypeSlug)?$myType['Type']['slug']:$myChildTypeSlug);

		// send to each appropriate view
		$str = substr(WWW_ROOT, 0 , strlen(WWW_ROOT)-1); // buang DS trakhir...
		$str = substr($str, 0 , strripos($str, DS)+1); // buang webroot...
		$src = $str.'views'.str_replace('/', DS, $this->backEndFolder).$myTypeSlug.'.ctp';

		if(file_exists($src))
		{
			$this->render($this->backEndFolder.$myTypeSlug);
		}
		else
		{
			// $this->render('admin_default');
			
			if($myType['Type']['slug'] == 'user-guides')
			{
				$userRole = $this->User->findById($this->Auth->user('user_id'));
				
				if($userRole['User']['role_id'] > 2)
				{
					//find user guide
					$userGuides = $this->Entry->find('all',array(
						'conditions' => array(
							'Entry.entry_type' => 'user-guides',
							'Entry.status' => 1
						),
						'recursive' => -1
					));
					$this->set('userGuides',$userGuides);
					$this->render('admin_default_guide');
				} else
				{
					$this->render('admin_default');
				}
			} else
			{
				$this->render('admin_default');
			}
		}
	}

	/**
	* target route for adding new entry
	* @return void
	* @public
	**/
	function admin_index_add()
	{
		if($this->params['type'] == 'pages')
		{
			// manually set pages data !!
			$myType['Type']['name'] = 'Pages';
			$myType['Type']['slug'] = 'pages';
			$myType['Type']['parent_id'] = -1;
		}
		else
		{
			$myType = $this->Type->findBySlug($this->params['type']);
		}

		// if this action is going to add CHILD list...
		if(!empty($this->params['entry']))
		{
			$myEntry = $this->Entry->findBySlug($this->params['entry']);
			if(!empty($this->params['url']['type']))
			{
				$myChildTypeSlug = $this->params['url']['type'];
			}
			else
			{
				$myChildTypeSlug = $myType['Type']['slug'];
			}
		}

		// custom function for add ...
		if($myType['Type']['slug'] == 'gallery' && empty($myChildTypeSlug) || $myChildTypeSlug == 'gallery')
		{
			$this->_admin_gallery_add($myType , $myEntry , $myChildTypeSlug);
		}
		else // this general action is one for all...
		{
			$this->_admin_default_add(($myType['Type']['slug']=='pages'?NULL:$myType) , $myEntry , $myChildTypeSlug);
		}

		$myTemplate = ($myType['Type']['slug']=='pages'?$myEntry['Entry']['slug']:(empty($myChildTypeSlug)?$myType['Type']['slug']:$myChildTypeSlug).'_add');
		// send to each appropriate view
		$str = substr(WWW_ROOT, 0 , strlen(WWW_ROOT)-1); // buang DS trakhir...
		$str = substr($str, 0 , strripos($str, DS)+1); // buang webroot...
		$src = $str.'views'.str_replace('/', DS, $this->backEndFolder).$myTemplate.'.ctp';

		// add / edit must use the same view .ctp, but with different action !!
		if(file_exists($src))
		{
			$this->render($this->backEndFolder.$myTemplate);
		}
		else
		{
			$this->render('admin_default_add');
		}
	}

	/**
	* target route for editing certain entry based on passed url parameter
	* @return void
	* @public
	**/
	function admin_index_edit()
	{
		if($this->params['type'] == 'pages')
		{
			// manually set pages data !!
			$myType['Type']['name'] = 'Pages';
			$myType['Type']['slug'] = 'pages';
			$myType['Type']['parent_id'] = -1;
		}
		else
		{
			$myType = $this->Type->findBySlug($this->params['type']);
		}
		$this->Entry->recursive = 2;
		$myEntry = $this->Entry->findBySlug($this->params['entry']);

		// if this action is going to edit CHILD list...
		if(!empty($this->params['entry_parent']))
		{
			$this->Entry->recursive = 1;
			$myParentEntry = $this->Entry->findBySlug($this->params['entry_parent']);
			if(!empty($this->params['url']['type']))
			{
				$myChildTypeSlug = $this->params['url']['type'];
			}
			else
			{
				$myChildTypeSlug = $myType['Type']['slug'];
			}
		}

		// custom function for edit ...
		if($myType['Type']['slug'] == 'gallery' && empty($myChildTypeSlug) || $myChildTypeSlug == 'gallery')
		{
			$this->_admin_gallery_edit($myType , $myEntry , $myParentEntry , $myChildTypeSlug , strtolower($this->params['url']['lang']));
		}
		else // this general action is one for all...
		{
			$this->_admin_default_edit(($myType['Type']['slug']=='pages'?NULL:$myType) , $myEntry , $myParentEntry , $myChildTypeSlug , strtolower($this->params['url']['lang']));
		}

		$myTemplate = ($myType['Type']['slug']=='pages'?$myEntry['Entry']['slug']:(empty($myChildTypeSlug)?$myType['Type']['slug']:$myChildTypeSlug).'_add');
		// send to each appropriate view
		$str = substr(WWW_ROOT, 0 , strlen(WWW_ROOT)-1); // buang DS trakhir...
		$str = substr($str, 0 , strripos($str, DS)+1); // buang webroot...
		$src = $str.'views'.str_replace('/', DS, $this->backEndFolder).$myTemplate.'.ctp';

		// add / edit must use the same view .ctp, but with different action !!
		if(file_exists($src))
		{
			$this->render($this->backEndFolder.$myTemplate);
		}
		else
		{
			$this->render('admin_default_add');
		}
	}

	/**
	* get a bunch of entries based on parameter given
	* @param string $myTypeSlug contains slug database type
	* @param string $myEntrySlug[optional] contains slug of the parent Entry (used if want to search certain child Entry)
	* @param string $myChildTypeSlug[optional] contains slug of child type database (used if want to search certain child Entry)
	* @return void echoing json result
	* @public
	**/
	function get_list_entry($myTypeSlug , $myEntrySlug = NULL , $myChildTypeSlug = NULL)
	{
		$this->autoRender = FALSE;
		if($myTypeSlug == 'pages')
		{
			// manually set pages data !!
			$myType['Type']['name'] = 'Pages';

			$myType['Type']['slug'] = 'pages';
			$myType['Type']['parent_id'] = -1;
		}
		else
		{
			$myType = $this->Type->findBySlug($myTypeSlug);
		}
		$myEntry = (empty($myEntrySlug)?NULL:$this->Entry->findBySlug($myEntrySlug));

		$this->onlyActiveEntries = TRUE;
		$json = $this->_admin_default($myType , 0 , $myEntry , NULL , NULL , $myChildTypeSlug);
		$this->onlyActiveEntries = FALSE;

		echo json_encode($json);
	}

	/**
	* get specific entry from entry lists based on entry id
	* @param integer $myEntryId contains id of the entry
	* @return void echoing json result
	* @public
	**/
	function get_detail_entry($myEntryId)
	{
		$this->autoRender = FALSE;
//		$this->Entry->recursive = 2;
		$myEntry = $this->Entry->findById($myEntryId);

		// if this is a child Entry...
		if($myEntry['Entry']['parent_id'] > 0)
		{
			$myParentEntry = $this->Entry->findById($myEntry['Entry']['parent_id']);
			$myType = $this->Type->findBySlug($myParentEntry['Entry']['entry_type']); // PARENT TYPE...

			$myChildTypeSlug = $myEntry['Entry']['entry_type'];
		}
		else // if this is a parent Entry ...
		{
			$myType = $this->Type->findBySlug($myEntry['Entry']['entry_type']);
		}

		$json = $this->_admin_default_edit($myType , $myEntry , $myParentEntry , $myChildTypeSlug);
		echo json_encode($json);
	}

	/**
	* querying to get a bunch of entries based on parameter given (core function)
	* @param array $myType contains record query result of database type
	* @param integer $paging[optional] contains selected page of lists you want to retrieve
	* @param array $myEntry[optional] contains record query result of the parent Entry (used if want to search certain child Entry)
	* @param string $myMetaKey[optional] contains specific key that entries must have
	* @param string $myMetaValue[optional] contains specific value from certain key that entries must have
	* @param string $myChildTypeSlug[optional] contains slug of child type database (used if want to search certain child Entry)
	* @param string $searchMe[optional] contains search string that existed in bunch of entries requested
	* @param string $lang[optional] contains language of the entries that want to be retrieved
	* @return array $data certain bunch of entries you'd requested
	* @public
	**/
	function _admin_default($myType = array(),$paging = 1 , $myEntry = array() , $myMetaKey = NULL , $myMetaValue = NULL , $myChildTypeSlug = NULL , $searchMe = NULL , $lang = NULL)
	{
		$data['mySetting'] = $this->Setting->get_settings();
		if ($this->RequestHandler->isAjax())
		{
			$this->layout = 'ajax';
			$data['isAjax'] = 1;
			if($searchMe != NULL || !empty($lang))
			{
				$data['search'] = "yes";
			}
			if($searchMe != NULL)
			{
				$searchMe = trim($searchMe);
				if(empty($searchMe))
				{
					unset($_SESSION['searchMe']);
				}
				else
				{
					$_SESSION['searchMe'] = $searchMe;
				}
			}
			$_SESSION['lang'] = strtolower(empty($lang)?(empty($_SESSION['lang'])?substr($data['mySetting']['sites']['language'][0], 0,2):$_SESSION['lang']):$lang);
		}
		else
		{
			$data['isAjax'] = 0;
			unset($_SESSION['searchMe']);
			$_SESSION['lang'] = strtolower(empty($lang)?substr($data['mySetting']['sites']['language'][0], 0,2):$lang);
		}

		$data['myType'] = $myType;
		$data['paging'] = $paging;
		if(!empty($myEntry))
		{
			$data['myEntry'] = $myEntry;
			$myChildType = $this->Type->findBySlug($myChildTypeSlug);
			$data['myChildType'] = $myChildType;
		}

		// set page title
		$this->setTitle(empty($myEntry)?$myType['Type']['name']:$myEntry['Entry']['title']);
		// set paging session...
		$countPage = $this->countListPerPage;

		// our list conditions... ----------------------------------------------------------------------------------////
		if(empty($myEntry))
		{
			$options['conditions'] = array(
				'Entry.parent_id' => 0,
				'Entry.status' => ($this->onlyActiveEntries?1:array(0,1)),
				'Entry.entry_type' => $myType['Type']['slug']
			);
		}
		else
		{
			$options['conditions'] = array(
				'Entry.parent_id' => $myEntry['Entry']['id'],
				'Entry.status' => ($this->onlyActiveEntries?1:array(0,1)),
				'Entry.entry_type' => $myChildTypeSlug
			);
		}
		if(!empty($_SESSION['searchMe']))
		{
			$options['conditions']['Entry.title LIKE'] = '%'.$_SESSION['searchMe'].'%';
		}
		if($myType['Type']['slug'] != 'media')
		{
			$options['conditions']['Entry.lang_code LIKE'] = $_SESSION['lang'].'-%';
			$data['language'] = $_SESSION['lang'];
		}
		// find last modified... ----------------------------------------------------------------------------------////
		$options['order'] = array('Entry.modified DESC');
		$lastModified = $this->Entry->find('first' , $options);
		$data['lastModified'] = $lastModified;
		// end of last modified...

		$resultTotalList = $this->Entry->find('count' , $options);
		$data['totalList'] = $resultTotalList;

		// check for description or image is used for this entry or not ?? ////////////////////////////////////////
		$tempOpt = $options;
		$tempOpt['conditions']['LENGTH(Entry.description) >'] = 0;
		$checkSQL = $this->Entry->find('first' , $tempOpt);
		$data['descriptionUsed'] = (empty($checkSQL)?0:1);

		$tempOpt = $options;
		$tempOpt['conditions']['Entry.main_image >'] = 0;
		$checkSQL = $this->Entry->find('first' , $tempOpt);
		$data['imageUsed'] = (empty($checkSQL)?0:1);
		// end of check... ////////////////////////////////////////////////////////////////

		$options['order'] = array('Entry.'.(empty($_SESSION['order_by'])?'sort_order DESC':$_SESSION['order_by']));
		if($paging > 0)
		{
			$options['offset'] = ($paging-1) * $countPage;
			$options['limit'] = $countPage+1;
		}
		$mysql = $this->Entry->find('all' ,$options);
		$data['myList'] = $mysql;

		// set New countPage
		$newCountPage = ceil($resultTotalList * 1.0 / $countPage);
		$data['countPage'] = $newCountPage;

		// set the paging limitation...
		$left_limit = 1;
		$right_limit = 5;
		if($newCountPage <= 5)
		{
			$right_limit = $newCountPage;
		}
		else
		{
			$left_limit = $paging-2;
			$right_limit = $paging+2;
			if($left_limit < 1)
			{
				$left_limit = 1;
				$right_limit = 5;
			}
			else if($right_limit > $newCountPage)
			{
				$right_limit = $newCountPage;
				$left_limit = $newCountPage - 4;
			}
		}
		$data['left_limit'] = $left_limit;
		$data['right_limit'] = $right_limit;

		// for image input type reason...
		$data['myImageTypeList'] = $this->EntryMeta->embedded_img_meta('type');

		// IS ALLOWING ORDER CHANGE OR NOT ??
		$data['isOrderChange'] = (substr($_SESSION['order_by'], 0 , 8) == 'modified'?0:1);

		// --------------------------------------------- LANGUAGE OPTION LINK ------------------------------------------ //
		if(!empty($myEntry))
		{
			$temp100 = $this->Entry->find('all' , array(
				'conditions' => array(
					'Entry.status' => array(0,1),
					'Entry.lang_code LIKE' => '%-'.substr($myEntry['Entry']['lang_code'], 3)
				)
			));
			foreach ($temp100 as $key => $value)
			{
				$parent_language[ substr($value['Entry']['lang_code'], 0,2) ] = $value['Entry']['slug'];
			}
			$data['parent_language'] = $parent_language;
		}
		// ------------------------------------------ END OF LANGUAGE OPTION LINK -------------------------------------- //

		// FINAL TOUCH !!
		if(!empty($myMetaKey) && !empty($myMetaValue))
		{
			$data = $this->_admin_meta_options($data , $myMetaKey , $myMetaValue);
		}
		$this->set('data' , $data);
		return $data;
	}

	function _admin_meta_options($data , $myMetaKey , $myMetaValue)
	{
		$lastModified = 0;
		$data['totalList'] = 0;
		foreach ($data['myList'] as $key => $value)
		{
			$state = 0;
			foreach ($value['EntryMeta'] as $key10 => $value10)
			{
				if(substr($value10['key'], 5) == $myMetaKey && $this->get_slug($value10['value']) == $myMetaValue)
				{
					$state = 1;
					break;
				}
			}
			if($state == 0)
			{
				unset($data['myList'][$key]);
			}
			else // if it is a valid list, ...
			{
				$data['totalList']++;
				// get our last Modified !!
				if($value['Entry']['modified'] > $lastModified)
				{
					$data['lastModified'] = $value;
					$lastModified = $value['Entry']['modified'];
				}
			}
		}
		$data['countPage'] = ceil($data['totalList'] * 1.0 / $this->countListPerPage);
		return $data;
	}

	/**
	* add new gallery in gallery database
	* @param array $myType contains record query result of database type
	* @param array $myEntry[optional] contains record query result of the selected Entry
	* @param string $myChildTypeSlug[optional] contains slug of child type database (used if want to search certain child Entry)
	* @return void
	* @public
	**/
	function _admin_gallery_add($myType = array() , $myEntry = array() , $myChildTypeSlug = NULL , $lang_code = NULL , $prefield_slug = NULL)
	{
		$data['mySetting'] = $this->Setting->get_settings();
		$this->setTitle(empty($myEntry)?$myType['Type']['name']:$myEntry['Entry']['title'].' - Add New');
		$data['myType'] = $myType;
		if(!empty($myEntry))
		{
			$data['myParentEntry'] = $myEntry;
			$myChildType = $this->Type->findBySlug($myChildTypeSlug);
			$data['myChildType'] = $myChildType;
		}
		// --------------------------------------------- LANGUAGE OPTION LINK ------------------------------------------ //
		if(!empty($myEntry))
		{
			$temp100 = $this->Entry->find('all' , array(
				'conditions' => array(
					'Entry.status' => array(0,1),
					'Entry.lang_code LIKE' => '%-'.substr($myEntry['Entry']['lang_code'], 3)
				)
			));
			foreach ($temp100 as $key => $value)
			{
				$parent_language[ substr($value['Entry']['lang_code'], 0,2) ] = $value['Entry']['slug'];
			}
			$data['parent_language'] = $parent_language;
		}
		$data['lang'] = strtolower(empty($myEntry)?(empty($_SESSION['lang'])? substr($data['mySetting']['sites']['language'][0], 0,2):$_SESSION['lang']):substr($myEntry['Entry']['lang_code'], 0,2));
		// ------------------------------------------ END OF LANGUAGE OPTION LINK -------------------------------------- //
		// FINAL TOUCH !!
		$this->set('data' , $data);

		// if form submit is taken...
		if (!empty($this->data))
		{
			if(empty($lang_code) && !empty($myEntry) && substr($myEntry['Entry']['lang_code'], 0,2) != $this->data['language'])
			{
				$myEntry = $this->Entry->findByLangCode($this->data['language'].substr($myEntry['Entry']['lang_code'], 2));
			}
			// set the type of this entry...
			$this->data['Entry']['entry_type'] = (empty($myEntry)?$myType['Type']['slug']:$myChildType['Type']['slug']);
			// generate slug from title...
			$this->data['Entry']['slug'] = $this->get_slug($this->data['Entry']['title']);
			// write my creator...
			$myCreator = $this->Auth->user();
			$this->data['Entry']['created_by'] = $myCreator['Account']['user_id'];
			$this->data['Entry']['modified_by'] = $myCreator['Account']['user_id'];
			// set parent_id
			$this->data['Entry']['parent_id'] = (empty($myEntry)?0:$myEntry['Entry']['id']);
			$this->data['Entry']['lang_code'] = strtolower(empty($lang_code)?$this->data['language']:$lang_code);

			// PREPARE FOR ADDITIONAL LINK OPTIONS !!
			$myChildTypeLink = (!empty($myEntry)&&$myType['Type']['slug']!=$myChildType['Type']['slug']?'?type='.$myChildType['Type']['slug']:'');
			$myTranslation = (empty($myChildTypeLink)?'?':'&').'lang='.substr($this->data['Entry']['lang_code'], 0,2);

			// now for validation !!
			$this->Entry->set($this->data);
			if($this->Entry->validates())
			{
				$this->Entry->create();
				$this->Entry->save($this->data);
				unset($this->data['Entry']['lang_code']);
				$galleryId = $this->Entry->id;
				$galleryCount = 0;
				$galleryTitle = $this->data['Entry']['title'];
				$galleryType = $this->data['Entry']['entry_type'];
				foreach ($this->data['Entry']['image'] as $key => $value)
				{
					$myImage = $this->Entry->findById($value);
					$this->data['Entry']['entry_type'] = $galleryType;
					$this->data['Entry']['title'] = $myImage['Entry']['title'];
					$this->data['Entry']['slug'] = $this->get_slug($myImage['Entry']['title']);
					$this->data['Entry']['main_image'] = $value;
					$this->data['Entry']['parent_id'] = $galleryId;
					$this->data['Entry']['created_by'] = $myCreator['Account']['user_id'];
					$this->data['Entry']['modified_by'] = $myCreator['Account']['user_id'];
					$this->Entry->create();
					$this->Entry->save($this->data);
					$galleryCount++;
				}

				// add COUNT to parent Entry...
				$this->Entry->id = $galleryId;
				$this->Entry->saveField('count' , $galleryCount);
				//--------------------------------- firstly create count-type in EntryMeta... ----------------------------- /////
				if($myType['Type']['parent_id'] == -1 && !empty($myEntry))
				{
					$updateCountType = $this->EntryMeta->find('first' , array(
						'conditions' => array(
							'EntryMeta.entry_id' => $myEntry['Entry']['id'],
							'EntryMeta.key' => 'count-'.$myChildTypeSlug
						)
					));
					if(!empty($updateCountType))
					{
						$this->EntryMeta->id = $updateCountType['EntryMeta']['id'];
						$this->EntryMeta->saveField('value' , $updateCountType['EntryMeta']['value']+1);
					}
					else
					{
						$this->data['EntryMeta']['entry_id'] = $myEntry['Entry']['id'];
						$this->data['EntryMeta']['key'] = 'count-'.$myChildTypeSlug;
						$this->data['EntryMeta']['value'] = 1;
						$this->EntryMeta->create();
						$this->EntryMeta->save($this->data);
					}
				}
				//------------------------------------ END OF create count-type in EntryMeta... -------------------------- /////

				// DELETE DUPLICATE ENTRY THAT HAS THE SAME LANG_CODE !!
				if(!empty($lang_code))
				{
					$this->Entry->deleteAll(array(
						'Entry.lang_code' => $lang_code,
						'Entry.status' => -1
					));
				}
				// NOW finally setFlash ^^
				$this->Session->setFlash($galleryTitle.' has been added.','success');
				$this->redirect (array('action' => $myType['Type']['slug'].(empty($myEntry)?'':'/'.$myEntry['Entry']['slug']).$myChildTypeLink.$myTranslation));
			}
			else
			{
				$this->Session->setFlash('Please complete all required fields.','failed');
				$this->redirect (array('action' => $myType['Type']['slug'].(empty($myEntry)?'':'/'.$myEntry['Entry']['slug']) ,(empty($lang_code)?'add':'edit/'.$prefield_slug).$myChildTypeLink.(empty($lang_code)?'':$myTranslation)));
			}
		}
	}

	/**
	* update gallery from gallery database
	* @param array $myType contains record query result of database type
	* @param array $myEntry contains record query result of the selected Entry
	* @param array $myParentEntry[optional] contains record query result of the parent Entry (used if want to search certain child Entry)
	* @param string $myChildTypeSlug[optional] contains slug of child type database (used if want to search certain child Entry)
	* @return array $result a selected entry with all of its attributes you'd requested
	* @public
	**/
	function _admin_gallery_edit($myType = array() , $myEntry = array() , $myParentEntry = array() , $myChildTypeSlug = NULL , $lang = NULL)
	{
		if ($this->RequestHandler->isAjax())
		{
			$this->layout = 'ajax';
			$data['isAjax'] = 1;
		}
		else
		{
			$data['isAjax'] = 0;
		}
		$this->setTitle(empty($myParentEntry)?(empty($myType)?$myEntry['Entry']['title']:$myType['Type']['name']):$myParentEntry['Entry']['title'].' - Edit '.$myEntry['Entry']['title']);
		$myChildType = $this->Type->findBySlug($myChildTypeSlug);
		$data['myType'] = $myType;
		$data['myEntry'] = $myEntry;
		$data['myParentEntry'] = $myParentEntry;
		$data['myChildType'] = $myChildType;
		// FIRSTLY, sorting our image children !!
		$tempChild = $this->Entry->find('all' , array(
			'conditions' => array(
				'Entry.parent_id' => $myEntry['Entry']['id']
			),
			'order' => array('Entry.id ASC')
		));
		$data['myEntry']['ChildEntry'] = $tempChild;

		// for image input type reason...
		$data['myImageTypeList'] = $this->EntryMeta->embedded_img_meta('type');
		// --------------------------------------------- LANGUAGE OPTION LINK ------------------------------------------ //
		$lang_opt = $this->Entry->find('all' , array(
			'conditions' => array(
				'Entry.status' => array(0,1),
				'Entry.lang_code LIKE' => '%-'.substr($myEntry['Entry']['lang_code'], 3)
			)
		));
		foreach ($lang_opt as $key => $value)
		{
			$language_link[substr($value['Entry']['lang_code'], 0,2)] = $value['Entry']['slug'];
		}
		$data['language_link'] = $language_link;
		$data['lang'] = $lang;
		if(!empty($myParentEntry))
		{
			$temp100 = $this->Entry->find('all' , array(
				'conditions' => array(
					'Entry.status' => array(0,1),
					'Entry.lang_code LIKE' => '%-'.substr($myParentEntry['Entry']['lang_code'], 3)
				)
			));
			foreach ($temp100 as $key => $value)
			{
				$parent_language[ substr($value['Entry']['lang_code'], 0,2) ] = $value['Entry']['slug'];
			}
			$data['parent_language'] = $parent_language;
		}
		// ------------------------------------------ END OF LANGUAGE OPTION LINK -------------------------------------- //
		// FINAL TOUCH !!
		$data['mySetting'] = $this->Setting->get_settings();
		$this->set('data' , $data);

		// if form submit is taken...
		if (!empty($this->data))
		{
			if(empty($lang))
			{
				// write my modifier ID...
				$myCreator = $this->Auth->user();
				$this->data['Entry']['modified_by'] = $myCreator['Account']['user_id'];

				// now for validation !!
				$this->Entry->set($this->data);
				if($this->Entry->validates())
				{
					$this->Entry->id = $myEntry['Entry']['id'];
					$this->Entry->save($this->data);
					$galleryId = $this->Entry->id;
					$galleryCount = 0;
					$galleryTitle = $this->data['Entry']['title'];

					// delete all the child image, and then add again !!
					$this->Entry->deleteAll(array('Entry.parent_id' => $galleryId));

					foreach ($this->data['Entry']['image'] as $key => $value)
					{
						$myImage = $this->Entry->findById($value);
						$this->data['Entry']['entry_type'] = $myEntry['Entry']['entry_type'];
						$this->data['Entry']['title'] = $myImage['Entry']['title'];
						$this->data['Entry']['slug'] = $this->get_slug($myImage['Entry']['title']);
						$this->data['Entry']['main_image'] = $value;
						$this->data['Entry']['parent_id'] = $galleryId;
						$this->data['Entry']['created_by'] = $myCreator['Account']['user_id'];
						$this->data['Entry']['modified_by'] = $myCreator['Account']['user_id'];
						$this->Entry->create();
						$this->Entry->save($this->data);
						$galleryCount++;
					}

					// add COUNT to parent Entry...
					$this->Entry->id = $galleryId;
					$this->Entry->saveField('count' , $galleryCount);
					// NOW finally setFlash ^^
					$this->Session->setFlash($galleryTitle.' has been updated.','success');
					$myChildTypeLink = (!empty($myParentEntry)&&$myType['Type']['slug']!=$myChildType['Type']['slug']?'?type='.$myChildType['Type']['slug']:'');
					$myTranslation = (empty($myChildTypeLink)?'?':'&').'lang='.substr($myEntry['Entry']['lang_code'], 0,2);
					$this->redirect (array('controller'=>(empty($myType)?'pages':'entries'),'action' => (empty($myType)?$myEntry['Entry']['slug']:$myType['Type']['slug']).(empty($myParentEntry)?'':'/'.$myParentEntry['Entry']['slug']).$myChildTypeLink.$myTranslation));
				}
				else
				{
					$this->Session->setFlash('Update failed. Please try again','failed');
					$this->redirect (array('action' => $myType['Type']['slug'].(empty($myParentEntry)?'':'/'.$myParentEntry['Entry']['slug']) , 'edit', $myEntry['Entry']['slug'].(!empty($myParentEntry)&&$myType['Type']['slug']!=$myChildType['Type']['slug']?'?type='.$myChildType['Type']['slug']:'')));
				}
			}
			else // ADD NEW TRANSLATION LANGUAGE !!
			{
				$passLangCode = $lang.substr( $myEntry['Entry']['lang_code'] , 2);
				$this->_admin_gallery_add($myType , $myParentEntry , $myChildTypeSlug , $passLangCode , $myEntry['Entry']['slug']);
			}
		}
		return $data;
	}

	/**
	* add new entry
	* @param array $myType contains record query result of database type
	* @param array $myEntry[optional] contains record query result of the selected Entry
	* @param string $myChildTypeSlug[optional] contains slug of child type database (used if want to search certain child Entry)
	* @return void
	* @public
	**/
	function _admin_default_add($myType = array() , $myEntry = array() , $myChildTypeSlug = NULL , $lang_code = NULL , $prefield_slug = NULL)
	{
		$data['mySetting'] = $this->Setting->get_settings();
		$this->setTitle((empty($myEntry)?(empty($myType)?'Pages':$myType['Type']['name']):$myEntry['Entry']['title']).' - Add New');
		$myChildType = $this->Type->findBySlug($myChildTypeSlug);
		$data['myType'] = $myType;
		$data['myParentEntry'] = $myEntry;
		$data['myChildType'] = $myChildType;
		if(!empty($myType))
		{
			// GENERATE TYPEMETA AGAIN WITH SORT ORDER !!
			$metaOrder = $this->TypeMeta->find('all' , array(
				'conditions' => array(
					'TypeMeta.type_id' => (empty($myEntry)?$myType['Type']['id']:$myChildType['Type']['id'])
				),
				'order' => array('TypeMeta.id ASC')
			));
			if(empty($myEntry))
			{
				$data['myType']['TypeMeta'] = $metaOrder;
			}
			else
			{
				$data['myChildType']['TypeMeta'] = $metaOrder;
			}
		}
		// --------------------------------------------- LANGUAGE OPTION LINK ------------------------------------------ //
		if(!empty($myEntry))
		{
			$temp100 = $this->Entry->find('all' , array(
				'conditions' => array(
					'Entry.status' => array(0,1),
					'Entry.lang_code LIKE' => '%-'.substr($myEntry['Entry']['lang_code'], 3)
				)
			));
			foreach ($temp100 as $key => $value)
			{
				$parent_language[ substr($value['Entry']['lang_code'], 0,2) ] = $value['Entry']['slug'];
			}
			$data['parent_language'] = $parent_language;
		}
		$data['lang'] = strtolower(empty($myEntry)?(empty($_SESSION['lang'])? substr($data['mySetting']['sites']['language'][0], 0,2):$_SESSION['lang']):substr($myEntry['Entry']['lang_code'], 0,2));
		// ------------------------------------------ END OF LANGUAGE OPTION LINK -------------------------------------- //
		// FINAL TOUCH !!
		$this->set('data' , $data);

		// if form submit is taken...
		if (!empty($this->data))
		{
			if(empty($lang_code) && !empty($myEntry) && substr($myEntry['Entry']['lang_code'], 0,2) != $this->data['language'])
			{
				$myEntry = $this->Entry->findByLangCode($this->data['language'].substr($myEntry['Entry']['lang_code'], 2));
			}
			// PREPARE DATA !!
			// START CUSTOM //
			// find detail volunteer and activity
			$volunteer = $this->Entry->findById($this->data['Member']['volunteer']);
			$activity = $this->EntryMeta->find('first', array(
				'conditions' => array(
					'Entry.entry_type' => 'activities',
					'Entry.id' => $this->data['Member']['activity'],
					'EntryMeta.key' => 'organization-id'
				)
			));
			$organization = $this->Entry->findById($activity['EntryMeta']['value']);
			
			if(!empty($myType) and $myType['Type']['slug'] == 'activity-members')
				$this->data['Entry'][0]['value'] = $volunteer['Entry']['title'].' dengan '.$organization['Entry']['title'];
			// END CUSTOM //
			$this->data['Entry']['title'] = $this->data['Entry'][0]['value'];
			$this->data['Entry']['description'] = $this->data['Entry'][1]['value'];
			$this->data['Entry']['main_image'] = $this->data['Entry'][2]['value'];

			// set the type of this entry...
			$this->data['Entry']['entry_type'] = (empty($myEntry)?(empty($myType)?'pages':$myType['Type']['slug']):$myChildType['Type']['slug']);
			// generate slug from title...
			$this->data['Entry']['slug'] = $this->get_slug($this->data['Entry']['title']);
			// write my creator...
			$myCreator = $this->Auth->user();
			// START CUSTOM //
			if(!empty($myType) and $myType['Type']['slug'] == 'activity-members')
				$myCreator['Account']['user_id'] = $this->data['Member']['volunteer'];
			// END CUSTOM //
			$this->data['Entry']['created_by'] = $myCreator['Account']['user_id'];
			$this->data['Entry']['modified_by'] = $myCreator['Account']['user_id'];
			// write time created manually !!
			$nowDate = $this->getNowDate();
			$this->data['Entry']['created'] = $nowDate;
			$this->data['Entry']['modified'] = $nowDate;
			// set parent_id
			$this->data['Entry']['parent_id'] = (empty($myEntry)?0:$myEntry['Entry']['id']);
			$this->data['Entry']['lang_code'] = strtolower(empty($lang_code)?$this->data['language']:$lang_code);

			// PREPARE FOR ADDITIONAL LINK OPTIONS !!
			$myChildTypeLink = (!empty($myEntry)&&$myType['Type']['slug']!=$myChildType['Type']['slug']?'?type='.$myChildType['Type']['slug']:'');
			$myTranslation = (empty($myChildTypeLink)?'?':'&').'lang='.substr($this->data['Entry']['lang_code'], 0,2);

			// now for validation !!
			$this->Entry->set($this->data);
			if($this->Entry->validates())
			{
				// --------------------------------- NOW for add / validate the details of this entry !!!
				$myDetails = $this->data['EntryMeta'];
				foreach ($myDetails as $key => $value)
				{
					// firstly DO checking validation from view layout !!!
					$myValid = explode('|', $value['validation']);
					foreach ($myValid as $key10 => $value10)
					{
						if(!$this->Validation->blazeValidate($value['value'],$value10))
						{
							$this->Session->setFlash('Please complete all required fields.','failed');
							$this->redirect (array('action' => $myType['Type']['slug'].(empty($myEntry)?'':'/'.$myEntry['Entry']['slug']) ,(empty($lang_code)?'add':'edit/'.$prefield_slug).$myChildTypeLink.(empty($lang_code)?'':$myTranslation)));
						}
					}
					// secondly DO checking validation from database !!!
					$state = 0;
					$myAutomaticValidation = (empty($myEntry)?$myType['TypeMeta']:$myChildType['TypeMeta']);
					foreach ($myAutomaticValidation as $key2 => $value2) // check for validation for each attribute key...
					{
						if($value['key'] == $value2['key']) // if find the same key...
						{
							$state = 1;
							$myValid = explode('|' , $value2['validation']);
							foreach ($myValid as $key3 => $value3)
							{
								if(!$this->Validation->blazeValidate($value['value'],$value3))
								{
									$this->Session->setFlash('Please complete all required fields.','failed');
									$this->redirect (array('action' => $myType['Type']['slug'].(empty($myEntry)?'':'/'.$myEntry['Entry']['slug']) ,(empty($lang_code)?'add':'edit/'.$prefield_slug).$myChildTypeLink.(empty($lang_code)?'':$myTranslation)));
								}
							}
							break;
						}
					}
					// if attribute key doesn't exist in type metas, therefore it must be added to type metas respectively...
					if($state == 0 && !empty($value['input_type']) && empty($lang_code))
					{
						$this->data['TypeMeta'] = $value;
						$this->data['TypeMeta']['type_id'] = (empty($myEntry)?$myType['Type']['id']:$myChildType['Type']['id']);
						$this->data['TypeMeta']['value'] = $value['optionlist'];
						$this->TypeMeta->create();
						$this->TypeMeta->save($this->data);
					}
				}
				// ------------------------------------- end of entry details...
				$this->Entry->create();
				$this->Entry->save($this->data);
				//--------------------------------- firstly create count-type in EntryMeta... ----------------------------- /////
				
				// START CUSTOM //
				
				if(!empty($myType) and $myType['Type']['slug'] == 'activities')
				{
					$this->data['EntryMeta']['entry_id'] = $this->Entry->id;
					$this->data['EntryMeta']['key'] = 'organization-id';
					$this->data['EntryMeta']['value'] = $this->data['Activity']['organization'];
					$this->EntryMeta->create();
					$this->EntryMeta->save($this->data);
				}
				else if(!empty($myType) and $myType['Type']['slug'] == 'activity-members')
				{
					$this->data['EntryMeta']['entry_id'] = $this->Entry->id;
					$this->data['EntryMeta']['key'] = 'activity-id';
					$this->data['EntryMeta']['value'] = $this->data['Member']['activity'];
					$this->EntryMeta->create();
					$this->EntryMeta->save($this->data);
				}
				
				// END CUSTOM //
				
				// START CREATE ATTACHMENT GALLERY //
				if($this->data['Entry']['entry_type'] == 'activities')
				{
					$galleryId = $this->Entry->id;
					$galleryCount = 0;
					$galleryTitle = $this->data['Entry']['title'];
					$galleryType = $this->data['Entry']['entry_type'];
					foreach ($this->data['Entry']['image'] as $key => $value)
					{
						$myImage = $this->Entry->findById($value);
						$this->data['Entry']['entry_type'] = $galleryType;
						$this->data['Entry']['title'] = $myImage['Entry']['title'];
						$this->data['Entry']['slug'] = $this->get_slug($myImage['Entry']['title']);
						$this->data['Entry']['main_image'] = $value;
						$this->data['Entry']['parent_id'] = $galleryId;
						$this->data['Entry']['created_by'] = $myCreator['Account']['user_id'];
						$this->data['Entry']['modified_by'] = $myCreator['Account']['user_id'];
						$this->Entry->create();
						$this->Entry->save($this->data);
						$galleryCount++;
					}

					// add COUNT to parent Entry...
					$this->Entry->id = $galleryId;
					$this->Entry->saveField('count' , $galleryCount);
				}
				// END CREATE ATTACHMENT GALLERY //
				
				//------------------------------------ START OF create SEO META in EntryMeta... -------------------------- /////
				
				for ($i = 1; $i <= count($this->data['EntryMetaSeo']); $i++) 
				{
					if(!empty($this->data['EntryMetaSeo'][$i]['value']))
					{
						$this->data['EntryMeta']['entry_id'] = $this->Entry->id;
						$this->data['EntryMeta']['key'] = $this->data['EntryMetaSeo'][$i]['key'];
						$this->data['EntryMeta']['value'] = $this->data['EntryMetaSeo'][$i]['value'];
						$this->EntryMeta->create();
						$this->EntryMeta->save($this->data);
					}
				}
				
				//------------------------------------ END OF create SEO META in EntryMeta... -------------------------- /////
				
				if($myType['Type']['parent_id'] == -1 && !empty($myEntry))
				{
					$updateCountType = $this->EntryMeta->find('first' , array(
						'conditions' => array(
							'EntryMeta.entry_id' => $myEntry['Entry']['id'],
							'EntryMeta.key' => 'count-'.$myChildTypeSlug
						)
					));
					if(!empty($updateCountType))
					{
						$this->EntryMeta->id = $updateCountType['EntryMeta']['id'];
						$this->EntryMeta->saveField('value' , $updateCountType['EntryMeta']['value']+1);
					}
					else
					{
						$this->data['EntryMeta']['entry_id'] = $myEntry['Entry']['id'];
						$this->data['EntryMeta']['key'] = 'count-'.$myChildTypeSlug;
						$this->data['EntryMeta']['value'] = 1;
						$this->EntryMeta->create();
						$this->EntryMeta->save($this->data);
					}
				}
				//------------------------------------ END OF create count-type in EntryMeta... -------------------------- /////
				$this->data['EntryMeta']['entry_id'] = $this->Entry->id;
				foreach ($myDetails as $key => $value)
				{
					if(!empty($value['value']) && substr($value['key'], 0,5) == 'form-')
					{
						$this->data['EntryMeta']['key'] = $value['key'];
						$this->data['EntryMeta']['value'] = ($value['input_type'] == 'checkbox'?implode("|",$value['value']):$value['value']);
						$this->EntryMeta->create();
						$this->EntryMeta->save($this->data);
					}
				}
				// add COUNT to parent Entry...
				if(!empty($myEntry))
				{
					$this->Entry->id = $myEntry['Entry']['id'];
					$this->Entry->saveField('count' , $myEntry['Entry']['count'] + 1);
				}
				// DELETE DUPLICATE ENTRY THAT HAS THE SAME LANG_CODE !!
				if(!empty($lang_code))
				{
					$this->Entry->deleteAll(array(
						'Entry.lang_code' => $lang_code,
						'Entry.status' => -1
					));
				}
				
				// NOW finally setFlash ^^
				$this->Session->setFlash($this->data['Entry']['title'].' has been added.','success');
				$this->redirect (array('action' => (empty($myType)?'pages':$myType['Type']['slug']).(empty($myEntry)?'':'/'.$myEntry['Entry']['slug']).$myChildTypeLink.$myTranslation));
			}
			else
			{
				$this->Session->setFlash('Please complete all required fields.','failed');
				$this->redirect (array('action' => $myType['Type']['slug'].(empty($myEntry)?'':'/'.$myEntry['Entry']['slug']) ,(empty($lang_code)?'add':'edit/'.$prefield_slug).$myChildTypeLink.(empty($lang_code)?'':$myTranslation)));
			}
		}
	}

	/**
	* update certain entry
	* @param array $myType contains record query result of database type
	* @param array $myEntry contains record query result of the selected Entry
	* @param array $myParentEntry[optional] contains record query result of the parent Entry (used if want to search certain child Entry)
	* @param string $myChildTypeSlug[optional] contains slug of child type database (used if want to search certain child Entry)
	* @return array $result a selected entry with all of its attributes you'd requested
	* @public
	**/
	function _admin_default_edit($myType = array() , $myEntry = array() , $myParentEntry = array() , $myChildTypeSlug = NULL , $lang = NULL)
	{
		if ($this->RequestHandler->isAjax())
		{
			$this->layout = 'ajax';
			$data['isAjax'] = 1;
		}
		else
		{
			$data['isAjax'] = 0;
		}
		
		// START CHECK TITLE SEO //
		if(!empty($myEntry['EntryMeta']))
		{
			foreach($myEntry['EntryMeta'] as $seo)
			{
				if($seo['key'] == 'SEO_Title')
				{
					$seoTitle = $seo['value'];
					break;
				}
			}
		}
		
		if(!empty($seoTitle))
			$this->setTitle($seoTitle);
		else
			if(!empty($myType))
				$this->setTitle($myEntry['Entry']['title']);
			else
				$this->setTitle(empty($myParentEntry)?(empty($myType)?$myEntry['Entry']['title']:$myType['Type']['name']):$myParentEntry['Entry']['title'].' - Edit '.$myEntry['Entry']['title']);
		// END CHECK TITLE SEO //
		
		// $this->setTitle(empty($myParentEntry)?(empty($myType)?$myEntry['Entry']['title']:$myType['Type']['name']):$myParentEntry['Entry']['title'].' - Edit '.$myEntry['Entry']['title']);
		$myChildType = $this->Type->findBySlug($myChildTypeSlug);
		$data['myType'] = $myType;
		$data['myEntry'] = $myEntry;
		$data['myParentEntry'] = $myParentEntry;
		$data['myChildType'] = $myChildType;
		
		// FIRSTLY, sorting our image children !!
		$tempChild = $this->Entry->find('all' , array(
			'conditions' => array(
				'Entry.parent_id' => $myEntry['Entry']['id'],
				'Entry.entry_type' => $myEntry['Entry']['entry_type']
			),
			'order' => array('Entry.id ASC')
		));
		$data['myEntry']['ChildEntry'] = $tempChild;
		
		if(!empty($myType))
		{
			// GENERATE TYPEMETA AGAIN WITH SORT ORDER !!
			$metaOrder = $this->TypeMeta->find('all' , array(
				'conditions' => array(
					'TypeMeta.type_id' => (empty($myParentEntry)?$myType['Type']['id']:$myChildType['Type']['id'])
				),
				'order' => array('TypeMeta.id ASC')
			));
			if(empty($myParentEntry))
			{
				$data['myType']['TypeMeta'] = $metaOrder;
			}
			else
			{
				$data['myChildType']['TypeMeta'] = $metaOrder;
			}
			
			// START ORDER //
			
			if($myType['Type']['slug'] == 'activities')
			{
				foreach($myEntry['EntryMeta'] as $meta)
				{
					if($meta['key'] == 'organization-id')
					{
						$orgId = $meta['value'];
						break;
					}
				}
				$this->set('orgId',$orgId);
			}
			else if($myType['Type']['slug'] == 'activity-members')
			{
				foreach($myEntry['EntryMeta'] as $meta)
				{
					if($meta['key'] == 'activity-id')
					{
						$activityId = $meta['value'];
						break;
					}
				}
				$this->set('activityId',$activityId);
				$this->set('volunteerId',$myEntry['Entry']['created_by']);
			}
			// END ORDER //
		}
		// for image input type reason...
		$data['myImageTypeList'] = $this->EntryMeta->embedded_img_meta('type');
		// --------------------------------------------- LANGUAGE OPTION LINK ------------------------------------------ //
		$lang_opt = $this->Entry->find('all' , array(
			'conditions' => array(
				'Entry.status' => array(0,1),
				'Entry.lang_code LIKE' => '%-'.substr($myEntry['Entry']['lang_code'], 3)
			)
		));
		foreach ($lang_opt as $key => $value)
		{
			$language_link[substr($value['Entry']['lang_code'], 0,2)] = $value['Entry']['slug'];
		}
		$data['language_link'] = $language_link;
		$data['lang'] = $lang;
		if(!empty($myParentEntry))
		{
			$temp100 = $this->Entry->find('all' , array(
				'conditions' => array(
					'Entry.status' => array(0,1),
					'Entry.lang_code LIKE' => '%-'.substr($myParentEntry['Entry']['lang_code'], 3)
				)
			));
			foreach ($temp100 as $key => $value)
			{
				$parent_language[ substr($value['Entry']['lang_code'], 0,2) ] = $value['Entry']['slug'];
			}
			$data['parent_language'] = $parent_language;
		}
		// ------------------------------------------ END OF LANGUAGE OPTION LINK -------------------------------------- //
		// FINAL TOUCH !!
		$data['mySetting'] = $this->Setting->get_settings();
		$this->set('data' , $data);
		
		// ------------------------------------------ START OF SEO META -------------------------------------- //
		
		foreach($myEntry['EntryMeta'] as $metaSeo)
		{
			if($metaSeo['key'] == 'SEO_Title')
				$seoTitle = $metaSeo['value'];
			else if($metaSeo['key'] == 'SEO_Keywords')
				$seoKeywords = $metaSeo['value'];
			else if($metaSeo['key'] == 'SEO_Description')
				$seoDescription = $metaSeo['value'];
		}
		$this->set(compact('seoTitle','seoKeywords','seoDescription'));
		
		// ------------------------------------------ END OF SEO META -------------------------------------- //

		// if form submit is taken...
		if (!empty($this->data))
		{
			if(empty($lang))
			{
				$this->data['Entry']['title'] = $this->data['Entry'][0]['value'];
				$this->data['Entry']['description'] = $this->data['Entry'][1]['value'];
				$this->data['Entry']['main_image'] = $this->data['Entry'][2]['value'];

				// write my modifier ID...
				$myCreator = $this->Auth->user();
				$this->data['Entry']['modified_by'] = $myCreator['Account']['user_id'];
				// write time modified manually !!
				$this->data['Entry']['modified'] = $this->getNowDate();

				// now for validation !!
				$this->Entry->set($this->data);
				if($this->Entry->validates())
				{
					// --------------------------------- NOW for validating the details of this entry !!!
					$myDetails = $this->data['EntryMeta'];
					foreach ($myDetails as $key => $value)
					{
						// firstly DO checking validation from view layout !!!
						$myValid = explode('|', $value['validation']);
						foreach ($myValid as $key10 => $value10)
						{
							if(!$this->Validation->blazeValidate($value['value'],$value10))
							{
								$this->Session->setFlash('Update failed. Please try again','failed');
								$this->redirect (array('action' => $myType['Type']['slug'].(empty($myParentEntry)?'':'/'.$myParentEntry['Entry']['slug']) , 'edit', $myEntry['Entry']['slug'].(!empty($myParentEntry)&&$myType['Type']['slug']!=$myChildType['Type']['slug']?'?type='.$myChildType['Type']['slug']:'')));
							}
						}
						// secondly DO checking validation from database !!!
						$myAutomaticValidation = (empty($myParentEntry)?$myType['TypeMeta']:$myChildType['TypeMeta']);
						foreach ($myAutomaticValidation as $key2 => $value2) // check for validation for each attribute key...
						{
							if($value['key'] == $value2['key']) // if find the same key...
							{
								$myValid = explode('|' , $value2['validation']);
								foreach ($myValid as $key3 => $value3)
								{
									if(!$this->Validation->blazeValidate($value['value'],$value3))
									{
										$this->Session->setFlash('Update failed. Please try again','failed');
										$this->redirect (array('action' => $myType['Type']['slug'].(empty($myParentEntry)?'':'/'.$myParentEntry['Entry']['slug']) , 'edit', $myEntry['Entry']['slug'].(!empty($myParentEntry)&&$myType['Type']['slug']!=$myChildType['Type']['slug']?'?type='.$myChildType['Type']['slug']:'')));
									}
								}
								break;
							}
						}
					}
					// ------------------------------------- end of entry details...
					$this->Entry->id = $myEntry['Entry']['id'];
					$this->Entry->save($this->data);
					
					// START CUSTOM //
					
					if(!empty($myEntry['Entry']['entry_type']) and $myEntry['Entry']['entry_type'] == 'activities')
					{
						// check meta organization id exist
						$checkOrgId = $this->EntryMeta->find('first', array(
							'conditions' => array(
								'EntryMeta.entry_id' => $myEntry['Entry']['id'],
								'EntryMeta.key' => 'organization-id'
							),
							'recursive' => -1
						));
						
						if(!empty($checkOrgId))
						{
							$this->EntryMeta->id = $checkOrgId['EntryMeta']['id'];
							$this->EntryMeta->saveField('value', $this->data['Activity']['organization']);
						}
						else
						{
							$this->data['EntryMeta']['entry_id'] = $myEntry['Entry']['id'];
							$this->data['EntryMeta']['key'] = 'organization-id';
							$this->data['EntryMeta']['value'] = $this->data['Activity']['organization'];
							$this->EntryMeta->create();
							$this->EntryMeta->save($this->data);
						}
					}
					else if(!empty($myEntry['Entry']['entry_type']) and $myEntry['Entry']['entry_type'] == 'activity-members')
					{
						// check meta activity id exist
						$checkActivityId = $this->EntryMeta->find('first', array(
							'conditions' => array(
								'EntryMeta.entry_id' => $myEntry['Entry']['id'],
								'EntryMeta.key' => 'activity-id'
							),
							'recursive' => -1
						));
						
						if(!empty($checkActivityId))
						{
							$this->EntryMeta->id = $checkActivityId['EntryMeta']['id'];
							$this->EntryMeta->saveField('value', $this->data['Member']['activity']);
						}
						else
						{
							$this->data['EntryMeta']['entry_id'] = $myEntry['Entry']['id'];
							$this->data['EntryMeta']['key'] = 'activity-id';
							$this->data['EntryMeta']['value'] = $this->data['Member']['activity'];
							$this->EntryMeta->create();
							$this->EntryMeta->save($this->data);
						}
						
						// update created by volunteer
						$this->Entry->id = $myEntry['Entry']['id'];
						$this->Entry->saveField('created_by', $this->data['Member']['volunteer']);
					}
					
					// END CUSTOM //
					
					// START EDIT ATTACHMENT GALLERY //
					$trueTitle = $this->data['Entry']['title'];
					if($myEntry['Entry']['entry_type'] == 'activities')
					{
						$galleryId = $this->Entry->id;
						$galleryCount = 0;
						$galleryTitle = $this->data['Entry']['title'];

						// delete all the child image, and then add again !!
						$this->Entry->deleteAll(array('Entry.parent_id' => $galleryId,'Entry.entry_type' => $myEntry['Entry']['entry_type']));

						foreach ($this->data['Entry']['image'] as $key => $value)
						{
							$myImage = $this->Entry->findById($value);
							$this->data['Entry']['entry_type'] = $myEntry['Entry']['entry_type'];
							$this->data['Entry']['title'] = $myImage['Entry']['title'];
							$this->data['Entry']['slug'] = $this->get_slug($myImage['Entry']['title']);
							$this->data['Entry']['main_image'] = $value;
							$this->data['Entry']['parent_id'] = $galleryId;
							$this->data['Entry']['created_by'] = $myCreator['Account']['user_id'];
							$this->data['Entry']['modified_by'] = $myCreator['Account']['user_id'];
							$this->Entry->create();
							$this->Entry->save($this->data);
							$galleryCount++;
						}

						// add COUNT to parent Entry...
						$this->Entry->id = $galleryId;
						$this->Entry->saveField('count' , $galleryCount);
					}
					// END EDIT ATTACHMENT GALLERY //
					
					// start edit seo meta //
					$this->EntryMeta->deleteAll(array('EntryMeta.entry_id' => $this->Entry->id , 'EntryMeta.key LIKE' => 'SEO_%'));
					
					for ($i = 1; $i <= count($this->data['EntryMetaSeo']); $i++) 
					{
						// find id seo meta
						// $idSeo = $this->EntryMeta->find('first', array(
							// 'conditions' => array(
								// 'EntryMeta.entry_id' => $myEntry['Entry']['id'],
								// 'EntryMeta.key' => $this->data['EntryMetaSeo'][$i]['key']
							// ),
							// 'recursive' => -1
						// ));
						// $this->EntryMeta->id = $idSeo['EntryMeta']['id'];
						// $this->EntryMeta->saveField('value', $this->data['EntryMetaSeo'][$i]['value']);
						
						if(!empty($this->data['EntryMetaSeo'][$i]['value']))
						{
							$this->data['EntryMeta']['entry_id'] = $this->Entry->id;
							$this->data['EntryMeta']['key'] = $this->data['EntryMetaSeo'][$i]['key'];
							$this->data['EntryMeta']['value'] = $this->data['EntryMetaSeo'][$i]['value'];
							$this->EntryMeta->create();
							$this->EntryMeta->save($this->data);
						}
					}
					// end edit seo meta //

					// delete all the attributes, and then add again !!
					$this->EntryMeta->deleteAll(array('EntryMeta.entry_id' => $this->Entry->id , 'EntryMeta.key LIKE' => 'form-%'));
					$this->data['EntryMeta']['entry_id'] = $this->Entry->id;
					foreach ($myDetails as $key => $value)
					{
						if(!empty($value['value']) && substr($value['key'], 0,5) == 'form-')
						{
							$this->data['EntryMeta']['key'] = $value['key'];
							$this->data['EntryMeta']['value'] = ($value['input_type'] == 'checkbox'?implode("|",$value['value']):$value['value']);
							$this->EntryMeta->create();
							$this->EntryMeta->save($this->data);
						}
					}
					// $this->Session->setFlash($this->data['Entry']['title'].' has been updated.','success');
					$this->Session->setFlash($trueTitle.' has been updated.','success');
					$myChildTypeLink = (!empty($myParentEntry)&&$myType['Type']['slug']!=$myChildType['Type']['slug']?'?type='.$myChildType['Type']['slug']:'');
					$myTranslation = (empty($myChildTypeLink)?'?':'&').'lang='.substr($myEntry['Entry']['lang_code'], 0,2);
					$this->redirect (array('action' => (empty($myType)?'pages':$myType['Type']['slug']).(empty($myParentEntry)?'':'/'.$myParentEntry['Entry']['slug']).$myChildTypeLink.$myTranslation));
				}
				else
				{
					$this->Session->setFlash('Update failed. Please try again','failed');
					$this->redirect (array('action' => (empty($myType)?'pages':$myType['Type']['slug']).(empty($myParentEntry)?'':'/'.$myParentEntry['Entry']['slug']) , 'edit', $myEntry['Entry']['slug'].(!empty($myParentEntry)&&$myType['Type']['slug']!=$myChildType['Type']['slug']?'?type='.$myChildType['Type']['slug']:'')));
				}
			}
			else // ADD NEW TRANSLATION LANGUAGE !!
			{
				$passLangCode = $lang.substr( $myEntry['Entry']['lang_code'] , 2);
				$this->_admin_default_add($myType , $myParentEntry , $myChildTypeSlug , $passLangCode , $myEntry['Entry']['slug']);
			}
		}
		return $data;
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid entry', true));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('entry', $this->Entry->read(null, $id));
	}

	function add() {
		if (!empty($this->data)) {
			$this->Entry->create();
			if ($this->Entry->save($this->data)) {
				$this->Session->setFlash(__('The entry has been saved', true));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The entry could not be saved. Please, try again.', true));
			}
		}
		$dbtypes = $this->Entry->Dbtype->find('list');
		$media = $this->Entry->Media->find('list');
		$parentEntries = $this->Entry->ParentEntry->find('list');
		$this->set(compact('dbtypes', 'media', 'parentEntries'));
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(__('Invalid entry', true));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->Entry->save($this->data)) {
				$this->Session->setFlash(__('The entry has been saved', true));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The entry could not be saved. Please, try again.', true));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->Entry->read(null, $id);
		}
		$dbtypes = $this->Entry->Dbtype->find('list');
		$media = $this->Entry->Media->find('list');
		$parentEntries = $this->Entry->ParentEntry->find('list');
		$this->set(compact('dbtypes', 'media', 'parentEntries'));
	}

	/**
	 * blueimp jQuery plugin function for initialize upload media image purpose
	 * @return void
	 * @public
	 **/
	public function UploadHandler()
	{
		$this->autoRender = FALSE;
		App::import('Vendor', 'uploadhandler');
		$upload_handler = new UploadHandler();

		$info = $upload_handler->post();

		// update database...
		if(isset($info[0]->name) && (!isset($info[0]->error)))
		{
			// set the type of this entry...
			$this->data['Entry']['entry_type'] = 'media';
			$this->data['Entry']['title'] = substr($info[0]->name, 0 , strripos($info[0]->name, '.'));
			// generate slug from title...
			$this->data['Entry']['slug'] = $this->get_slug($this->data['Entry']['title']);
			// write my creator...
			$myCreator = $this->Auth->user();
			$this->data['Entry']['created_by'] = $myCreator['Account']['user_id'];
			$this->data['Entry']['modified_by'] = $myCreator['Account']['user_id'];
			$this->Entry->create();
			$this->Entry->save($this->data);

			// save the image type...
			$mytype = substr($info[0]->type, strpos($info[0]->type, "/") + 1);
			$this->data['EntryMeta']['entry_id'] = $this->Entry->id;
			$this->data['EntryMeta']['key'] = 'image_type';
			$this->data['EntryMeta']['value'] = $mytype;
			$this->EntryMeta->create();
			$this->EntryMeta->save($this->data);
			// save the image size...
			$this->data['EntryMeta']['key'] = 'image_size';
			$this->data['EntryMeta']['value'] = $info[0]->size;
			$this->EntryMeta->create();
			$this->EntryMeta->save($this->data);

			// now for the next step....
			$myid = $this->Entry->id;

			// rename the filename...
			rename( WWW_ROOT.'img'.DS.'upload'.DS.'original'.DS.$info[0]->name , WWW_ROOT.'img'.DS.'upload'.DS.'original'.DS.$myid.'.'.$mytype);
			//rename( WWW_ROOT.'img'.DS.'upload'.DS.$info[0]->name , WWW_ROOT.'img'.DS.'upload'.DS.$myid.'.'.$mytype);

			// now generate for display and thumb image according to the media settings...
			$myType = $this->Type->findBySlug($this->data['Type']['slug']);
			$myMediaSettings = $this->Entry->getMediaSettings($myType);
			//Resize display image manually...
			$this->Entry->createDisplay($myid , $mytype , $myMediaSettings);
			//Resize original file for thumb...
			$this->Entry->createThumb($myid , $mytype , $myMediaSettings);
			//Create fixed image for display admin
			$this->Entry->createSettingThumb($myid , $mytype , 120, 120, 1);
		}
		
		$redirect = isset($_REQUEST['redirect']) ?
		stripslashes($_REQUEST['redirect']) : null;
		
		if (!empty($redirect)) {
			$json = json_encode($info);
			$this->redirect(sprintf($redirect, rawurlencode($json)));
		}
	}

	/**
	 * generate upload popup for uploading image to media library
 	 * @param string $myTypeSlug contains from what database type this function is called(used for media settings arrangements)
	 * @return void
	 * @public
	 **/
	public function upload_popup($myTypeSlug = NULL)
	{
		$this->layout = 'ajax';
		$this->set('myTypeSlug' , $myTypeSlug);
	}

	/**
	 * generate upload popup form for selecting image from media library
	 * @param integer $paging[optional] contains selected page of lists you want to retrieve
	 * @param string $myCaller[optional] contains type of method this popup is called
 	 * @param string $myTypeSlug[optional] contains from what database type this function is called(used for media settings arrangements)
	 * @return void
	 * @public
	 **/
	public function media_popup_single($paging = 1 , $mycaller = NULL , $myTypeSlug = NULL)
	{
		$this->layout='ajax';
		if($mycaller == NULL && $myTypeSlug == NULL)
		{
			$this->set('isAjax' , 1);
		}
		else
		{
			$this->set('isAjax' , 0);
		}
		$this->set('paging' , $paging);
		$this->set('myTypeSlug' , $myTypeSlug);

		$countPage = $this->countListPerPage;

		$options['conditions'] = array(
			'Entry.parent_id' => 0,
			'Entry.status' => 1,
			'Entry.entry_type' => 'media'
		);
		$resultTotalList = $this->Entry->find('count' , $options);
		$this->set('totalList' , $resultTotalList);

		$options['order'] = array('Entry.created DESC');
		$options['offset'] = ($paging-1) * $countPage;
		$options['limit'] = $countPage;
		$mysql = $this->Entry->find('all' ,$options);
		$this->set('myList' , $mysql);

		// set New countPage
		$newCountPage = ceil($resultTotalList * 1.0 / $countPage);
		$this->set('countPage' , $newCountPage);

		// set the paging limitation...
		$left_limit = 1;
		$right_limit = 5;
		if($newCountPage <= 5)
		{
			$right_limit = $newCountPage;
		}
		else
		{
			$left_limit = $paging-2;
			$right_limit = $paging+2;
			if($left_limit < 1)
			{
				$left_limit = 1;
				$right_limit = 5;
			}
			else if($right_limit > $newCountPage)
			{
				$right_limit = $newCountPage;
				$left_limit = $newCountPage - 4;
			}
		}
		$this->set('left_limit' , $left_limit);
		$this->set('right_limit' , $right_limit);

		// set mycaller...
		if($mycaller == NULL)
		{
			$this->set('mycaller' , '0');
		}
		else
		{
			$this->set('mycaller' , $mycaller);
		}
	}

	/**
	* display images info may have been used or not on pop up
	* @param integer $id get media id
	* @return void
	**/
	public function mediaused($id=NULL)
	{
		$this->autoRender = FALSE;
		if($id!=NULL)
		{
			// check for direct media_id in Entries...
			$result = $this->Entry->find('all' , array(
				'conditions' => array(
					'Entry.status' => array(0,1),
					'Entry.main_image' => $id
				),
				'order' => array('Entry.sort_order ASC')
			));

			foreach ($result as $key => $value)
			{
				echo '"' . $value['Entry']['entry_type'] . '" - ' . $value['Entry']['title'] . '
';
			}
		}
	}

	/**
	 * update entry slug through ajax
	 * @return void
	 * @public
	 **/
	function update_slug()
	{
		$this->autoRender = FALSE;
		$slug = $this->Entry->get_valid_slug(    $this->get_slug($this->params['form']['slug'])   ,  $this->params['form']['id']  );
		$this->Entry->id = $this->params['form']['id'];
		$this->Entry->saveField('slug' , $slug);
		echo $slug;
	}

	/**
	 * re-order entry sort_order for entries view order through ajax
	 * @return void
	 * @public
	 **/
	function reorder_list()
	{
		$this->autoRender = FALSE;
		$src = explode(',', $this->params['form']['src_order']);
		$dst = explode(',', $this->params['form']['dst_order']);
		unset($src[count($src)-1]);
		unset($dst[count($dst)-1]);
		foreach ($dst as $key => $value)
		{
			$fast_dst[$value] = $src[$key];
		}

		foreach ($src as $key => $value)
		{
			$temp = $this->Entry->findBySortOrder($value);
			$this->Entry->id = $temp['Entry']['id'];
			$result[$this->Entry->id] = $fast_dst[$this->Entry->id];
		}

		foreach ($result as $key => $value)
		{
			$this->Entry->id = $key;
			$this->Entry->saveField('sort_order' , $value);
		}
	}

	/*
		save data from front end
	*/
	function save($slug = "") {
		$this->autoRender = false;

		$account = $this->Auth->user();

		if (!empty($slug)) {
			$type = $this->Type->findBySlug($slug);
			if (!empty($type)) {
				// prepare save data
				$save_entry = array();
				$save_entry["Entry"] = array(
					"entry_type" => $slug,
					"title" => $this->data["Entry"]["title"],
					"slug" => $this->get_slug($this->data["Entry"]["title"]),
					"description" => $this->data["Entry"]["description"],
					"created_by" => (empty($account) ? 1 : $account["Account"]["user_id"]),
					"modified_by" => (empty($account) ? 1 : $account["Account"]["user_id"]),
					"lang_code" => "en"
				);

				// validate Entry
				$this->Entry->set($save_entry);
				$validate_success = false;
				if($this->Entry->validates()) {
					// validate and prepare EntryMeta
					$validate_success = true;
					foreach ($type["TypeMeta"] as $v)
						if (substr($v["key"], 0, 5) == "form-" and !empty($v["input_type"])) {
							$validation = explode("|", $v["validation"]);

							if (isset($this->data["EntryMeta"][$v["key"]])) {
								if ($v["input_type"] != "checkbox")
									$this->data["EntryMeta"][$v["key"]] = trim($this->data["EntryMeta"][$v["key"]]);
								foreach ($validation as $v2) {
									$v2 = trim($v2);
									if (!empty($v2) and !$this->Validation->blazeValidate($this->data["EntryMeta"][$v["key"]], $v2)) {
										$validate_success = false;
										break;
									}
								}

								// extra check value for input type checkbox and radio button
								$original_value = explode("\r\n", $v["value"]);
								if ($v["input_type"] == "checkbox") {
									foreach ($this->data["EntryMeta"][$v["key"]] as &$v2) {
										$v2 = trim(strtolower($v2));
										if (!in_array($v2, array_map("strtolower", $original_value))) {
											$validate_success = false;
											break;
										}
									}
									$this->data["EntryMeta"][$v["key"]] = implode("|", $this->data["EntryMeta"][$v["key"]]);
								}
								else if ($v["input_type"] == "radio") {
									$this->data["EntryMeta"][$v["key"]] = trim(strtolower($this->data["EntryMeta"][$v["key"]]));
									if (!in_array($this->data["EntryMeta"][$v["key"]], array_map("strtolower", $original_value))) {
										$validate_success = false;
										break;
									}
								}

								$save_entry["EntryMeta"][] = array(
									"key" => $v["key"],
									"value" => $this->data["EntryMeta"][$v["key"]]
								);
							}
							else if (in_array("not_empty", array_map("strtolower", $validation))) {
								$validate_success = false;
								break;
							}
						}
				}

				if ($validate_success) {
					$this->Entry->saveAll($save_entry);

					// success saving
					if ($this->params["isAjax"])
						return true;
					else
						$this->redirect("/");
				}
			}
		}
		// fail saving
		if ($this->params["isAjax"])
			return false;
		else
			$this->redirect("/");
	}
	
	// START CUSTOM //
	
	function ajax_send_organization()
	{
		$this->autoRender = false;
		$emailValidation = filter_var($this->data['Organization']['email'], FILTER_VALIDATE_EMAIL);
		if($emailValidation)
		{
			// if(isset($this->data['Organization']['check1']) and isset($this->data['Organization']['check2']))
				// return "* Pilih satu kategori saja.";
			// else if(!isset($this->data['Organization']['check1']) and !isset($this->data['Organization']['check2']))
				// return "* Form harus diisi dengan benar.";
			
			// check email exist
			$exist = $this->UserMeta->find('first', array(
				'conditions' => array(
					'UserMeta.key' => 'email',
					'UserMeta.value' => $this->data['Organization']['email']
				)
			));
			
			if(!empty($exist))
				return "* Email sudah terdaftar.";
			else
			{
				// Create database organization
				if(isset($this->data['Organization']['check1']) and $this->data['Organization']['check1'] == 'Organisasi')
				{
					$type = $this->data['Organization']['check1'];
					$entryType = "organizations";
					$role = 6;
				}
				else if(isset($this->data['Organization']['check1']) and $this->data['Organization']['check1'] == 'Sukarelawan')
				{
					$type = $this->data['Organization']['check1'];
					$entryType = "volunteers";
					$role = 4;
				}
			
				// Create user participation and meta
				$name = explode('@',$this->data['Organization']['email']);
		
				$this->data['User']['firstname'] = $this->data['Organization']['name'];
				$this->data['User']['lastname'] = "";
				$this->data['User']['role_id'] = $role;
				$this->data['User']['created_by'] = 2;
				$this->data['User']['modified_by'] = 2;
				$this->data['User']['status'] = 0;
							
				$this->User->create();
				$this->User->save($this->data);
				
				$this->data['UserMeta']['user_id'] =  $this->User->id;
				$this->data['UserMeta']['key'] = 'email';
				$this->data['UserMeta']['value'] = $this->data['Organization']['email'];
				
				$this->UserMeta->create();
				$this->UserMeta->save($this->data);
				
				
				if($entryType == "volunteers")
				{
					// $description = '<div class="idrw-titcon">';
					// $description .= '<h3>TUGAS SUKARELAWAN :</h3>';
					// $description .= '<p>-</p>';
					// $description .= '</div>';
					// $description .= '<div class="idrw-titcon">';
					// $description .= '<h3>TUJUAN :</h3>';
					// $description .= '-';
					// $description .= '</div>';
					$description = "";
				}
				else
				{
					$description = '<div class="idrw-inline">';
					$description .= '<p><h3>Fokus : </h3> Ekonomi, Lingkungan</p>';
					$description .= '</div>';
					$description .= '<div class="idrw-titcon">';
					$description .= '<h3>Visi : </h3>';
					$description .= '<p>Meningkatkan kodisi sosial ekonomi dari komunitas di daerah pedesaan dan terpencil. IBEKA didirikan untuk membantu masyarakat dalam bidang energy dan ekonomi kerakyatan.</p>';
					$description .= '</div>';
					$description .= '<div class="idrw-titcon">';
					$description .= '<h3>Misi : </h3>';
					$description .= '<p>Implementasi dan promosi teknologi tepat guna, terutama teknologi pembangkit listrik mikro hidro, termasuk pengembangan program yang membuat masyarakat menjadi mandiri secala sosial dan ekonomi.</p>';
					$description .= '</div>';
				}
				
				$this->data['Entry']['entry_type'] = $entryType;
				$this->data['Entry']['title'] = $this->data['Organization']['name'];
				$this->data['Entry']['slug'] = $this->get_slug($this->data['Organization']['name']);
				$this->data['Entry']['description'] = $description;
				$this->data['Entry']['created_by'] = $this->User->id;
				$this->data['Entry']['modified_by'] = $this->User->id;
				$this->data['Entry']['lang_code'] = "en";
				
				$this->Entry->create();
				$this->Entry->save($this->data);
				
				// find email from setting
				$email = $this->Setting->find('first', array(
					'conditions' => array(
						'Setting.name' => 'info',
						'Setting.key' => 'email'
					),
					'recursive' => -1
				));
				
				$this->Email->from = $this->data['Organization']['email'];
				$this->Email->to = $email['Setting']['value'];
				$this->Email->subject = 'Registrasi Indorelawan';
				
				$body = array();
				if($entryType == "volunteers")
				{
					$body[] = "Nama Sukarelawan : ".$this->data['Organization']['name']."<br />";
					$body[] = "Alamat Email : ".$this->data['Organization']['email'];
				}
				else
				{
					$body[] = "Nama Organisasi : ".$this->data['Organization']['name']."<br />";
					$body[] = "Alamat Email : ".$this->data['Organization']['email'];
				}
				$success = $this->Email->send($body);
				
				return "* Pendaftaran email berhasil.";
			}
		}
		else
			return "* Form harus diisi dengan benar.";
	}
	
	function ajax_load_more()
	{
		$data['myImageTypeList'] = $this->EntryMeta->embedded_img_meta('type');
		// find last featured id
		$id = $this->Entry->find("first", array(
			"conditions" => array(
				"Entry.slug" => $this->params['form']['lastFeatured']
			),
			"recursive" => -1
		));
		
		$lastFeatured = $this->Entry->find("all", array(
			"conditions" => array(
				"Entry.entry_type" => "activity-members",
				"Entry.parent_id" => 0,
				"Entry.status" => 1,
				"Entry.description <>" => ""
			),
			"recursive" => -1,
			"limit" => 1,
			"order" => "Entry.created ASC"
		));
		
		if($lastFeatured['Entry']['slug'] == $this->params['form']['lastFeatured'])
		{
			$this->set("status",0);
		}
		else
		{
			$featured = $this->Entry->find("all", array(
				"conditions" => array(
					"Entry.entry_type" => "activity-members",
					"Entry.parent_id" => 0,
					"Entry.status" => 1,
					"Entry.description <>" => "",
					"Entry.sort_order <" => $id['Entry']['sort_order']
				),
				"recursive" => -1,
				"limit" => 1,
				"order" => "Entry.created DESC"
			));
			$this->set("status",1);
			$this->set("featured",$featured);
			$this->set("data",$data);
		}
		$this->render($this->frontEndFolder."ajax_load_more");
	}
	
	function export() {
		$this->layout = "ajax";
		
		$data = $this->User->find("all", array(
			"conditions" => array (
				"User.role_id" => array(4, 6)
			),
			"recursive" => -1,
			"order" => "User.created asc"
		));
		
		$posts = array();
		foreach ($data as $v) {
			if($v["User"]["role_id"] == 4)
				$type = "Sukarelawan";
			else
				$type = "Organisasi";
				
			$email = $this->UserMeta->find('first', array(
				'conditions' => array(
					'UserMeta.user_id' => $v["User"]["id"],
					'UserMeta.key' => 'email'
				)
			));
		
			$posts[] = array(
				"Participant" => array(
					"Tipe" => $type,
					"Nama" => $v["User"]["firstname"].' '.$v["User"]["lastname"],
					"Email" => $email['UserMeta']['value'],
					"Daftar Tanggal" => $v["User"]["created"]
				)
			);
		}

		// Make the data available to the view (and the resulting CSV file)
		$this->set("posts", $posts);
	}
	
	// END CUSTOM //
}
