<?php

require_once(TOOLKIT . '/class.datasource.php');
require_once(TOOLKIT . '/class.xsltprocess.php');

Class EntryVersionsManager {
	
	public function Context() {
		if(class_exists('Frontend')){
			return (object)Frontend::instance();
		}
		return (object)Administration::instance();
	}
	
	// saves an entry to disk
	public static function saveVersion($entry, $fields, $is_update) {
		
		// list existing versions of this entry
		$existing_versions = General::listStructure(MANIFEST . '/versions/' . $entry->get('id') . '/', '/.xml$/');
		
		// create folder
		if (!file_exists(MANIFEST . '/versions/' . $entry->get('id'))) {
			General::realiseDirectory(MANIFEST . '/versions/' . $entry->get('id'));
		}
		
		// max version number
		$new_version_number = count($existing_versions['filelist']);
		$new_version_number++;
		
		if ($is_update) $new_version_number--;
		
		if ($new_version_number == 0) $new_version_number++;
		
		unset($fields['entry-versions']);
		
		// run custom DS to get the built XML of this entry
		$ds = new EntryVersionsXMLDataSource(self::Context(), null, false);
		$ds->dsParamINCLUDEDELEMENTS = array_keys($fields);
		$ds->dsParamFILTERS['id'] = $entry->get('id');
		$ds->dsSource = (string)$entry->get('section_id');
		
		$param_pool = array();
		$entry_xml = $ds->grab($param_pool);

		// get text value of the entry
		$proc = new XsltProcess;
		$data = $proc->process(
			$entry_xml->generate(),
			file_get_contents(EXTENSIONS . '/entry_versions/lib/entry-version.xsl'),
			array(
				'version' => $new_version_number,
				'created-by' => ((self::Context()->Author) ? self::Context()->Author->getFullName() : ''),
				'created-date' => date('Y-m-d', time()),
				'created-time' => date('H:i', time()),
			)
		);
		
		$write = General::writeFile(MANIFEST . '/versions/' . $entry->get('id') . '/' . $new_version_number . '.xml', $data);
		General::writeFile(MANIFEST . '/versions/' . $entry->get('id') . '/' . $new_version_number . '.dat', self::serializeEntry($entry));
		
		return $new_version_number;
		
	}
	
	public static function entryHistory($entry_id) {
		
		if (!$entry_id) return array();
		
		$entries = array();
		
		$files = General::listStructure(MANIFEST . '/versions/' . $entry_id . '/', '/.xml$/', false, 'desc');
		if (!is_array($files['filelist'])) $files['filelist'] = array();
		
		natsort($files['filelist']);
		$files['filelist'] = array_reverse($files['filelist']);

		
		foreach($files['filelist'] as $file) {
			$entry = new DomDocument();
			$entry->load(MANIFEST . '/versions/' . $entry_id . '/' . $file);
			$entries[] = $entry;			
		}
		
		return $entries;
		
	}
	
	public static function getLatestVersion($entry_id) {
		
		$files = General::listStructure(MANIFEST . '/versions/' . $entry_id . '/', '/.xml$/', false, 'desc');
		if (!is_array($files['filelist'])) $files['filelist'] = array();
		
		if (count($files['filelist']) == 0) return;
		
		natsort($files['filelist']);
		$files['filelist'] = array_reverse($files['filelist']);
		
		$file = reset($files['filelist']);
		
		$entry = new DomDocument();
		$entry->load(MANIFEST . '/versions/' . $entry_id . '/' . $file);
		
		return $entry;
		
	}
	
	public function serializeEntry($entry) {
		$entry->findDefaultData();		
		$entry = array(
			'id' => $entry->get('id'),
			'author_id' => $entry->get('author_id'),
			'section_id' => $entry->get('section_id'),
			'creation_date' => $entry->get('creation_date'),
			'creation_date_gmt' => $entry->get('creation_date_gmt'),
			'data' => $entry->getData()
		);	
		return serialize($entry);
	}	
	
	// rebuild entry object from a previous version
	public function unserializeEntry($entry_id, $version) {
		$entry = unserialize(
			file_get_contents(MANIFEST . '/versions/' . $entry_id . '/' . $version . '.dat')
		);
		
		$entryManager = new EntryManager(self::Context());		
		$new_entry = $entryManager->create();
		$new_entry->set('id', $entry['id']);
		$new_entry->set('author_id', $entry['author_id']);
		$new_entry->set('section_id', $entry['section_id']);
		$new_entry->set('creation_date', $entry['creation_date']);
		$new_entry->set('creation_date_gmt', $entry['creation_date_gmt']);
		
		foreach($entry['data'] as $field_id => $value) {
			$new_entry->setData($field_id, $value);
		}
		
		return $new_entry;
	}
	
}

Class EntryVersionsXMLDataSource extends Datasource{
	
	public $dsParamROOTELEMENT = 'entries';
	public $dsSource = null;
	
	public $dsParamORDER = 'desc';
	public $dsParamLIMIT = '1';
	public $dsParamREDIRECTONEMPTY = 'no';
	public $dsParamSORT = 'system:id';
	public $dsParamSTARTPAGE = '1';		
	
	public function __construct(&$parent, $env=NULL, $process_params=true){
		parent::__construct($parent, $env, $process_params);
	}
	
	public function getSource(){
		return $this->dsSource;
	}
	
	public function grab(&$param_pool){

		$result = new XMLElement($this->dsParamROOTELEMENT);
		
		try{
			include(TOOLKIT . '/data-sources/datasource.section.php');
		}
		catch(Exception $e){
			$result->appendChild(new XMLElement('error', $e->getMessage()));
			return $result;
		}
		if($this->_force_empty_result) $result = $this->emptyXMLSet();

		return $result;		

	}
	
}
