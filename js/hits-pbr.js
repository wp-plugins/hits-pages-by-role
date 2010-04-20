// JavaScript Document
jQuery(function(){
		   jQuery("#hits_pbr_add_item_button").click(add);
		   jQuery("a.pbrDelete").click(remove);
		   jQuery("a.pbrMoveUp").click(moveUp);
		   jQuery("a.pbrMoveDown").click(moveDown);
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

function successfulAdd(html)
{
	jQuery(html).insertBefore("#newRecordRow");
	jQuery("#pageList:last-child a.pbrDelete").click(remove);
	jQuery("#pageList:last-child a.pbrMoveUp").click(moveUp);
	jQuery("#pageList:last-child a.pbrMoveDown").click(moveDown);
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

function successfulRemove(html)
{
	var recordId = '#record-'+html;
	var record=jQuery(recordId);
	record.slideUp(300,function(){record.remove();});
}

function moveUp(e)
{
	e.preventDefault();
	var parent=jQuery(this).parent().parent();
	hits_pbr_page_ID= parent.attr('id').replace('record-','');
	
	var data = {
		action: 'hits_pbr_moveUp_record',
		page_id: hits_pbr_page_ID
	}
	jQuery.post(ajaxurl,data,successfulMoveUp);
	
}

function successfulMoveUp(html)
{
	var pages=html.split(",");
	if(pages.length==2)
	{
		var recordId = '#record-'+ pages[0];
		var targetRecord = '#record-'+ pages[1];
		jQuery(recordId).insertBefore(targetRecord);
	}
}

function moveDown(e)
{
	e.preventDefault();
	var parent=jQuery(this).parent().parent();
	hits_pbr_page_ID= parent.attr('id').replace('record-','');
	
	var data = {
		action: 'hits_pbr_moveDown_record',
		page_id: hits_pbr_page_ID
	}
	jQuery.post(ajaxurl,data,successfulMoveDown);
	
}

function successfulMoveDown(html)
{
	var pages=html.split(",");
	if(pages.length==2)
	{
		var recordId = '#record-'+ pages[0];
		var targetRecord = '#record-'+ pages[1];
		jQuery(recordId).insertAfter(targetRecord);
	}
}