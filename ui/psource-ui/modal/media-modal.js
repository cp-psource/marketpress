jQuery(document).ready(function($){
    $('[data-psource-media-button]').each(function(){
        var $btn = $(this);
        var $input = $('#' + $btn.data('psource-media-target'));
        var mediaUploader;
        $btn.on('click', function(e){
            e.preventDefault();
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            mediaUploader = wp.media({
                title: 'Bild auswählen',
                button: { text: 'Bild übernehmen' },
                multiple: false
            });
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $input.val(attachment.url);
            });
            mediaUploader.open();
        });
    });
});