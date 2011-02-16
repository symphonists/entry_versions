<?php
	
	require_once(EXTENSIONS . '/entry_versions/lib/class.entryversionsmanager.php');
	
	class extension_entry_versions extends Extension {
		
		public function about() {
			return array(
				'name'			=> 'Entry Versions',
				'version'		=> '0.3.1',
				'release-date'	=> '2010-09-01',
				'author'		=> array(
					'name'			=> 'Nick Dunn',
					'website'		=> 'http://nick-dunn.co.uk'
				),
				'description' => 'Create, browse and restore entry versions.'
			);
		}
		
		public function uninstall() {
			Symphony::Database()->query("DROP TABLE `tbl_fields_entry_versions`");
		}
		
		public function install() {
			Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_fields_entry_versions` (
					`id` int(11) NOT NULL auto_increment,
					`field_id` int(11) NOT NULL,
					`show_in_publish` enum('yes','no') default 'no',
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
				
				$page->addElementToHead(new XMLElement(
					'script',
					"Symphony.Context.add('entry_versions', " . json_encode(array('version' => $_GET['version'])) . ")",
					array('type' => 'text/javascript')
				), 9359350);
				
				$page->addStylesheetToHead(URL . '/extensions/entry_versions/assets/entry_versions.publish.css', 'screen', 9359351);
				$page->addScriptToHead(URL . '/extensions/entry_versions/assets/entry_versions.publish.js', 9359352);
			}
			
		}
		
		/*
		Just before saving a new entry, ...
		*/
		public function saveVersion(&$context) {
			$section = $context['section'];
			$entry = $context['entry'];
			$fields = $context['fields'];
			
			$is_update = ($fields['entry-versions'] != 'yes');
			
			// find the Entry Versions field in the section and remove its presence from
			// the copied POST array, so that its value is not saved against the version
			foreach($section->fetchFields() as $field) {
				if($field->get('type') == 'entry_versions') unset($fields[$field->get('element_name')]);
			}
			
			$version = EntryVersionsManager::saveVersion($entry, $fields, $is_update, $entry_version_field_name);
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

			if ($entry) $context['entry'] = EntryVersionsManager::unserializeEntry($entry_id, $version);
			
		}
		
			
	}
?>