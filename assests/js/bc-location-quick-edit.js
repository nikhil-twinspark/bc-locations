jQuery(document).ready(function() {
    jQuery('.editinline').live('click', function() {
        var id = inlineEditPost.getId(this);
        var get_category = jQuery("tr#post-" + id).find('td.column-category')[0].innerText;
        jQuery('.cat-checklist.bc_location_category-checklist').find('label').each(function() {
            if (this.innerText == (' '+get_category)) {
                jQuery(this).find('input').attr("checked","checked");
            }
        });
    });
});
