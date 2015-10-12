(function ( $ ) {

	$(function () {

		var _custom_media = true,
      _orig_send_attachment = wp.media.editor.send.attachment;

	  $('#map_icon').click(function(e) {
	    var send_attachment_bkp = wp.media.editor.send.attachment;
	    var button = $(this);
	    _custom_media = true;
	    wp.media.editor.send.attachment = function(props, attachment){
	      if ( _custom_media ) {
	    	console.log(attachment.url);
	        $('#map_icon img').attr('src', attachment.url);
	        $('#icon_url').val(attachment.url); 
	      } else {
	        return _orig_send_attachment.apply( this, [props, attachment] );
	      };
	    }

	    wp.media.editor.open(button);
	    return false;
	  });


	});

}(jQuery));