/*
var scripts = document.getElementsByTagName("script"),
    __auth_js_path__ = scripts[scripts.length-1].src,
    __auth_dir_path__ = __auth_js_path__.match(/.*\//);
*/
var __auth_html_components__ = '/cdx/auth/components2.html';
var __auth_php_url__  = '/auth/auth.php';
var __oauth_php_url__ = '/auth/oa/login.php';

//console.log("AUTH PATH:", __auth_dir_path__ + '');

AUTH_SERV_JSON_RESP = {};

function auth__init(div){
    auth__translate(div, 0, ['auth'], function(){
        //console.log("translated");
        auth__add_listeners_to_inputs();
        var is_pwr = auth__check_and_display_pwr_if_needed();
        if (is_pwr) return;
        try{
            auth__button_enable();
        }catch(e){
            console.log(e);
        }
    });
}

function auth__load_components(page){
    var page = typeof page === 'string' ? page : __auth_html_components__;
    if (document.getElementById('auth_win')){
        console.log("auth components already downloaded");
        auth__init();
        return;
    }
    var page_dir = page.split('/').slice(0, -1).join('/');
    var xhr = new XMLHttpRequest();
    xhr.open('GET', page);
    xhr.onload = function (e) {
        if (xhr.readyState === 4){
            if(xhr.status === 200){
                var div = document.createElement('div');
                div.style.display = "none";
                div.innerHTML = xhr.responseText;
                document.getElementsByTagName('body')[0].appendChild(div);
                [].forEach.call(div.getElementsByTagName('meta'), function (e, i){
                    e.setAttribute('data-ownerPage', page);
                });
                auth__init();
                div.style.display = "block";
            } else{ console.error("XHR ERROR: status:", xhr.status, xhr.statusText); }
        } else{ console.error("XHR ERROR: state:", xhr.readyState, xhr.statusText); }
    }
    xhr.send();
}

function auth__translate(obj, lang, mix, callback){
    if (typeof(obj) === 'string'){
        return translate_text(obj, lang, mix) || obj; //not async
    }else{
        translate_content_using_meta_info(obj, lang, callback); //async
        //translate_content(obj, lang, mix, callback);
    }
    
}
__auth_display_mode = 'v';
function auth__display_mode(d){//'v','h','t'
    if (d == 't') return auth__display_mode(__auth_display_mode === 'v' ? 'h' : 'v');
    /*felxDir. column NOT WORK WITH IE*/
    var cs = window.getComputedStyle(document.documentElement).getPropertyValue('--auth-oauth-first');
    cs = cs ? 'column-reverse' : 'column';
    cs = '';//NOT WORK WITH IE;
    //document.querySelector('#auth_components').style.display = (d == 'h' ? 'flex' : 'block');
    //document.querySelector('#auth_components').style.flexDirection = (d == 'h' ? 'row' : cs);
    document.querySelector('#auth_h_sep').style.display = (d == 'h' ? 'none' : 'block');
    //document.querySelector('#auth_win_inner').style.maxWidth = (d == 'h' ? '500px' : '400px');
    /*
        var ois = document.querySelector('#oauth_icons');
        ois.style.display = (d == 'h' ? 'block' : 'flex');
        ois.style.flexDirection = (d == 'h' ? 'column' : 'row');
        //ois.borderLeft = (d == 'h' ? '1px solid gray' : 'none');
        ois.style.marginLeft = (d == 'h' ? '5px' : '0px');
        ois.style.textAlign = (d == 'h' ? 'right' : 'center');
    */
    __auth_display_mode = d;
    var el = document.getElementById('auth_win_toggle');
    if (el)
        el.innerHTML = __auth_display_mode.toUpperCase();
}

function auth__add_listeners_to_inputs(){
    //return;
    var els = document.getElementsByTagName('input');
    for (var i = 0; i < els.length; i++){
        els[i].addEventListener('input', function(ev){
            var el = ev.srcElement;

            if (el.value.trim()){
                el.parentNode.setAttribute('data-inside', true);
            }else{
                el.parentNode.setAttribute('data-inside', false);
            }
        });
        els[i].addEventListener('blur', function(ev){
            ev.srcElement.parentNode.setAttribute('data-focus', false);
            var el = ev.srcElement.parentNode;
            el.parentNode.style.visibility = 'hidden';
            el.parentNode.style.visibility = 'inherit';
        });
        els[i].addEventListener('focus', function(ev){
            var elx = document.querySelector("[data-focus=true]");
            if (elx) elx.setAttribute('data-focus', false);
            var pn = ev.srcElement.parentNode;
            pn.setAttribute('data-focus', true);
            pn.parentNode.style.visibility = 'hidden';
            pn.parentNode.style.visibility = '';
        });
    }

    var body = document.querySelector('#auth__onsite');
    var tab_key_nav = tabKeyNavigatorInst();
    body.addEventListener('keydown', tab_key_nav.key_down);
    body.addEventListener('keyup', tab_key_nav.key_up);
}

function auth__check_and_display_pwr_if_needed(){
    var x = new URL(window.location);
    var code = x.searchParams.get('pwrc');
    var email = x.searchParams.get('em');
    if (!code) return;
    auth__show_inputs('pw_reset_code');
    var title = document.querySelector('#auth_win_title');
    title.querySelector('div').innerHTML = 
            '<span style="color:gray" data-ci="password_reset">'
            + auth__translate('password_reset') + "</span>";
    title.style.display = 'block';
    document.getElementById('auth_win').style.background = "gray";
    document.getElementById('show_auth_win').disabled = true;
    document.getElementById('auth_win_close_btn').style.display = "none";
    document.getElementById('auth_menu').style.display = 'none';
    document.getElementById('oauth').style.display = 'none';
    document.getElementById('auth_win').style.visibility = 'visible';
    var a = [email, code];
    ['pwr_email', 'pwr_code'].forEach(function(n, i){
        var el = document.getElementById(n);
        el.parentNode.setAttribute('data-empty', '0');
        el.value = a[i]; //el.parentNode.style.background = '#DDDDDD';
        el.contentEditable = false; 
        el.disabled = true;
    });
    return true;
} 

function auth__button_enable(){
    //document.getElementById('show_auth_win').disabled = false;
    //document.getElementById('auth_win').style.visibility = 'visible';
}

function auth__show_win(flag, kind){
    if (flag === false){
        document.getElementById('auth_win').style.visibility = 'hidden';
        return;
    }
    document.querySelector("#auth_components").style.display = "";
    document.querySelector("#auth_info").style.display = "none";
    auth__show_inputs(kind);
    document.getElementById('auth_win').style.visibility = 'visible';
}

function auth__show_inputs(el){
    var tables = ['su_table', 'li_table', 'ipwr_table', 'reset_code_table'];
    for (var i = 0; i < tables.length; i++){
        document.getElementById(tables[i]).style.display = 'none';
    }
    document.getElementById('auth_errors').innerHTML = '';
    document.getElementById('auth_info').innerHTML = '';
    var kind;
    if (typeof(el) === 'string'){
        kind = el;
        if (el === 'pw_reset_code'){
            document.getElementById('reset_code_table').style.display = 'block';
            return;
        }
    }else{
        kind = el.getAttribute('id');
    }
    /*
    document.getElementById('auth_menu').style.display = 'none';
    document.getElementById('oauth').style.display = 'none';
    */
    var _display = 'block';
    if (kind == 'auth_opt_signUp'){
        document.getElementById('auth_opt_logIn').style.display = 'block';
        document.querySelector('#auth_opt_logIn .auth_opt_question').style.display = '';
        document.getElementById('auth_opt_signUp').style.display = 'none';
        document.getElementById('auth_opt_recoveryPwInit').style.display = 'block';
        document.getElementById('su_table').style.display = _display;
    }else if (kind == 'auth_opt_recoveryPwInit'){
        document.getElementById('auth_opt_logIn').style.display = 'block';
        document.querySelector('#auth_opt_logIn .auth_opt_question').style.display = 'none';
        document.getElementById('auth_opt_signUp').style.display = 'block';
        document.getElementById('auth_opt_recoveryPwInit').style.display = 'none';
        document.getElementById('ipwr_table').style.display = _display;
    }else if (kind == 'auth_opt_logIn'){
        document.getElementById('auth_opt_logIn').style.display = 'none';
        document.getElementById('auth_opt_signUp').style.display = 'block';
        document.getElementById('auth_opt_recoveryPwInit').style.display = 'block';
        document.getElementById('li_table').style.display = _display;
    }else if (kind == 'auth_opt_activate'){
        
    }
}


function hashCode(str) {//not used
    return str.split('').reduce(function(prevHash, currVal){
        (((prevHash << 5) - prevHash) + currVal.charCodeAt(0))|0}, 0);
}

function auth__disable_submit_buttons(flag){
    [].forEach.call(document.querySelectorAll(
            '#li_table button, #su_table button, #ipwr_table button'), function(e, i){
        e.disabled = flag;
    });
}

function send_auth_request(dict, callback){
    var el_err = document.getElementById('auth_errors'); el_err.innerHTML = '&nbsp;';
    var el_info = document.getElementById('auth_info'); el_info.innerHTML = '';
    el_info.style.display = 'none';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', __auth_php_url__);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    //xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
    xhr.onload = function (e) {
        if (xhr.readyState === 4) {
            auth__disable_submit_buttons(false);
            if (xhr.status === 200) {
                var resp = xhr.responseText.trim();
                /*
                if ((typeof (resp) === 'string') && resp.includes('Error')){
                    var t = resp.replace(/\r/g,'<br>').replace(/\\/g,'');
                    el_err.innerHTML = t;
                    return;
                }else if(resp.startsWith('<')){
                    document.getElementById('auth_components').style.display = 'none';
                    el_info.style.display = 'block';
                    el_info.innerHTML = resp;
                    return;
                }
                */
                try{
                    var json = JSON.parse(resp);
                    if (json['location']){
                        window.location = json['location'];
                        return;
                    }else if(json['error']){
                        el_err.innerHTML = 
                            '<span data-ci="'+json['error']+'">'
                                +auth__translate(json['error'])
                            +'</span>'
                    }else{
                        if (callback) callback(json);
                    }
                }catch(e){
                    console.log("ERROR: while treating XHR response:", e);
                    console.log(resp);
                    console.log("----------");
                    return;
                }
            } else{ console.error("XHR ERROR: status:", xhr.status, xhr.statusText); }
        } else{ console.error("XHR ERROR: state:", xhr.readyState, xhr.statusText); }
    };
    console.log(dict);
    var params = "";
    for(var k in dict){
        //console.log(k);
        if (!dict.hasOwnProperty(k)) continue;
        params += k + '=' + dict[k] + '&';
    };
    //let params = Object.entries(dict).map(function(pair){pair.join('=')}).join('&');
    //var r = JSON.stringify()
    //console.log("PARAMS:", params);
    auth__disable_submit_buttons(true);
    xhr.send(params);
}

function auth__submitt_request(dict){ //sister of the above xhr
    //callback not required as this will result in redirection
    var frm = document.querySelector('#auth__submitter');
    var hidden_inputs = frm.querySelectorAll('input');
    [].forEach.call(hidden_inputs, function(e, i){
        e.name = ""; e.value = "";
    });
    Object.entries(dict).forEach(function(en, ix){
        hidden_inputs[ix].name = en[0];
        hidden_inputs[ix].value = en[1];
    });
    frm.submit();
}

function auth__display_info(m){
    var els = document.querySelectorAll('#auth_info, #auth_components');
    els[1].style.display = 'none';
    if (typeof m !== 'string'){
        els[0].style.display = 'none';
        els[0].appendChild(m);
        translate_content(els[0], 0, 0, function(){
            console.log(els[0]);
            els[0].style.display = 'block';
        });
    }else{
        els[0].innerHTML = m;
        els[0].style.display = 'block';
    }
}

function auth__display_error(msg_id){
    var msg = auth__translate(msg_id, 0, 0);
    document.getElementById('auth_errors').innerHTML = 
            '<span data-ci="' + msg_id +'">' + msg + '</span>';
    document.getElementById('auth_errors').style.display = 'block';
}

function auth__login(el){
    var email_el = document.getElementById('li_email');
    var pass_el = document.getElementById('li_password');
    var email = email_el.value;
    var pass = pass_el.value;
    var err = 0;
    if (!email) err = 'email_may_not_be_empty';
    else if (!pass) err = 'password_may_not_be_empty';
    if (err){
        auth__display_error(err);
        return;
    }
    var hasher1 = new jsSHA('SHA-1', 'BYTES');
    hasher1.update(pass)
    var pwh = hasher1.getHash('HEX');
    
    var dict = {'pwh':pwh, 'lauth_act':'li', 'email':email, 'lang':'none'};
    //auth__submitt_request(dict);
    send_auth_request(dict);
}

function auth__sign_up(el){
    var name = document.getElementById('su_nm').value;
    var email = document.getElementById('su_ml').value;
    var pass1 = document.getElementById('su_pw').value;
    var pass2 = document.getElementById('su_cf').value;
    var err = 0; 
    if (!name) err = 'name_may_not_be_empty';
    else if (!email) err = 'email_may_not_be_empty';
    else if (!pass1) err = 'password_may_not_be_empty';
    else if (pass1 != pass2) err = 'passwords_mismatch';
    if (err){
        auth__display_error(err);
        return;
    }
    var hasher1 = new jsSHA('SHA-1', 'BYTES');
    hasher1.update(pass1);
    var pwh = hasher1.getHash('HEX');
    var dict = {'pwh':pwh, 'lauth_act':'su', 'email':email, 'name':name, 'lang':'none'};
    send_auth_request(dict, function(json){
        //var m;
        AUTH_SERV_JSON_RESP = json;
        if (json['act_mail_sent']){
            var info_inner = document.createElement('div');
            info_inner.setAttribute('data-ci', "activation_email_sent");
            auth__display_info(info_inner);
        }else{
            
        }
    });
}

function auth__pw_reset_new_pw(el){
    var code, email, pass1, pass2, a = Array(4);
    document.querySelectorAll('#pwr_code, #pwr_email, #pwr_password1, #pwr_password2')
    .forEach(function(x,i){ a[i] = x.value });
    [code, email, pass1, pass2] = a;
    //console.log(a);
    /*var code = document.getElementById('pwr_code').value;
    var email = document.getElementById('pwr_email').value;
    var pass1 = document.getElementById('pwr_password1').value;
    var pass2 = document.getElementById('pwr_password2').value;*/
    var err = 0;

    if (!email) err = 'email_may_not_be_empty';
    if (!code) err = 'insert_recovery_code';
    else if (!pass1) err = 'password_may_not_be_empty';
    else {
        if (pass1 != pass2) err = 'passwords_mismatch';
    }
    if (err){
        auth__display_error(err);
        return;
    }
    var hasher1 = new jsSHA('SHA-1', 'BYTES');
    hasher1.update(pass1)
    var pwh = hasher1.getHash('HEX');
    var dict = {'pwh':pwh, 'lauth_act':'pwrc', 'email':email, 'pwrc':code, 'name':name, 'lang':'none'};
    send_auth_request(dict);
}

function auth__pw_recovery_init(el){
    var email = document.getElementById('ipwr_email').value;
    var err = 0;
    if (!email) err = 'email_may_not_be_empty';
    if (err){
        auth__display_error(err);
        return;
    }
    var dict = {'lauth_act':'ipwr', 'email':email, 'lang':'none'};
    send_auth_request(dict);
    send_auth_request(dict, function(json){
        var m;
        AUTH_SERV_JSON_RESP = json;
        if (json['reset_code_sent']){
            var info_inner = document.createElement('div');
            info_inner.setAttribute('data-ci', "reset_code_sent");
            auth__display_info(info_inner);
        }else{
            
        }
    });
}

function auth__login_with(provider){
    var oas_key = provider + '-oas';
    //var oas = localStorage.getItem(oas_key);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', __oauth_php_url__, true);
    //xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function (e) {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                var nl = xhr.responseText;
                console.log("GOING TO OAUTH:", nl);
                setTimeout(function(){
                    window.location.href = nl;
                }, 250);
            } else {
                console.error(xhr.statusText);
            }
        }
    };
    //let urlParameters = Object.entries(obj).map(function(pair){pair.join('=')}).join('&');
    urlParameters = 'login=' + provider + '&oas=null';
    xhr.send(urlParameters);
}

function TabKeyNavigator(){
    var _active_at_down;
    var self = this;
    this.key_up = function(e){
        e = e || event;
        if (e.keyCode != 9) return;
        var el = self._active_at_down;
        self._active_at_down = null;
        if (!el) return;//el = document.activeElement;
        var p;
        if (el.tagName == 'INPUT'){
            p = el.parentNode.parentNode.parentNode;
        } else if (el.tagName == 'BUTTON'){
            p = el.parentNode.parentNode;
        }
        if (p){
            if (self.focus_next(p, el)){
                e.stopPropagation();
                e.preventDefault();
                return false;
            }
        }
    }

    this.key_down = function(e) {
        "use strict";
        // pick passed event or global event object if passed one is empty
        e = e || event;
        if (e.keyCode != 9) return;
        self._active_at_down = document.activeElement;
        /*
        e.stopPropagation();
        e.preventDefault();
        return false;
        */
    }

    this.focus_next = function(p, el){
        var els = p.querySelectorAll('input, button');
        var index;
        for (index = 0; index < els.length; index++){
            if (els[index] == el) break;
        }
        var n = index+1; if (n >= els.length){
            return; 
        }
        //console.log("next index:", n);
        var next = els[n];
        next.style.visibility = 'visible';
        next.focus();
        el.style.visibility = '';
        el.blur();
        return true;
    }
}

var __tab_key_navigator_inst__;
function tabKeyNavigatorInst(){
    if (!__tab_key_navigator_inst__) __tab_key_navigator_inst__ = new TabKeyNavigator();
    return __tab_key_navigator_inst__;
}

//const tab_key_nav = new TabKeyNavigator();

/*
if (window.addEventListener){
    window.addEventListener('load', auth__load_components);
}else{
    window.attachEvent('onload', auth__load_components);
}
*/

