/*-----------------------------------------------------------------------------
	Language strings
-----------------------------------------------------------------------------*/	 

	Symphony.Language.add({
		'Show all versions': false,
		'Save as new version': false,
		'Update this version': false
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
			
			var update_checkbox = jQuery('input[name="fields[entry-versions]"]');
			
			var submit_new = jQuery('input[name="action[save]"]');
			submit_new.val('Save as new version');
			
			var submit_update = submit_new.clone();
			submit_update.val('Update this version');
			submit_new.after(submit_update);
			
			submit_new.bind('click', function() { update_checkbox.attr('checked', true); });
			submit_update.bind('click', function() { update_checkbox.attr('checked', false); });
			
			if (update_checkbox.attr('disabled') == true) {
				submit_update.attr('disabled', 'disabled');
			}
			
		}
		
	};
	
	jQuery(document).ready(function() {
		if (jQuery('.field-entry_versions').length) EntryVersions.init();
	});