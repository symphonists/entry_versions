<?php
	
	require_once(EXTENSIONS . '/entry_versioning/lib/class.versionmanager.php');
	
	class extension_entry_versioning extends Extension {
		
		public function about() {
			return array(
				'name'			=> 'Entry Versions',
				'version'		=> '0.1 Alpha',
				'release-date'	=> '2010-06-22',
				'author'		=> array(
					'name'			=> 'Nick Dunn',
					'website'		=> 'http://nick-dunn.co.uk'
				),
				'description' => 'Create, browse and restore entry versions.'
			);
		}
		
		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_entry_version`");
		}
		
		public function install() {
			Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_fields_entry_version` (
					`id` int(11) NOT NULL auto_increment,
					`field_id` int(11) NOT NULL,
					PRIMARY KEY (`id`)
				)"
			);
			return true;
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'InitaliseAdminPageHead',
					'callback'	=> 'initializeAdmin'
				),
				array(
					'page'		=> '/publish/new/',
					'delegate'	=> 'EntryPostCreate',
					'callback'	=> 'saveVersion'
				),				
				array(
					'page'		=> '/publish/edit/',
					'delegate'	=> 'EntryPostEdit',
					'callback'	=> 'saveVersion'
				),
				array(
					'page'		=> '/publish/edit/',
					'delegate'	=> 'EntryPreRender',
					'callback'	=> 'renderVersion'
				),
				array(
					'page' => '/frontend/',
					'delegate' => 'EventPostSaveFilter',
					'callback' => 'saveVersion'
				),
			);
		}
		
		public function initializeAdmin($context) {	
			$page = $context['parent']->Page;
			
			$callback = Administration::instance()->getPageCallback();
					
			if ($page instanceof contentPublish and in_array($page->_context['page'], array('new', 'edit'))) {
				$page->addStylesheetToHead(URL . '/extensions/entry_versioning/assets/entry-versioning.css', 'screen', 9359351);
				$page->addScriptToHead(URL . '/extensions/entry_versioning/assets/entry-versioning.js', 9359352);
			}
			
		}
		
		/*
		Just before saving a new entry, ...
		*/
		public function saveVersion(&$context) {
			$entry = $context['entry'];
			$fields = $context['fields'];
			
			$version = VersionManager::saveVersion($entry, $fields, ($fields['entry-versions'] != 'yes'));
			$context['messages'][] = array('version', 'passed', $version);
			
		}
		
		/*
		Just before rendering an entry for editing, hijack and insert versioned entry for editing
		*/
		public function renderVersion($context) {
			$section = $context['section'];
			$entry = $context['entry'];
			$fields = $context['fields'];
			
			$entry_id = $entry->get('id');
			$version = $_GET['version'];
			
			if (!isset($version)) return false;

			if ($entry) $context['entry'] = VersionManager::unserializeEntry($entry_id, $version);
			
		}
		
			
	}
?>