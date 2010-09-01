<?php
	
	require_once(EXTENSIONS . '/entry_versions/lib/class.entryversionsmanager.php');
	require_once(EXTENSIONS . '/entry_versions/lib/class.domconverter.php');
	
	Class fieldEntry_Versions extends Field{	
		
		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = 'Entry Versions';
			$this->_required = false;
		}
				
		public function processRawFieldData($data, &$status, $simulate=false, $entry_id=null) {	
			$status = self::__OK__;			
			return array(
				'value' => '',
				'last_modified' => DateTimeObj::get('Y-m-d H:i:s', time()),
				'last_modified_author' => Administration::instance()->Author->getFullName()
			);
		}
		
		public function displaySettingsPanel(&$wrapper, $errors = null) {
			
			$wrapper->appendChild(new XMLElement('h4', ucwords($this->name())));
			$wrapper->appendChild(Widget::Input('fields['.$this->get('sortorder').'][type]', $this->handle(), 'hidden'));
			if($this->get('id')) $wrapper->appendChild(Widget::Input('fields['.$this->get('sortorder').'][id]', $this->get('id'), 'hidden'));
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Label (fixed)'));
			$label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][label]', 'Entry Versions', null, array('readonly'=>'readonly')));
			if(isset($errors['label'])) $div->appendChild(Widget::wrapFormElementWithError($label, $errors['label']));
			else $div->appendChild($label);		
			
			$div->appendChild($this->buildLocationSelect($this->get('location'), 'fields['.$this->get('sortorder').'][location]'));
			
			$wrapper->appendChild($div);
			
			$order = $this->get('sortorder');
			
			$label = Widget::Label();
			$input = Widget::Input("fields[{$order}][hide_in_publish]", 'yes', 'checkbox');
			if ($this->get('show_in_publish') == 'no') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' Hide version history list on publish page');
			$wrapper->appendChild($label);
			
			$this->appendShowColumnCheckbox($wrapper);
			
		}
		
		function commit(){
			if(!parent::commit()) return false;			
			$id = $this->get('id');
			if($id === false) return false;			
			$fields = array();			
			$fields['field_id'] = $id;
			$fields['show_in_publish'] = ($this->get('hide_in_publish') == 'yes') ? 'no' : 'yes';
			$this->_engine->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return $this->_engine->Database->insert($fields, 'tbl_fields_' . $this->handle());			
		}
		
		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){					
			
			if ($this->get('show_in_publish')=='no') return;
			
			$callback = Administration::instance()->getPageCallback();
			$entry_id = $callback['context']['entry_id'];
			
			$viewing_version = $_GET['version'];
			
			$container = new XMLElement('div', null, array('class' => 'container'));
			
			if (!$entry_id) {
				$container->appendChild(
					new XMLElement('p', 'Version 1 will be created when you save.')
				);
				$wrapper->appendChild($container);
				return;
			}
			
			$label = new XMLElement('label');
			
			$minor_edit_attributes = array('checked' => 'checked');
			if (isset($viewing_version)) $minor_edit_attributes = array('disabled' => 'disabled', 'checked' => 'checked');
			
			$input = Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, 'yes', 'checkbox', $minor_edit_attributes);
			$label->setValue($input->generate(false) . ' Create new version (major edit)');
			
			if (isset($viewing_version)) {
				$label->appendChild(
					Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, 'yes', 'hidden')
				);
			}
			
			$revision_history = new XMLElement('ol');
			$revision_history->setAttribute('class', 'revisions');
			
			$i = 0;
			$entries = EntryVersionsManager::entryHistory($entry_id);
			foreach($entries as $entry) {
				
				$meta = $entry->documentElement;
				
				$href = '/symphony' . $callback['pageroot'] . $callback['context']['page'] . '/' . $entry_id;
				if ($i != 0) {
					$href .= '/?version=' . $meta->getAttribute('version');
				}
				$dom_revision = new XMLElement(
					'a',
					'Version ' . $meta->getAttribute('version'),
					array(
						'href' => $href
					)
				);
				
				$timestamp = strtotime($meta->getAttribute('created-date') . ' ' . $meta->getAttribute('created-time'));
				
				$dom_created = new XMLElement(
					'span',
					'on ' . DateTimeObj::get(__SYM_DATE_FORMAT__, $timestamp) . ' ' . DateTimeObj::get(__SYM_TIME_FORMAT__, $timestamp),
					array('class' => 'date')
				);
				
				$dom_author = new XMLElement(
					'span',
					'by ' . $meta->getAttribute('created-by'),
					array('class' => 'author')
				);
				
				$dom_li = new XMLElement('li');
				if (!isset($viewing_version) && $i == 0) $dom_li->setAttribute('class', 'viewing');
				if (isset($viewing_version) && (int)$viewing_version == (int)$meta->getAttribute('version')) $dom_li->setAttribute('class', 'viewing');
				
				
				$dom_li->appendChild($dom_revision);
				$dom_li->appendChild($dom_author);
				$dom_li->appendChild($dom_created);				
				$revision_history->appendChild($dom_li);
				
				$i++;
				
			}
			
			$container->appendChild($label);
			$container->appendChild($revision_history);
			
			$wrapper->appendChild($container);
			
			
		}
		
		public function prepareTableValue($data, XMLElement $link=NULL, $entry_id) {
			
			$version = EntryVersionsManager::getLatestVersion($entry_id);
			
			if (!$version) return sprintf('<span class="inactive">%s</span>', 'Unversioned');
			
			$meta = $version->documentElement;
			
			$timestamp = strtotime($meta->getAttribute('created-date') . ' ' . $meta->getAttribute('created-time'));
			$date = DateTimeObj::get(__SYM_DATE_FORMAT__, $timestamp) . ' ' . DateTimeObj::get(__SYM_TIME_FORMAT__, $timestamp);
			$author = $meta->getAttribute('created-by');
			
			return sprintf('Version %d <span class="inactive">on %s by %s</span>', $version, $date, $author);
		}
		
		public function appendFormattedElement(&$wrapper, $data, $encode=false, $mode=null) {
			$entry_id = $wrapper->getAttribute('id');
			
			$versions = new XMLElement('versions');
			$entries = EntryVersionsManager::entryHistory($entry_id);
			foreach($entries as $entry) {				
				$versions->appendChild(DOMConverter::toXMLElement($entry));
			}
			
			$wrapper->appendChild($versions);
			
		}
				
		public function createTable(){
			
			return $this->Database->query(
			
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `value` double default NULL,
				  `last_modified` datetime default NULL,
				  `last_modified_author` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `value` (`value`)
				) TYPE=MyISAM;"
			
			);
		}
						
	}

?>