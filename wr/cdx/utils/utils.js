function coderoot(){
    return '/' + window.location.href.split('://')[1].split('/')[1];
}

var ___hidden_div_used_for_calc___;
function cssinches(){
    if (!___hidden_div_used_for_calc___){
        document.body.innerHTML += '<div id="dpi" style="height: 1in; width: 1in; left: 100%; position: fixed; top: 100%;"></div>';
        ___hidden_div_used_for_calc___ = document.getElementById('dpi');
    }
    var dpi_x = ___hidden_div_used_for_calc___.offsetWidth;
    var dpi_y = ___hidden_div_used_for_calc___.offsetHeight;
    /*
    console.log(___hidden_div_used_for_calc___.getBoundingClientRect());
    console.log(dpi_x, dpi_y);
    console.log(window.screen.width, window.screen.height);
    console.log(window.devicePixelRatio);
    */
    var w = window.screen.width/dpi_x;
    var h = window.screen.height/dpi_y;
    return {w:Math.round(w * 100)/100, h:Math.round(h*100)/100};
}

//===================================== TEXT ==============================================

var FETCH_CACHE = 'no-cache';
var fetch__myHeaders;
if (typeof Headers !== 'undefined'){
    fetch__myHeaders = new Headers();
    fetch__myHeaders.append('pragma', FETCH_CACHE);
    fetch__myHeaders.append('cache-control', FETCH_CACHE);
}else{
    fetch__myHeaders = [];
}

var fetch__myInit = {
    method: 'GET',
    headers: fetch__myHeaders,
    cache: FETCH_CACHE
};

function fetch_as_text(url) {
    //console.log("fetch_as_text:", url);
    return fetch(url, fetch__myInit)
    .then(function(result){
        if (result.ok){ //test this because the fetch will not fail on 404
            var txt = result.text();
            return txt;
        }
        else{
            var msg = 'Request rejected with status ' + result.status + ' for "' + url + '"';
            return '<div style="color:#CC3300;background:#DDEE00;padding:3px;margin:1px;border:1.3px solid #CC3300;'
                + 'font-weight:bold;font-family:\"Courier New\";font-size:14px;">' + msg + '</div>';
            //throw Error(msg);
        }
    }, function(reason){
        console.log("error reason:", reason);
    });
}

function load_component_page(page, holder, callback) {
    var __holder = (typeof holder === 'string') ? document.getElementById(holder) : holder;
    console.log("load_component_page() src=", page);
    if (__holder !== null){
        var inner = __holder.innerHTML.trim();
        if (inner && !inner.startsWith('<!--')){
            console.log('INFO:  HTML component: ', holder.getAttribute('data-src'), ' seems ALREADY LOADED ',
                " with ", inner.length, "B");
            if (callback) callback();
            return;
        }
    }
    fetch_as_text(page)
    .then(function(value){
        //console.log("fetch as text value:", value);
        var remote_scripts = [];
        var aelh_id = 0;
        var original_display_status;
        if (__holder !== null){
            original_display_status = window.getComputedStyle(__holder, null)
                    .getPropertyValue('display');
            __holder.style.display = 'none';
            aelh_id = 'k' + (Math.floor(Math.random() * 999) + 1);
            __holder.innerHTML = value;
            __holder.setAttribute('data-fetched','true'); //very important to be here
            var to_add_to_head = [];
            [].forEach.call(__holder.getElementsByTagName('meta'), function(e, i){
                //move them to header or not ?
                //BUT SURELY preserve the owner somehow
                //console.log("meta", e, "for", page);
                e.setAttribute("data-ownerPage", page);
                to_add_to_head.push(e);
                //if (_head) _head.appendChild(e); //very wrong to add here as it changes content and for failes
            });
            document.head && to_add_to_head.forEach( function(e){ document.head.appendChild(e)} );
            [].forEach.call(__holder.getElementsByTagName('script'), function(e, i){
                var ne_script = e;
                if (ne_script.src){
                    //script elements with 'src' (remote scripts) will be loaded via xhr - see load scripts chain
                    var s = ne_script.src;
                    //adjust src if not starts with '/';
                    if (s[0] === '/' || s.startsWith('http')){
                        //pass
                    }
                    else{
                        var p = page.split('/').slice(0, -1).join('/');
                        s = p + '/' + s;
                    }
                    remote_scripts.push([s, ne_script]);
                }
                else{ //script has content - hook window.onload and evaluate
                    var ex_script = document.createElement('script');
                    ex_script.text = replace_addEventListener(ne_script.innerHTML, aelh_id);
                    ne_script.parentNode.replaceChild(ex_script, ne_script);
                }
            });
        }
        load_scripts_chain(remote_scripts)
        .then(function (arg){
            if (aelh_id) addEventListener_execute_handlers(aelh_id);
            if (__holder !== null){
                //it is very important to be here since overlaps may occur;
                __holder.style.display = original_display_status;
            }
            if (callback) callback();
        });
    }, function(reason){
        console.log("LCP error reason:", reason);
    });
}

var __loaded_scripts__;
function load_scripts_chain(chain){ //.css should be included, otherwise the component will be shown ugly before .css load 
    var promises = Array();
    [].forEach.call(chain, function(obj, i){
        if (!__loaded_scripts__){
            __loaded_scripts__ = [];
        }
        //console.trace();
        var path = obj[0];
        if (__loaded_scripts__.includes(path)){
            //return;
            promises.push(Promise.resolve('Already loaded or pending'));
            return;
        }
        __loaded_scripts__.push(path);
        var holder = obj[1];
        console.log("SCRIPT TO LOAD:", path);
        var prom = fetch_as_text(path)
        .then(function(value){
            var aelh_id = 'k' + (Math.floor(Math.random() * 999) + 1);
            var text_with_hooks = replace_addEventListener(value, aelh_id);
            var ex_script = document.createElement('script');
            ex_script.text = text_with_hooks;
            if (holder && holder.parentNode)
                holder.parentNode.replaceChild(ex_script, holder);
            else document.body.appendChild(ex_script);
            addEventListener_execute_handlers(aelh_id);
        }, function(reason){
            console.log("load script error:", reason);
        });
        promises.push(prom);
    });
    //console.log("==================================");
    //console.log(promises);
    //console.log("==================================");
    return Promise.all(promises);
}

//----------------------------------------------------------------------------------------

function replace_addEventListener(txt, hook_id){
    return txt.replace(/window\.addEventListener\s*\(\s*\'load\'/g ,
                        "addEventListener_hook_20180129('load', '" + hook_id + "'");
}

var ___ael_handlers___;
function addEventListener_hook_20180129(evt, key, handler){
    //console.log("addEventListener_hook CALLED for key", key);
    window.addEventListener(evt, handler);
    if (!___ael_handlers___) ___ael_handlers___ = [];
    if (!___ael_handlers___.hasOwnProperty(key))
        ___ael_handlers___[key] = [];
    ___ael_handlers___[key].push(handler);
}

function addEventListener_execute_handlers(key){
    if (typeof ___ael_handlers___ === 'undefined' || !___ael_handlers___.hasOwnProperty(key)) return;
    ___ael_handlers___[key].forEach(function(eh, i){
        console.log("calling evt handler ", i);
        eh();
    });
}

//----------------------------------------------------------------------------------------

//var __pages__;
function load_component_page_and_translate(page, holder, lang, callback){
    //var stack = new Error().stack;
    //dictionaries are found in the page meta
    console.log("LCP&T:", page);
    var holder_el = (typeof holder === 'string') ? document.getElementById(holder):
                      (typeof holder === 'function') ? holder() : holder;
    
    if (holder_el.hasAttribute('data-__loading_content_flag')){
        return;
    }
    holder_el.setAttribute('data-__loading_content_flag', 'true');
    var styDi = getComputedStyle(holder_el).getPropertyValue("display");
    //holder_el.setAttribute('data-originalStyleDisplay', styDi);
    holder_el.style.display = "none";
    load_component_page(page, holder_el, function(value){
        //console.log("page loaded, translate now:", page, lang);
        translate_content_using_meta_info(holder_el, lang, function(){
            //holder_el.style.display = holder_el.getAttribute('data-originalStyleDisplay');
            holder_el.style.display = styDi;
            holder_el.removeAttribute('data-__loading_content_flag');
            //if (!callback){ console.log(stack); }
            callback();
        });
    });
}

function load_component_page_and_translate_with_promise(page, holder, lang){
    return new Promise ( function(resolve, reject){
        load_component_page_and_translate(page, holder, lang, resolve);
    });
}

/*
function load_pages_chain(chain, lang, callback, index){
    if (!index) index = 0;
    if (index >= chain.length){ callback(); return; };
    load_component_page_and_translate(chain[index][0], chain[index][1], lang, function(){
        load_pages_chain(chain, lang, callback, index + 1);
    });
}*/

function load_pages_chain(chain, lang, callback){
    var promises = Array();
    chain.forEach(function (e,i){
        promises.push(
            load_component_page_and_translate_with_promise(chain[i][0], chain[i][1], lang)
        );
    });
    Promise.all(promises).then(callback);
}

function populate_components_holders(doc){
    if (typeof doc === 'undefined') doc = document;
    else if (typeof doc === 'string') doc = document.querySelector(doc);
    else if (doc.target) doc = doc.target;
    var chain = [];
    [].forEach.call(doc.querySelectorAll('.component_holder'), function (el, i){
        var src = el.getAttribute('data-src');
        if (!src) return;
        var fetched = el.getAttribute('data-fetched');
        if (fetched === 'true'){
            console.log('seems fetched', el);
            return;
        }
        chain.push([src, el]);
    });
    load_pages_chain(chain, 0, function(){
        console.log('components holders populated');
    });
}
window.addEventListener('load', populate_components_holders);

//===================================== JSON ==============================================
function fetch_post_json(page, json) {
    return fetch(page, {
        method: 'POST',
        headers: {
            //'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(json)
    })
    .then(function(r){return r.json()})
}


function post_json(page, json, callback){
    fetch_post_json(page, json)
    .then(function(recv_json){callback(recv_json)})
    .catch(function(err){console.log("ERROR: post_json call failed:", err)});
}

//=============================================================================================================================

///ERRORS
function show_xhr_dbg_msg(msg, color, background){
    var color = color || "red";
    var background = background || "yellow";
    var el = document.getElementById("xhr_dbg_msgs");
    if (!el){
        el = document.createElement("div");
        document.body.insertBefore(el, document.body.firstChild);
        el.style.textAlign = "center";
        el.id = "xhr_dbg_msgs";
    }
    el.style.display = "block";
    el.innerHTML += "<br/> <div style='color:" + color + ";background:" + background + "'>" + msg + "</div>";
}