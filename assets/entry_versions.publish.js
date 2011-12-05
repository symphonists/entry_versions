/*-----------------------------------------------------------------------------
Language strings
-----------------------------------------------------------------------------*/	 

Symphony.Language.add({
	'Show all versions': false,
	'Show recent versions': false,
	'Create new version': false
}); 


/*-----------------------------------------------------------------------------
Entry Versions
-----------------------------------------------------------------------------*/

var EntryVersions = {
	
	limit: 5,
	
	init: function() {
		var self = this;
		
		var field = jQuery('.field-entry_versions div');
		var revisions = field.find('ol');
		
		// how many versions
		var total_revisions = revisions.find('li').length;
		// which version is being viewed now
		var selected_revision = revisions.find('li.viewing');
		var selected_revision_index = selected_revision.index();
		// how many versions to show in the list. If viewing a specific entry
		// and it's so old it's buried by the recent limit, show up to this version
		var show_until = ((selected_revision_index >= this.limit) ? selected_revision_index + 1 : this.limit);
		revisions.find('li:gt(' + (show_until - 1) + ')').addClass('limit');
		
		// show the "Show all" if list has been truncated
		if (show_until < total_revisions) {
			var more = jQuery('<a href="#" class="more">' + Symphony.Language.get('Show all versions') + '</a>');

			more.bind('click', function(e) {
				e.preventDefault();
				var limited = revisions.find('li.limit');
				revisions.find('li.limit').toggle();
				jQuery(this).remove();
			});
			
			field.append(more);
		}
		
		// currently-viewed entry in list is not clickable
		selected_revision.find('a').bind('click', function(e) {
			e.preventDefault();
		});
		
		var submit_new = jQuery('.actions > input[name="action[save]"]');
		var submit_update = jQuery('<label id="entry-versions-actions"><input type="checkbox" name="fields[entry-versions]" checked="checked" value="yes" />' + Symphony.Language.get('Create new version') + '</label>');
		submit_new.after(submit_update);
		
		// don't give the option of major/minor version if creating a new entry
		// or viewing an older version. Always force a new version.
		// Show the "create new" checkbox as evidence for this enforcement, but disable it
		// so have to write a hidden copy to the DOM to ensure "yes" is POSTed
		if(Symphony.Context.get('env').entry_id == null || Symphony.Context.get('entry_versions').version || field.find('.has-no-version').length) {
			var submit_update_clone = submit_update.clone().hide();
			submit_update
				.addClass('inactive')
				.find('input').attr('disabled','disabled').end()
				.after(submit_update_clone)
		} 
		
	}
	
};

jQuery(document).ready(function() {
	if (jQuery('.field-entry_versions').length) EntryVersions.init();
});