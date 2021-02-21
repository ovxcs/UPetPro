function Inventory(){

    const __page = '/aucts/products.php';
    const __req_params = ['products_op', 'my_list'];
    const __model_selector = '#inventory__models .row';
    const __table_selector = '#inventory__table';
    const __messages_selector = '#inventory__message';
    const __no_pict_img = '/res/_/camera-icon3.jpg';
    var self = this;
    self.lst = 0;
    self.sts = 0;

    this.fill_table_from_json = function(json){
        //console.log(json);
        if (json){
            self.lst = json['lst'];
            self.sts = json['sts'];
        }
        var table = document.querySelector(__table_selector);
        var msg = document.querySelector(__messages_selector);
        table.innerHTML = '';
        msg.style.display = 'none';
        var model = document.querySelector(__model_selector);//.innerHTML;
        //table.style.display = "none";
        for (var i=0; i < self.lst.length; i++){
            var row = table.appendChild(model.cloneNode(true));
            self.build_row(row, i);
            //row.style.display = "block";
        }
        //model.style.display = "block";
        //table.style.display = "";
    }

    this.build_row = function(row, index){
        var entry = self.lst[index];
        //console.log(entry);
        var img_src = entry['def'];
        if (!img_src || String(img_src).length < 20)
            img_src = __no_pict_img;
        var name = entry['name']; if(!name) name = '(no name)';
        //var parent = row.parentNode;
        var spans = row.getElementsByTagName('span');
        row.setAttribute("data-goodId", entry['id']);
        spans[0].innerHTML = index + '.';
        spans[1].innerHTML = entry['id'];
        row.getElementsByTagName('img')[0].src = img_src;
        row.getElementsByClassName('product_name')[0].innerHTML = name;
        if (entry['offer']){
            local_dt_from_serv_ts(entry['offer']['end_ts'], language())
            .then(function(dt){
                var stat = row.querySelector('.product_status');
                var stat_spans = stat.querySelectorAll('span');
                stat_spans[1].innerHTML = dt[0];
                stat_spans[1].setAttribute('data-cts', dt[1]);
                stat.style.display = 'block';
                stat_spans[2].style.display = 'none';
            }, function (err){
                console.log("ERROR: ", err);
            });
        }else{
            var stat_spans = row.querySelectorAll('.product_status span');
            stat_spans[0].style.display = 'none';
            stat_spans[1].style.display = 'none';
        }
        /*
        [].forEach.call(row.getElementsByTagName('td'), function(td, td_ix){
            td.onclick = function(ev) {
                //console.log(index, td, td_ix, ev);
                self.open_item(index, td, td_ix, ev);
            }
        });
        */
    }

    this.fetch_json = function(params){
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
         }, function (err){
            console.log("ERROR:", err);
         })
    }

    this.request_list = function(params){
        if (!params) params = {};
        params[__req_params[0]] = __req_params[1];
        return self.fetch_json(params)
        .then(function(rjson){
            if (rjson.hasOwnProperty('error') && rjson['error']){
                console.log(rjson['error']);
                throw rjson['error'];
            }
            console.log(rjson);
            self.fill_table_from_json(rjson);
        }, function (err){
            console.log("ERROR:", err);
            return "iError!";
        });
    }

    this.request_json = function(params){
        if (!params) params = {};
        params['products_op'] = 'my_list';
        return self.request_list(params)
        .then(function(arg){
            //console.log("req.json:", arg)
        });
    }

    this.open_item = function(row){
        /* !!! NAVIGATE INSTEAD
        ProductRegistrationInst().fetch_and_load_json(self.lst[row_ix]['id'])
        .then(function(a){
            show_win__product_registration();
        });
        */
        console.log(row.getAttribute('data-goodId'));
        window.location.href = "/cdx/aucts/product_registration.html?gid=" + row.getAttribute('data-goodId');
    }

    this.img_click = function(event){
        console.log(event.target);
        event.stopPropagation();
    }
}

var __inventory_inst = 0;
function InventoryInst(){
    if (!__inventory_inst) __inventory_inst = new Inventory();
    return __inventory_inst;
}


if (window.addEventListener){
    window.addEventListener('load', function(){
        InventoryInst().request_json();
    });
}else{
    window.attachEvent('onload', function(){ products__test('load2') });
}









