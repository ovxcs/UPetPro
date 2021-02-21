var scripts = document.getElementsByTagName("script"),
    __product_registration_path__ = scripts[scripts.length-1].src;

function ProductRegistration(){
    var __page = '/aucts/products.php';
    //__page = __product_registration_path__.split('/').slice(0, -1).join('/') + '/' + __page;
    var self = this;
    var __inputs_ids__ = [
        'name', 'year', 'location',
        'description',
        'price_s', 'valut',
        'auct_ends', 'auct_ends_unit',
        'status',
        'activated'
    ];
    var uploader_data = 0;
    var NO_IMG = "data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=";
    var __initial_inputs__ = {};

    function __grab_inputs(aucts){
        var json = {};
        __inputs_ids__.forEach(function(e, i){
            if (!aucts && e.startsWith('auct')) return;
            var el_id = 'product_' + e;
            var el = document.getElementById(el_id);
            if (el){
                var val = el.value;
                if (el.type === 'checkbox') val = el.checked;
                if (__initial_inputs__[e] !== undefined){
                    if (__initial_inputs__[e] === val)
                        return;
                }
                json[e] = val;
            }
        });
        console.log("*** reg.prod: ", json);
        return json;
    }

    this.clear = function(force){
        if (!force){
            if (!grab_id()) return;
        }
        document.getElementById('product_id').innerHTML = '';
        __inputs_ids__.forEach(function(e, i){
            var el_id = 'product_' + e;
            var el = document.getElementById(el_id);
            if (el){ el.value = '';}
        });
        self.disable_all(false);
        document.querySelector('#offer_placed').style.display = 'none';
        document.querySelector('#product_def_pic img').src = '';
        uploader() && uploader().clear();
    }

    this.grab_into_json = function(aucts){
        var json = __grab_inputs(aucts);
        //var translations = self.__grab_translations();
        var uploader_info = uploader_data;
        if (uploader_info && uploader_info['modified'] === true){
            json['files'] = uploader_info['files'];
            json['def_pic'] = uploader_info['def'];
        }else{
            console.log("ERROR: gettering uploader data");
        }
        if (!Object.keys(json).length){
            //console.log('nothing modified');
            return json;
        }
        //console.log("$#@!", json);
        return json;
    }

    this.__fill_view_from_json = function(json){
        //console.log("loading from JSON", json);
        __inputs_ids__.forEach(function(e, i){
            var el = document.getElementById('product_' + e);
            if (json[e] === undefined) return;
            if (json[e] === null || json[e] === -1) json[e] = '';
            if (el) el.value = json[e];
            //console.log("initial", e, json[e]);
            __initial_inputs__[e] = json[e];
        });
        def_pic_show(json);
        document.getElementById('product_id').innerHTML = "#"+json['id'];
        if (json['files']) uploader().fill(json);
        let op_el = document.getElementById('offer_placed');
        if (json['offer']){
            self.disable_all(true);
            op_el.style.display = 'block';
            local_dt_from_serv_ts(json['offer']['end_ts'], language())
            .then(function(dt){
                console.log(dt);
                var ope = document.getElementById('offer_placed_ends');
                ope.innerHTML = '&nbsp;' + dt[0];
                ope.setAttribute('data-cts', dt[1]);
            }).catch(console.log);
            return;
        }
        self.disable_all(false);
        op_el.style.display = 'none';
    }

    this.fill_view_from_json = function(json){
        //console.log("fill_view_from_json...", json); 
        if (json['error'] || json['errors']){
            document.getElementById('register_product__errors').innerHTML = 
                json['error'] || json['errors'];
        }else{
            document.getElementById('register_product__errors').innerHTML = '';
            self.__fill_view_from_json(json);
        }
    }

    this.load_id = function(id){
        self.fetch_json({
                id:id,
                products_op:"product_details"})
        .then(function(json){
            console.log(">>>>>>>>>>>>>>>>>");
            console.log(json);
            console.log("<<<<<<<<<<<<<<<<<");
            self.fill_view_from_json(json);
        });
    }

    this.try_load_id_from_location = function(){
        const urlParams = new URLSearchParams(window.location.search);
        const gid = urlParams.get('gid');
        if (gid) this.load_id(gid);
    }

    this.display_error = function(error_id){
        var msg = '';
        if (error_id)
            msg = ((typeof translate_text === "function") && translate_text(error_id)) || error_id;
        document.getElementById('register_product__errors').innerHTML = msg;
    }

    function grab_id(json){
        var _id = parseInt(document.getElementById(
            'product_id').innerHTML.trim().substring(1));
        if (!isNaN(_id)){
            if (json) json['id'] = _id;
            return _id;
        }
    }

    this.register_product_and_update_if = function(el){
        return self.register_product(el)
        .then(function(recv_json){
            return self.fill_view_from_json(recv_json);
        })
        .catch(function(arg){
            console.log(arg);
        });
    }

    this.register_product = function(el, placeIt, silent){
        if (!silent) document.getElementById('register_product__errors').innerHTML = '';
        var json = self.grab_into_json(true);
        if (!json) return Promise.reject("ERROR: grab_into_json failed");
        json['products_op'] = (json['activated'] === true) ? 'placeIt' : 'set';
        grab_id(json);
        if (json.hasOwnProperty('name') && (!json['name'])){
            if (!silent) self.display_error("product_name_not_set");
            return Promise.reject("ERROR: product_name not set");
        }
        if (el) el.classList.add('clicked');
        return self.fetch_json(json)
        .then(function(resp){
            if (el) el.classList.remove('clicked');
            return resp;
        });
    }

    this.fetch_json = function(params){
        //console.log(__page, params);
        return fetch(__page, {
            method: 'POST',
            headers: {
               'Accept': 'application/json',
               'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(params),
        })
        .then(function(resp){
            if(resp.status != 200){
                throw resp.status + ' ' + resp.statusText;
            }
            return resp.json();
        })
        .catch(function(err){
            console.log("Loading json failed:", err);
        });
    }

    this.fetch_json_and_update_view = function(id){
        params = {
            'products_op': 'product_details',
            'id':id,
        }
        return self.fetch_json(params)
        .then(function(recv_json){
            self.fill_view_from_json(recv_json)})
        .catch(function(e){console.log("ERROR:", e)});
    }

    this.disable_all = function(flag){
        [].forEach.call(document.querySelectorAll(
                '#table_prices, #product_submit_buttons, #product_add_lang'),
            function(e){
                e.style.display = flag ? 'none' : 'flex';
            }
        );
        //document.querySelector('product_def_pic').disabled = flag;
        var els = document.querySelectorAll('button.fancy_buttons,'
            +'#register_product__forms input,'
            +'#register_product__forms textarea,'
            +'#register_product__forms select,'
            +'#product_def_pic');
        [].forEach.call(els, function(e, i){
            //console.log("e", e);
            if (e.id === 'product_lang_select') return;
            e.disabled = flag;
            if (e.tagName === 'SELECT') return;
            if (flag){
                e.style.background = '#DDDDDD';
                e.style.color = (e.tagName === 'BUTTON') ? 'gray' : 'black';
                e.style.border = "1px solid #CCCCCC";
                e.style.mouse = 'normal';
            }else{
                e.style.background = '';
                e.style.border = '';
                e.style.color = '';
                e.style.mouse = '';
            }
        });
    }

    var __uploader;
    const uploader = function(){
        if (__uploader === undefined)
            try{
                __uploader = UploaderTableInst('myUploadPics');
                __uploader.onDone = on_uploader_close;
            }catch(e){
                console.log(e);
            }
        return __uploader;
    }

    this.show_uploader = function(){
        //create Uploader ST if not already;
        //sets body css to a modal context (overflow hidden);
        uploader().show(true);
        document.getElementById("uploader_holder").style.display = "block";
    }

    function def_pic_show(data){
        var img = document.querySelector('#product_def_pic img');
        var alt = document.querySelector('#product_def_pic div');
        var def = data["def"] ? data["def"] : data["files"] ? data["files"][0] : "";
        if (def){
            img.src = def;
            img.style.display = "block";
            alt.style.display = "none";
        }else{
            img.src = NO_IMG;
            img.style.display = "none";
            alt.style.display = "block";
        }
    }

    const on_uploader_close = function(data){
        console.log("on uploader close called", data);
        //document.querySelector('#win__uploader').style.display = 'none';
        def_pic_show(data);
        uploader_data = data;
    }

}

var __product_registration_inst = 0;
function ProductRegistrationInst(){
    if (!__product_registration_inst)
        __product_registration_inst = new ProductRegistration();
    return __product_registration_inst;
}

function product_registration__add_translation_box(){
    var boxes = document.querySelectorAll(".product_other_langs_box");
    if (getComputedStyle(boxes[0]).getPropertyValue("display") === 'none'){
        boxes[0].style.display = "block";
    }else if(boxes[boxes.length-1].getElementsByTagName("textarea")[0].value.trim()){
        var clone = boxes[0].cloneNode(true);
        clone.getElementById("product_lang_select").removeAttr('id');
        clone.getElementsByClassName("product_other_langs_name")[0].value = "";
        clone.getElementsByTagName("textarea")[0].value = "";
        //console.log(clone);
        document.querySelector("#product_other_languages").appendChild(clone);
    }
}

function product_registration__bookmark(goodId, callback){
    var json = {
        "products_op":"toggle_bookmark",
        "id":goodId
    }
    ProductRegistrationInst().fetch_json(json)
    .then(function(recv_json){
        callback(recv_json);
        console.log(recv_json);
    }, function(err){
        console.log("ERROR: bookmark failed:", err);
    });
}

function get_product_id(){
    const urlParams = new URLSearchParams(window.location.search);
    const gid = urlParams.get('gid');
    return gid;
}