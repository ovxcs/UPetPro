
function Products(){
    const __page = '/aucts/products.php';
    const __cell_model_selector = '#products__models .products__grid_cell';
    const __table_selector = "#aucts_table";
    const __pages_selector = 0;
    const __update_step = 10;

    var self = this;
    self.sts = 0;
    self.list = 0;
    self.favs = [];
    self.pages = -1;

    var pages_selector_insts = new Array();
    this.attach_pages_selector = function(inst){
        pages_selector_insts.push(inst);
    }

    var __crt_cols_count = 0;
    this.fill_table_from_json = function(json){
        //console.log(json);
        if (json){
            self.list = json['lst'];
            self.sts = json['sts'];
            if (json['favs']) self.favs = json['favs'].split(',');
        }
        var table = document.querySelector(__table_selector);
        table.innerHTML = "";
        var cell_model = document.querySelector(__cell_model_selector);
        for(var i = 0; i < self.list.length; i++){
            var ccn = cell_model.cloneNode(true);
            fill_cell(ccn, i, self.sts);
            table.appendChild(ccn);
        }
    }

    const open_picts_viewer = function(ev){
        var ix = ev.target.__index__;
        var entry = self.list[ix]['good'];
        var def_pic = entry['def_pic'];
        var picts = entry['picts'].split(',');
        var def_index = picts.indexOf(def_pic);
        g_picts_viewer.navigate_to(def_index, picts);
        //show_win__viewer();
        //document.querySelector("#win__viewer").style.display = "";
    }

    const get_cell_index = function(el){
        while (true){
            if (el.hasAttribute("data-cell_index"))
                return parseInt(el.getAttribute("data-cell_index"));
            el = el.parentElement;
        }
    }

    const fill_cell = function(cloned_cell, index){
        var entry = self.list[index];
        cloned_cell.setAttribute('data-cell_index', index);
        cloned_cell.setAttribute('data-goodId', entry['good']['id']);
        var src = entry['def'] || '';
        var im = cloned_cell.querySelector('img');
        if (src){
            im.src = src;
            im.style.display = '';
            im.style.cursor = "pointer";
            im.__index__ = index;
            im.onclick = function(ev){
                open_picts_viewer(ev);
            }
        } else {
            im.style.display = 'none';
            cloned_cell.querySelector('img ~ .no_pict_alt').style.display = 'block';
        }
        cloned_cell.querySelector('.name').innerHTML = entry['good']['name'] || '(no name)';
        cloned_cell.querySelector('.products__eta').innerHTML = eta_from_server_ts(entry['end_ts'], self.sts);
        var valut = entry['good']['valut'];
        var starting_price = cloned_cell.querySelectorAll('.products__starting_price span');
        starting_price[0].innerHTML = entry['good']['price_s'];
        starting_price[1].innerHTML = valut;
        cloned_cell.querySelector("span.bid__valut").innerHTML = valut;
        var crt_bid = cloned_cell.querySelectorAll('.products__crt_bid span');
        crt_bid[0].innerHTML = "?!";
        crt_bid[1].innerHTML = valut;
        var bottoms = cloned_cell.querySelector('.bottom_numbers .ID').innerHTML = entry['good']['id'];
        //bottoms[2].innerHTML = index;
        if (self.favs.indexOf(entry['id']) != -1){
            cloned_cell.getElementsByClassName(
                    "bookmark")[0].classList.add("bookmarked");
        }
        var dn = Math.random() * 10;
        if (dn >= 1){
            rdn = Math.floor(dn * 20)/20;
            var ec = evaluation_circles(dn);
            var el = cloned_cell.querySelector('.evaluation_circles span');
            if (ec[0] === 'n'){
                el.innerHTML = ec;
                el.setAttribute('data-ci', 'no rev.');
            }
            else{
                el.innerHTML = ec;
                el.removeAttribute('data-ci');
                cloned_cell.querySelector('.evaluation_circles span:nth-child(2)').innerHTML = '['+rdn+']';
            }
            
        }
    }

    const owner_cell = function(el){
        var n = (el.dataset.cell_index)
        if (!isNaN(n) && isFinite(n)) return el;
        if (el.parentNode) return owner_cell(el.parentNode);
    }

    const update_ui = function(updates){
        [].forEach.call(document.querySelectorAll(
                '#products__component .products__eta'),
                function(e, i){
            var r = eta_from_server_ts(self.list[i]['end_ts'], self.sts);
            //.then( r => {
                e.style.color = (updates != undefined)? 'black' : null;
                e.innerHTML = r
            //});
        });
    }

    const requires_update_filter = function(){//who requires update
        if (!self.list) return 0;
        var _list = [];
        //return all - no filter - for now!!!
        self.list.forEach(function(e, i){
            _list.push(e['id']);
        });
        return _list
    }

    const serv_sync_init = function(){
        var __counter = 0;
        console.log(__update_step);
        self.__timerID = setInterval(function(){
            //console.log(__counter);
            if (!self.sts) return;
            if (!self.list.length){
                __counter = 0;
                return;
            }
            if (__counter >= __update_step - 1){
                //console.log("updating");
                list_to_update = requires_update_filter();
                self.fetch_json({
                        'products_op': 'updates',
                        'list': list_to_update}
                )
                .then(function(rjson){
                    //console.log(rjson);
                    self.sts = rjson['sts'];
                    update_ui(rjson['updates']);
                });
            }else{
                self.sts += 1;
                update_ui();
            }
            __counter += 1;
            __counter %= __update_step;
        }, 1000);
    }

    this.fetch_json = function(data, url_search_params_dict){
        data['wls'] = window.location.search;
        var urlsp = url_search_params_dict ?
            new URLSearchParams(url_search_params_dict) : '';
        console.log(urlsp.toString());
        return fetch(__page + '?' + urlsp.toString(), {
            method: 'POST',
            headers: {
               'Accept': 'application/json',
               'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(data),
        })
        .then(function(resp){
            if(resp.status != 200){
                throw resp.status + ' ' + resp.statusText;
            }
            //resp.text().then(console.log);
            return resp.json();
        })
        /*
        .catch((e) => {
            console.log("ERROR:", e);
        });
        */
    }

    function evaluation_circles(dn){
        if (dn < 0){
            return "no rev.";
        }
        dn = dn / 10 * 5;
        var FC = '&#x2b24;';
        var HC = '&#x25D0;';
        var EC = '&#x2b58;';
        var full = 5;
        var int_p = Math.trunc(dn);
        var fr_p = dn - int_p;
        if (int_p >= full) return FC.repeat(full);
        return  (FC.repeat(int_p)) + 
                (fr_p < 0.25 ? EC : (fr_p > 0.75 ? FC : HC)) +
                    (EC.repeat(full - int_p - 1));
    }

    this.request_list = function(filter, urlsp_dict){
        if (!filter) filter = {};
        filter['products_op'] = 'all_offers';
        return self.fetch_json(filter, urlsp_dict).then(function(rjson){
            if (rjson.hasOwnProperty('error') && rjson['error'])
                throw rjson['error'];
            //update UI (table and pages selector);
            //if (typeof PagesSelectorInst !== 'undefined')
                //PagesSelectorInst(__pages_selector).build(rjson['pages'],
                        //self.request_list);
            pages_selector_insts.forEach( function (psi, ix){
                psi.build(rjson['pages'], self.request_list); // or hide if rjson['pages'] === 0;
            });
            self.pages = rjson['pages'];
            self.fill_table_from_json(rjson);
        })
        .catch(function(e){
            console.log("ERROR:", e);
            console.trace();
        });
    }

    this.on_bid_button_click = function(ev, btn){
        btn.color = 'red';
        var cell = owner_cell(btn);
        var ix  = cell.dataset.cell_index;
        var value = cell.querySelector('input').value;
        var err_place = cell.querySelector('.bid__errors');
        err_place.style.display = 'none';
        if (!value || isNaN(value)){
            var er_str_id = "invalid_value";
            err_place.setAttribute("data-ci", er_str_id);
            err_place.innerHTML = translate_text(er_str_id);
            //translate_strId(er_str_id);
            err_place.style.display = null; //reset to CSS;
        }
        ev.stopPropagation();
    }

    this.open_item = function(ev, el){
        var cell = owner_cell(el);
        window.location.href = "/cdx/aucts/product_registration.html?mode=view&gid=" + cell.getAttribute('data-goodId');
    }
    //serv_sync_init();

    this.togggle_bookmark = function(ev, el){
        var auct_id = self.list[get_cell_index(el)]['id'];
        product_registration__bookmark(auct_id, function(json){
            var cb = el.classList.contains("bookmarked");
            if (json['stat'] == 0){
                if (cb) el.classList.remove("bookmarked");
            }else{
                if (!cb) el.classList.add("bookmarked"); 
            }
        });
        ev.stopPropagation();
    }
}

var __products_inst;
function ProductsInst(){
    if(!__products_inst){
        __products_inst = new Products();
    }
    return __products_inst;
}


function products__loader(mode){
    if (mode == 'load1'){
        var dict = {'products_op' : 'all_offers'};
        ProductsInst().fetch_json(dict)
        .then(function(rjson){
            if (rjson.hasOwnProperty('error') && rjson['error'])
                throw rjson['error'];
            console.log(typeof(rjson));
        })
        .catch(function(e){
            console.log("ERROR:", e);
        });
    }
    if (mode == 'load2'){
        ProductsInst().request_list();
    }
}


if (window.addEventListener){
    window.addEventListener('load', function(){ products__loader('load2') });
}else{
    window.attachEvent('onload', function(){ products__loader('load2') });
}





