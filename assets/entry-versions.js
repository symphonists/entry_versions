/*-----------------------------------------------------------------------------
	Language strings
-----------------------------------------------------------------------------*/	 

	Symphony.Language.add({
		'Show all versions': false,
		'Save as new version (major edit)': false,
		'Update this version (minor edit)': false
	}); 
	

/*-----------------------------------------------------------------------------
	Entry Versions
-----------------------------------------------------------------------------*/

	var EntryVersions = {
		
		$field: null,
		$revisions: null,
		$more: null,
		limit: 5,
		
		init: function() {
			var self = this;
			
			this.$field = jQuery('.field-entry_versions div');
			this.$revisions = this.$field.find('ol.revisions');
			
			var total_revisions = this.$revisions.find('li').length;
			var selected_revision = this.$revisions.find('li.viewing').index() + 1;
			var show_until = ((selected_revision >= this.limit) ? selected_revision + 1 : this.limit);
			
			this.$revisions.find('li:gt('+(show_until-1)+')').addClass('limit');
			
			if (show_until < total_revisions) {
				this.$field.append('<a href="#" class="more">' + Symphony.Language.get('Show all versions') + '</a>');
				this.$more = this.$field.find('a.more');

				this.$more.bind('click', function(e) {
					e.preventDefault();
					self.$revisions.find('li.limit').toggle();
					jQuery(this).remove();
				});
			}
			
			var submit_new = jQuery('input[name="action[save]"]');
			var submit_update = jQuery('<label id="entry-versions-actions"><input type="checkbox" name="fields[entry-versions]" checked="checked" value="yes" />Create new version</label>');
			submit_new.after(submit_update);
			
		}
		
	};
	
	jQuery(document).ready(function() {
		if (jQuery('.field-entry_versions').length) EntryVersions.init();
	});