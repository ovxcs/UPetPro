

__glob_place_a_product_if_reloaded__ = false;
function show_place_a_product_if(flag){
    var el = document.getElementById('register_product_win');
    if (flag === false){
        el.style.display = 'none';
    }else{
        if (!__glob_place_a_product_if_reloaded__){
            var doc = document.getElementById('place_a_product_if').contentDocument;
            doc.location.reload(true);
            parent.fill_with_lang_words(parent.__glob_all_dicts__[parent.glob_sel_lang], doc);
            __glob_place_a_product_if_reloaded__ = true;
        }
        el.style.display = 'block';
    }
}