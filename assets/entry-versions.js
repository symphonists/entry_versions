/*-----------------------------------------------------------------------------
	Language strings
-----------------------------------------------------------------------------*/	 

	Symphony.Language.add({
		'Show all versions': false,
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
			
		}
		
	};
	
	jQuery(document).ready(function() {
		EntryVersions.init();
	});