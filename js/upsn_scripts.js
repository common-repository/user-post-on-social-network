jQuery(function(){
	jQuery("#frmupsn").validate();

	jQuery("#upsn_source").change(function(){
	    if( this.files[0].size > allowedFileSize )
	    {
	    	alert("exceeds the maximum upload size for this site.");
	    }
	});
});