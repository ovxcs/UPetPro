function send_parameters(method, target, parameters_to_send, callback, arg){
    //var _str = SCD + '/gt.php?json=' + encodeURIComponent(JSON.stringify(json_to_send));
    //console.log("SENDING JSON: ", json_to_send);
    var xhr = new XMLHttpRequest();
    let url = target;
    if (method === 'GET'){
        url += '?' + parameters_to_send;
        console.log(url);
        xhr.open("GET", url, true);
        xhr.setRequestHeader("Content-Type", "application/json");
    }else if(method === 'POST'){
        xhr.open("POST", url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }
    //xhr.setRequestHeader("mwtoken", MWTOKEN);
    if (callback !== undefined)
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                //show_working(false);
                try {
                    var json = JSON.parse(xhr.responseText);
                } catch (e){
                    console.log(e);
                    console.log(xhr);
                    document.write(xhr.responseText.replace('\r','<br>'));
                    return;
                }
                //console.log("SERVER JSON:", json, "/END SERVER JSON");
                //__last_callback_and_args__ = [callback, json, json_to_send, arg];
                //console.log('calling callback ...' + callback);
                callback(json, parameters_to_send, arg); //response, request, custom arg(can also be in the request but anyway...)
                //enable inputs;
            }
        };
    //if (callback !== 0) show_working(true);
    if (method === 'GET'){
        xhr.send();
    }else if (method === 'POST'){
        xhr.send(parameters_to_send);
    }
}

function send_json(method, target, json_to_send, callback, arg){
    let urlParameters = Object.entries(json_to_send).map(pair => pair.join('=')).join('&');
    send_parameters(method, target, urlParameters, callback, arg);
}

function load_component(page, holder){
    var xhr = new XMLHttpRequest();
    xhr.open("GET", page, true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById(holder).innerHTML = xhr.responseText;
        }
    };
    xhr.send();
}