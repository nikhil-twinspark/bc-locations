jQuery(document).ready(function($) {
    var taxonomy = 'status',
        post_id = null,
        term_id = null,
        li_ele_id = null;
    $('a.editinline').on('click', function() {
        post_id = inlineEditPost.getId(this);
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'wpse_139269_inline_edit_radio_checked_hack',
                'ajax-taxonomy': taxonomy,
                'ajax-post-id': post_id
            },
            type: 'POST',
            dataType: 'json',
            success: function (response) {
                term_id = response;
                li_ele_id = 'in-' + taxonomy + '-' + term_id;
                $( 'input[id="'+li_ele_id+'"]' ).attr( 'checked', 'checked' );
            }
        });
    });

});