var uploader__PICS_PER_ROW = 6;

parent_callback = function(fname, args, callback){
    if (parent.uploader__callback === undefined) return;
    return parent.uploader__callback();
}

var __IS_WIDE_CONSTANT__ = 1.4;
function recommended_aspect(){
    var w = window.innerWidth;
    var h = window.innerHeight;
    return h > w ? 1 : 2;
    if (w < 800) return 1;
    if (w > __IS_WIDE_CONSTANT__ * h) return 2; //TO AVOID TOGGLE ASPECT WHEN KB SHOWS
    return 1;
}



var __uploader_prev_aspect = 0;
window.onresize = (function(a,b,c,d,e){
    var ra = recommended_aspect();
    if (ra === __uploader_prev_aspect) return;
    uploader__PICS_PER_ROW = (ra == 1) ? 3 : 6;
    
    /*
    if (UPLOADER['files'] === undefined) return;
    var table = new uploader__table();
    table.element().innerHTML = '';
    uploader__create_table(UPLOADER.default_index());
    */
    //g_uploader.create_table();
});

//---------------------------------------------------------------------------


function onlyUnique(value, index, self) { 
    return self.indexOf(value) === index;
}

var __uploader__cells_index = 0;
function UploaderTable(){
    var self = this;

    var __selector = '#pics_uploader';
    var __table = document.querySelectorAll(__selector)[0];
    __table.innerHTML = '';
    var __rows = 0; var __cells = 0;
    var __srcs = 0;
    var __modified = false;

    this.cells = function(){
        __cells = __table.getElementsByTagName('span');
        return __cells;}
    this.rows = function(){
        __rows = __table.getElementsByClassName('uploader__row'); 
        return __rows;}
    this.element = function(){
        __table = document.querySelectorAll(__selector)[0];
        return __table;}
    this.modified = function(){ return __modified; }
    this.appendRow = function(){
        if (!__rows) self.rows();
        var new_row = document.createElement('div');
        new_row.className = 'uploader__row';
        __table.insertAdjacentElement('beforeend', new_row);
    }

    this.appendCell = function(){
        if (!__rows) self.rows();
        var last_row = __rows[__rows.length - 1];
        var new_cell = document.createElement('span');
        new_cell.className = 'uploader__cell';

        var d = new Date();
        var n = __uploader__cells_index++;
        new_cell.className = 'uploader__cell';
        new_cell.setAttribute('onClick','');
        new_cell.onmouseover = function (e){
            self.show_pict_menu(new_cell)};
        new_cell.onmouseout = function (e){
            self.show_pict_menu(new_cell, false)};

        last_row.insertAdjacentElement('beforeend', new_cell);
    }

    this.first_empty_cell_index = function(){
        cs = self.cells();
        var i = cs.length - 1;
        for(; i >= 0; i--){
            if (cs[i].innerHTML.length > 10)
                return i+1;
        }
        return 0;
    }
    this.add_cells = function(count){
        var feci = self.first_empty_cell_index();
        var tcc = __cells.length;
        var req = count - (tcc - feci);
        var req_rows = Math.round(req/uploader__PICS_PER_ROW) + 1;
        for (var i = 0; i < req_rows; i++){
            self.appendRow();
            for (var k = 0; k < uploader__PICS_PER_ROW; k++){
                self.appendCell();
                var last_cell = __cells[__cells.length-1];
                last_cell.setAttribute('min-height', '50px');
                last_cell.innerHTML = '&nbsp';
                last_cell.style.background = 'rgba(0,0,0,0)';
            }
        }
    }
    
    this.show_pict_menu = function(el, flag){
        var d = el.getElementsByTagName("div")[1];
        if (d === undefined) return;
        if (flag === false){
            d.style.background='rgba(0,0,0,0)';
        }else{
            d.style.background='black';
        }
        var els = d.getElementsByTagName("label");
        for (var i = 0; i < els.length; i++){
            var l = els[i];
            if (flag === false){
                if (l.className !== 'checked_as_default')
                    l.style.display='none';
            }else{
                l.style.display='inline';
            }
        }
    }

    this.get_cell = function(el){
        var ci = el.getAttribute('data-cellIndex');
        if (ci !== undefined && ci !== null)
            return el;
        if (el.parentNode)
            return self.get_cell(el.parentNode);
    }

    function __mark_default(self, el){
        var cell = self.get_cell(el);
        var index = cell.getAttribute('data-cellIndex');
        //var table = new uploader__table();
        var olds = self.element().getElementsByClassName('checked_as_default');
        [].forEach.call(olds, function(e, i){
            e.className = 'check_default';
            e.style.display = 'none';
        });
        el = cell.getElementsByClassName('check_default')[0];
        el.style.display = 'inline';
        el.className = 'checked_as_default';
    }

    this.mark_default = function(obj, set_modif){
        var el;
        if (typeof(obj) === 'string'){
            var img = self.element().querySelectorAll('img[src="'+obj+'"]')[0];
            if (img){
                el = self.get_cell(img); 
            }
        }else{
            el = obj;
            console.log("mark_def: set modif to true");
            //__modified = true; //the other case occurs only on creation; 
        }
        if (set_modif === true) __modified = true;
        if (el) __mark_default(this, el);
        
    }

    this.clear = function(){
        __table.innerHTML = '';
        document.getElementById('uploader__no_pict').style.display = 'inline';
        __rows = 0; __cells = 0;
        __srcs = 0;
        __modified = false;
    }

    this.remove_cell = function(el){
        if (el.style.display === 'none') return;
        var cell = el.parentNode.parentNode.parentNode;
        var index = parseInt(cell.getAttribute('data-cellIndex'));
        //var table = new uploader__table();
        var urls = Array.from(self.grab_srcs());
        r = urls.splice(index, 1);
        self.element().innerHTML = '';
        self.update_from_list(urls);
        console.log("set modif to true");
        __modified = true;
    }

    this.update_from_list = function(files_list, is_new){

        if (!files_list || (files_list.length === 0)){
            console.log("show it");
            document.getElementById('uploader__no_pict').style.display = 'inline';
            return;
        }
        document.getElementById('uploader__no_pict').style.display = 'none';
        var idy = "";
        var L = files_list.length;
        //var table = new uploader__table();
        self.add_cells(L);
        var cells = self.cells();
        var feci = self.first_empty_cell_index();
        idy = 'models';
        var elem2 = document.querySelectorAll("#models .uploader__td_div")[0];
        for (var i = 0; i < L; i++){
            var ci = feci + i;
            cells[ci].innerHTML = elem2.outerHTML;
            cells[ci].getElementsByTagName('img')[0].src = files_list[i];
            cells[ci].setAttribute('data-cellIndex', ci);
        }
        __modified = (is_new === true) ? false : true;
        console.log("set modif to:", __modified);
    }
    
    self.grab_srcs = function(){
        var imgs = __table.querySelectorAll('img');
        __srcs = new Array(imgs.length);
        for (var i = 0; i < imgs.length; i++){
            __srcs[i] = imgs[i].src;
        }
        return __srcs;
    }

    this.grab_default = function(){
        var sel = __table.querySelectorAll('.checked_as_default')[0];
        if (sel){
            var cell = self.get_cell(sel);
            if (cell){
                var img = cell.getElementsByTagName('img')[0];
                if (img){
                    return img.src;
                }
            }
        }
        if (__srcs) return __srcs[0];
    }
}


function Uploader(){
    __page = 'uploader/uploader.php';
    var self = this;
    //self.on_win_close_callback = on_win_close_callback;
    this.clear = function(){
        self.table.clear();
    }
    this.create_table = function(files_list, def_file){
        if (typeof(files_list) === 'string'){
            files_list = files_list.match(/[^\s\,]+/g);
        }
        self.table = new UploaderTable();
        self.table.update_from_list(files_list, true);
        if (def_file){
            self.table.mark_default(def_file, false);
        }
    }

    this.selection_done = function(elem){
        var idy = elem.parentNode.id;
        var elem2 = document.getElementById(idy);
        var inp = elem2.getElementsByTagName('input')[0];
        self.upload_files__fetch(inp)
        .then(function(recv_json){
            console.log("upload files done", recv_json['files']);
            if (!self.table) self.create_table([]);
            self.table.update_from_list(recv_json['files'], false);
        })
        .catch(console.log);
    }

    this.get_data = function(){
        if (self.table)
            return ({
                'files': self.table.grab_srcs(),
                'default': self.table.grab_default(),
                'modified': self.table.modified()
            });
    }

    this.upload_files__fetch = function(input_element){
        var inp = input_element;
        var fd = new FormData();
        for (var i = 0; i < inp.files.length; i++){
            fd.append(i, inp.files[i]);
            console.log(i, inp.files[i]);
        }
        fd.append('SOME_CONTENT__ULF', inp.files.length);
        return fetch(__page, {
            method: 'POST',
            body:fd,
            credentials: 'same-origin',
        })
        .then(function(resp){
            if(resp.status != 200){
                throw resp.status + ' ' + resp.statusText;
            }
            return resp.json();
        })
        .catch(function(e){console.log("ERROR:",e)});
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
         })
    }

    /*
    this.xh_request = function(data){
        var xhr = new XMLHttpRequest();
        var _table = self.table;
        xhr.open('POST', 'uploader/uploader.php', true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var json = JSON.parse(xhr.responseText);
                if (json['files']){
                    _table.update_from_list(json['files']);
                }
            }
        }
        xhr.send(data);
    }*/

    var __on_close_stack = [];
    this.addCloseEventListener = function(callback){
        __on_close_stack.push(callback)
    }

    this.on_win_close = function(){
        __on_close_stack.forEach(function(cb){cb()});
        __on_close_stack = [];
    }

}

var __uploader_inst;
function UploaderInst(){
        if(!__uploader_inst) __uploader_inst = new Uploader();
        return __uploader_inst;
    }

/*
function uploader__create(files, def_pic, on_close){
    var _uploader = new Uploader(on_close);
    if (!files){ files = []; def_pic = null; }
    _uploader.create_table(files, def_pic);
    g_uploader = _uploader;
    return _uploader;
}*/

//var g_uploader = new Uploader();

