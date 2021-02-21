function create_user_profile_components(){
    return fetch(coderoot()+'/users/profile_widget.html')
    .then(function(r){
            if(r.status === 404) throw ('page not found (404): users/profile_widget.html');
            return r.text()
        })
    .then(function(t){
            var upc = document.getElementById('user_profile_components');
            upc.style.display = 'none';
            upc.innerHTML = t;
        })
    .catch(function(e,b){console.log("loading profile failed", e)});
}

glob_MY_INFO = 0;

function session__fetch_json(page, params){
    //var url = page + '?' + new URLSearchParams(params).toString();
    //return fetch(url)
    return fetch(page, {
            method: 'POST',
            headers: {
               'Accept': 'application/json',
               'Content-Type': 'application/json',
            },
            referrerPolicy: 'no-referrer',
            credentials: 'same-origin',
            body: JSON.stringify(params),
        })
    .then(function(r) {
            if(r.status === 404){
                console.log("??? Page not found! ???");
                throw ('page not found (404):' + page);
            }
            //console.log(r);
            return r.json()
        })
    .then(function(r_json){
            if (r_json && ('error' in r_json)){
                console.log('ERROR. Redir to error page');
                //document.getElementById('xhr_errors').innerHTML = r_json['error'];
                //logout();
                show_xhr_dbg_msg(r_json['error'], 'orange', 'black');
                return;
            }
            glob_MY_INFO = r_json['usr_info'];
            return r_json;
        })
    .catch(function(e){console.log("ERROR:", e)});
}

function request_my_info(){
    return session__fetch_json('/ep.php', {
        'req':'usr_info',
    })
}

function session__my_info(){
    if (glob_MY_INFO)
        return new Promise(function(resolve, reject){
            resolve(glob_MY_INFO);
        });
    else
        return request_my_info();
}

function user_profile__fill_from_json(json){
    var info = json['usr_info'];
    var img_src = info['pict'];
    if (window.location.search.indexOf('provider=facebook') > -1){
        // https://graph.facebook.com/2227744360842482/picture
        var img_src = "https://graph.facebook.com/"+info['eid']+"/picture";
    }
    if (img_src === null || img_src === ''){
        var img_src = '/res/_/user3.png';
    }
    console.log("USER INFO:", info);
    var is_na = (parseInt(info['status']) === 0); //not activated
    var is_oau = (info['flags'] & 0x8); //oauth user
    var provider = info['prov'];
    document.getElementById('profile__activ_stat').style.display = (is_na && !is_oau)   ? 'block' : 'none';
    var prov_el = document.getElementById('profile__provider');
    document.querySelector('#profile__provider span').innerHTML =  provider ? provider : '';
    prov_el.style.display =  provider ? 'block' : 'none';
    document.getElementById('profile__user_pict').src = img_src;
    document.getElementById('profile__user_name').innerHTML = info['name'];
    var upc = document.getElementById('user_profile_components');
    upc.style.display = 'inline-block';
}

function logout(){
    console.log(glob_MY_INFO);
    console.log("logging out ...");
    //console.log('but not now');
    window.location.href = '/cdx/login.html';
}

function autoload_user_profile(){
    console.log("loading user profile components...");
    create_user_profile_components()
    .then(request_my_info)
    .then(function (recv_json){
        user_profile__fill_from_json(recv_json);
    })
    .catch(function(e) {
        console.log("ERROR:", e.stack);
    });
}

function profile__post_request_page(){
    console.log(document.user_profile_widget_form.submit());
}

if (window.addEventListener){
  window.addEventListener('load', autoload_user_profile)
}else{
  window.attachEvent('onload', autoload_user_profile)
}