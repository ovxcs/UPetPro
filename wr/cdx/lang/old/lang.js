CONTENT_IDS = {}; //fill this in other JS sources; CONTENT_IDS['test'] = {'ro':{...},} 

function create_lang_sel(){
    var sel_code = '<span style="position:fixed;left:0;top:0;z-index:200;width:100px;overflow:hidden">'
    +'<select id="lang_sel" onchange="select_language()" style="font-size:15px; '
        +'background:rgba(255,255,255,0.5) left center no-repeat;'
        +'border:none;'
        +'appearence:none;'
        +'width:120px;padding:2px 2px 2px 15px;display:block">'
    +'<option value="de" style="font-size:18px">Deutsch</option>'
    +'<option value="en" style="font-size:18px">English</option>'
    +'<option value="hu" style="font-size:18px">magyar</option>'
    +'<option value="ro" style="font-size:18px">română</option>'
    +'</select>'
    +'</span>';
    document.body.insertAdjacentHTML( 'afterbegin', sel_code);
    var userLang = localStorage.getItem('sel_lang');
    if (!userLang){
        userLang = navigator.language || navigator.userLanguage;
        userLang = userLang.substr(0, 2);
    }
    document.getElementById('lang_sel').value = userLang;
    glob_sel_lang = userLang;
    merge_all_dicts();
    fill_with_lang_words(__glob_all_dicts__[glob_sel_lang]);
}

function extend(obj, src) {
    for (var key in src) {
        if (src.hasOwnProperty(key)) obj[key] = src[key];
    }
}

function extend_rec(obj, src){
    for (var key in src) {
        if (src.hasOwnProperty(key)){
            var t = typeof src[key];
            if (t === 'string')
                obj[key] = src[key];
            else if (t === 'object'){
                if (!obj[key]) obj[key] = {};
                extend_rec(obj[key], src[key]);
            }
        }
    }
}

function merge_all_dicts(){
    __glob_all_dicts__ = {};
    console.log(CONTENT_IDS);
    for (var dct in CONTENT_IDS){
        if (CONTENT_IDS.hasOwnProperty(dct)){
            extend_rec(__glob_all_dicts__, CONTENT_IDS[dct]);
        }
    }
}

function select_language(){
    var sel_lang = document.getElementById('lang_sel').value;
    localStorage.setItem('sel_lang', sel_lang);
    glob_sel_lang = sel_lang;
    merge_all_dicts();
    fill_with_lang_words(__glob_all_dicts__[glob_sel_lang]);
}

function fill_with_lang_words(lang_dict, doc){
    if (lang_dict === undefined){
        lang_dict = __glob_all_dicts__[glob_sel_lang];
    } 
    if (lang_dict === undefined){
        console.log('translation error! undefined dict for', glob_sel_lang);
        return;
    }
    var counter = 0;
    if (!doc) doc = document;
    var els = doc.getElementsByTagName('*');
    for (var i = 0; i < els.length; i++){
        var el = els[i];
        if (el.tagName === 'IFRAME'){
            var innerDoc = el.contentDocument || el.contentWindow.document;
            fill_with_lang_words(lang_dict, innerDoc);
        }
        var cid = el.getAttribute('data-ci');
        if (!cid) continue;
        counter++; 
        var w = lang_dict[cid];
        if (w === undefined){
            console.log('translation error! a match for', cid, 'not found in ', glob_sel_lang);
        }else{
            console.log(el.tagName);
            if(el.tagName === 'INPUT'){
                el.placeholder = w;
            }else{
                el.innerHTML = w;
            }
        }
    }
    console.log("translated elements:", counter);
}


if (window.addEventListener){
  window.addEventListener('load', create_lang_sel)
}else{
  window.attachEvent('onload', create_lang_sel)
}