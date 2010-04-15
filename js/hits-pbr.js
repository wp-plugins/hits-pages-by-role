// JavaScript Document
jQuery(function(){
		   jQuery("#hits_pbr_add_item_button").click(add);
		   jQuery("a.pbrDelete").click(remove);
});

function add()
{
	var hits_pbr_page_ID = jQuery("#hits_pbr_page_ID").val();
	var hits_pbr_page_MinAccess = jQuery("#hits_pbr_page_MinAccess").val();
	var hits_pbr_page_OverrideText = jQuery("#hits_pbr_page_OverrideText").val();
	
	var dataString = "hits_pbr_page_ID=" + hits_pbr_page_ID + 
					"&hits_pbr_page_MinAccess=" + hits_pbr_page_MinAccess + 
					"&hits_pbr_page_OverrideText=" + hits_pbr_page_OverrideText;
					
	var data = {
		action: 'hits_pbr_add_record',
		page_id: hits_pbr_page_ID,
		minAccess: hits_pbr_page_MinAccess,
		overrideText:hits_pbr_page_OverrideText		
	};
	jQuery.post(ajaxurl,data,successfulAdd);
	return false;
}

function remove(e)
{
	e.preventDefault();
	var parent=jQuery(this).parent().parent();
	hits_pbr_page_ID= parent.attr('id').replace('record-','');
	
	var data = {
		action: 'hits_pbr_remove_record',
		page_id: hits_pbr_page_ID
	}
	jQuery.post(ajaxurl,data,successfulRemove);
}

function successfulAdd(html)
{
	jQuery(html).insertBefore("#newRecordRow");
}

function successfulRemove(html)
{
	var recordId = '#record-'+html;
	var record=jQuery(recordId);
	record.slideUp(300,function(){record.remove();});
}