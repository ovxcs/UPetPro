var HOSTNAME = window.location.hostname;

function MultiDict(){
    var self = this;
    this.map = {};
    var __jsons__ = {};
    var __paths = {};

    this.load_json = function(json_path, callback){
        if (__jsons__.hasOwnProperty(json_path)){
            callback(__jsons__[json_path]);
            return;
        }
        //console.log("not in cache, downloading ...", json_path);
    fetch(json_path, {cache: "reload"})
        .then(function(r){
            if (r.ok){
                return r.text();
            }else{
                throw('Request for json rejected with status: ' + r.status);
            }
        })
        .then(function(jt){
            var dict = JSON.parse(jt);
            __jsons__[json_path] = dict;
            /*load fallback*/
            if (dict.hasOwnProperty(':fallback')){
                var fallback_json_path = json_path.match(/.*\//) + dict[':fallback'] + '.json';
                self.load_json(fallback_json_path,
                    function(fbd){
                        //fbd - fallback dict
                        //dict = fbd.update(dict); //!!!
                        Object.keys(fbd).forEach(function(k){
                            if (!dict.hasOwnProperty(k)){
                                dict[k] = fbd[k];
                            }
                        });
                        callback(dict);
                    }
                );
            }else{
                callback(dict);
            }
        })
        .catch(function(e){
            console.log('ERROR: loading', json_path, 'failed:', e, ' - passing an empty dict to callback');
            callback({});
        });
    }

    this.load_chain = function(lang, chain, callback, index){
        if (!index) index = 0;
        if (index >= chain.length){
            //console.log(self.map);
            callback();
            return;
        }
        var path_to_json = chain[index] + '/' + lang + '.json';
        self.load_lang_from_json(path_to_json, lang, function(){
            self.load_chain(lang, chain, callback, index+1);
        });
    }

    this.load_all_jsons_of_language_from_meta = function(lang, callback){
        var paths = [];
        //grab paths from meta
        [].forEach.call(document.getElementsByTagName("meta"), function(e, i){
            if (!(e.getAttribute('class') === 'dictionaries')) return;
            var owner = e.getAttribute('data-ownerPage');
            console.log("grab meta for lang '", lang, "'; meta owner:", owner);
            if (!owner) owner = window.location.href;
            owner = owner.split('/').slice(0, -1).join('/');
            //console.log("META OWNER:", owner);
            e.getAttribute('content').split(',').forEach(function (x,j){
                var p = x.trim();
                if (!p) return;
                if (p[0] === '/') paths.push(p);
                else paths.push(owner + '/' + p);
            });
        });
        self.load_chain(lang, paths, callback);
    }

    this.load_lang_from_json = function(path_to_json, lang, callback){
        var parent_path = path_to_json.split('/').slice(0, -1).join('/');
        if (!__paths.hasOwnProperty(parent_path)) __paths[parent_path] = 0;
        if (!self.map.hasOwnProperty(lang)) self.map[lang] = {};
        //console.log("load_lang_from_json:", path_to_json, lang);
        self.load_json(path_to_json, function(dict){
            for (var dk in dict){
                if (!dict.hasOwnProperty(dk)) continue;
                self.map[lang][dk] = dict[dk];
            }
            callback();
        });
    }

    this.load_lang_of_module = function(module_path, lang){
    //this.load_lang_of_module = function(module_path, lang, cb){
        //return self.load_lang_from_json(module_path + '/lang/' + lang + '.json', lang, cb);
        return self.load_lang_from_json(module_path + '/lang/' + lang + '.json', lang);
    }

    this.load_another_language = function(lang, callback){
        //RELY ON PPREVIOUS LOADED PATHS
        //console.log("LOADING LANGUAGE:", lang);
        var total_parts = Object.keys(__paths).length;
        if (!total_parts){
            console.log('error: no language translation dicts parts found');
            callback();
            return;
        };
        if (!self.map.hasOwnProperty(lang))
            self.map[lang] = {};
        var loaded_parts = 0;
        var chain = [];
        for (var lk in __paths){
            if (!__paths.hasOwnProperty(lk)) continue;
            chain.push(lk);
        }
        self.load_chain(lang, chain, callback);
    }
}

var __multidict_inst;
function MultiDictInst(){
    if (!__multidict_inst) __multidict_inst = new MultiDict();
    return __multidict_inst;
}

function recursive_get_property(obj, chain, index){
    if (!index) index = 0;
    if (index >= chain.length || typeof obj != 'object') return obj;
    return recursive_get_property(obj[chain[index]], chain, index+1);
}

var lang_regex_var = /\{[a-zA-Z0-9_.]+\}/g;

function fill_with_lang_words(d, lang, callback){
    var __lang = lang || language();
    var dict = MultiDictInst().map[__lang];
    if (!dict){
        console.log("ERROR: NO DICT FOUND FOR LANG.:", __lang, " - TRANSLATION ABORTED");
        callback();
        return;
    }
    var counter = 0, failed = 0;
    if (!d) {d = document; d.tagName="DOC_TAG"; d.id = 'DOC_ID'}
    //console.log("filling with strings:", d.tagName, d.id);
    var els = d.getElementsByTagName('*');
    
    for (var i = 0; i < els.length; i++){
        var el = els[i];
        if (el.tagName === 'IFRAME'){
            var innerDoc = el.contentDocument || el.contentWindow.document;
            fill_with_lang_words(innerDoc, lang, function(){console.log("iframe translated");});
        }
        var cts = el.getAttribute('data-cts');
        if (cts){
            var t = translate_dt(cts, __lang);
            if(el.tagName === 'INPUT'){
                el.placeholder = t[0];
            }else{
                el.innerHTML = t[0];
            }
            continue;
        }
        var cid_kind = 0;
        var cid = el.getAttribute('data-ci');
        if (!cid){
            cid_kind = 1;
            cid = el.id;
            if (!cid) continue;
        }
        var w = dict[cid];
        if (w === undefined){
            if (cid_kind === 0){
                //console.log('Translation ERROR: A match for', cid, 'not found in ', __lang);
                if (el.getAttribute('data-tl') !== __lang){
                    w = '[' + cid + ']'; //!!! CID will be DISPLAYED IF TRANSLATION FAILS !!!;
                    failed++;
                }
            }
        }
        if (w){
            counter++;
            if(w[0] === ':'){
                if (w === ":empty"){
                    //el.style.display = "none"; continue;//!!! not good
                    w = "";
                }
                else if(w.startsWith(":{}")){
                    w = w.substr(3);
                    w.match(lang_regex_var).forEach(function (e,i){
                        //var v = window[e.substr(1,e.length-2)];
                        var v = recursive_get_property(window, e.substr(1, e.length-2).split('.'));
                        if (v !== undefined)
                            w = w.replace(e, v);
                    });
                    //console.log(w);
                }
            }
            if(el.tagName === 'INPUT'){
                el.placeholder = w;
            }else{
                el.innerHTML = w;
            }
            el.setAttribute('data-tl', __lang);
        }
    }
    console.log("translatable:", counter, ", failed:", failed);
    callback();
}

function translate(doc, lang, dicts, callback){
    if (!MultiDictInst().map.hasOwnProperty(lang)){
        MultiDictInst().load_another_language(lang,
            function(){
                fill_with_lang_words(doc, lang, callback);
            }
        );
    }else{
        fill_with_lang_words(doc, lang, callback);
    }
}

//var __url_params_obj = undefined;
function url_params_obj(){
    //if (__url_params_obj === undefined){
    var __url_params_obj = {};
    var wl = window.location;
    var q = wl.search.split('?')[1];
    if (q){
        q.split('&').forEach(function (e, i){
            var p = e.split('=');
            __url_params_obj[p[0]] = p[1];
        });
    }
    //}
    return __url_params_obj;
}

function language(lang){
    if (lang){
        if(typeof lang !== 'string'){
            throw "Type error: " + lang + " is " + typeof lang;
        }else{
            if (lang.length > 6){
                throw "Type error: " + lang + " is > 6";
            }
            
        }
    }
    var ls = document.getElementById('lang_sel');
    var pl = url_params_obj()['lang'];
    //var pl = (new URL(window.location)).searchParams.get('lang');
    var l = (lang || (ls ? ls.value : 0) || localStorage.getItem(
                'sel_lang') || pl  || 'en');
    if (l.length > 6) l = 'en';
    localStorage.setItem('sel_lang', l);
    return l;
}

function translate_content(dc, lang, dicts, callback){
    lang = language(lang);
    var prog = document.getElementById('lang_translation_in_progress');
    if (prog) prog.style.display = 'block';
    translate(dc, lang, dicts,
        function(){
            if (prog) prog.style.display = 'none';
            callback();
    });
}

function translate_content_using_meta_info(dc, lang, callback){
//function translate_content_using_meta_info(dc, lang, callback){
    lang = language(lang);
    MultiDictInst().load_all_jsons_of_language_from_meta(lang, function(){
        translate_content(dc, lang, 0, callback);
    });
}

function translate_everything(lang, callback){
    localStorage.setItem('sel_lang', lang);
    translate_content_using_meta_info(0, lang, callback);
}

function translate_text(txt, lang, replaces){//!NOT async - translate from already loaded dicts
    lang = lang || language(lang);
    var dict = MultiDictInst().map[lang];
    if (!dict){
        console.log('Translation error! dictionary not found for', lang);
        return txt;
    }
    var w = dict[txt];
    if (w && w.length && w[0]){
        if(w.startsWith(":{}")){
            w = w.substr(3);
            w.match(lang_regex_var).forEach(function (e, i){
                //var v = window[e.substr(1,e.length-2)];
                var v = recursive_get_property(window, e.substr(1, e.length-2).split('.'));
                if (v !== undefined)
                    w = w.replace(e, v);
            });
        }
    }
    if (w === undefined){
        console.log('Translation error! a match for', txt, 'not found in ', lang);
        return txt;
    }
    return w;
}

function language_selector__fetch_component(show_lang_sel){
    return fetch('/cdx/lang/lang.html')
    .then(function(r){return(r.text())})
    .then(function(t){
        if(show_lang_sel) insert(t);
    })
    .catch(function(e){
        console.log('ERROR: loading language selector failed:', e)
    });
    function insert(code){
        document.body.insertAdjacentHTML('afterbegin', code);
        var userLang = localStorage.getItem('sel_lang');
        if (!userLang){
            userLang = navigator.language || navigator.userLanguage;
            userLang = userLang.substr(0, 2);
        }
        document.getElementById('lang_sel').value = userLang;
    }
}

function language_selector__on_selection(){
    var sel_lang = document.getElementById('lang_sel').value;
    translate_everything(sel_lang, function(){});
}

function LanguagesTable(languages, per_row){
    if (!per_row) per_row = 1;
    var count = languages.length;
    var rows_count = Math.ceil(count/per_row);
    var table = document.createElement("TABLE");
    table.id = "LangsTable";
    for(var r = 0; r < rows_count; r++){
        var row = table.insertRow(-1);
        for (var c = 0; c < per_row; c++){
            var cell = row.insertCell(-1);
            var i = r * per_row + c;
            if (i >= count) break;
            var d = languages[i];
            cell.innerHTML = d[0]+'&nbsp;('+d[1]+')';
        }
    }
    return table;
}


window.addEventListener('load', function() {
        translate_everything(language(),function(){})
    });




