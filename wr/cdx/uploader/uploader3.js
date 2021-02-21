
function FlexTable(id){
    var self = this;
    self.id = id;
    self.table = document.getElementById(id);
    self.add_cell_override = null; //if specified add_cell function should be called inside this.

    init();

    this.clear = function(){
        for (var i = self.table.children.length-2; i > 0; i--){
            self.table.removeChild(self.table.children[i]);
        }
    }

    this.add_cell = function(index = -1){
        var clone = self.table.children[0].cloneNode(true);
        clone.classList.remove("model");
        if (index === -1) self.table.insertBefore(clone,
                self.table.children[self.table.children.length-1]);
        else{
            console.log("ERROR: FlexTable: INSERTING CELL AT INDEX NOT IMPLEMENTED");
        }
        return clone;
    }

    function init(){
        document.getElementById(self.id).addEventListener("click", function(event){
            if (event.target.classList.contains("adder")){
                self.add_cell_override ? self.add_cell_override() : self.add_cell();
            }
        }, true);
    }
}

var __flextable_insts = {};
function FlexTableInst(id){
    if (!__flextable_insts[id])
        __flextable_insts[id] = new FlexTable(id);
    return __flextable_insts[id];
}
//==============================================================================



//==============================================================================
//UPLOADER

function UploaderTable(id, done_callback){
    var self = this;
    self.id = id;
    self.onDone = done_callback;
    var FT = FlexTableInst(id);
    //var input = FT.table.getElementsByClassName("file_input")[0];
    var input = create_hiden_file_input();
    var selCell, selCell_content;
    var initial_data = "";
    //var plus_button = FT.table.getElementsByClassName("adder")[0];
    //var done_button = FT.table.getElementsByClassName("done")[0];
    //var wip = FT.table.getElementsByClassName("wip")[0];

    //console.log("UploaderTable CALLED with id:", id);
    //init();
    FT.add_cell_override = function(index = -1){
        input.click();
    }

    this.clear = function() { FT.clear(); }

    this.file_selection_done = function(el){
        FT.table.querySelector(".cell.last").classList.add("working");
        UploaderProcessInst().upload_files__fetch(input)
        .then(function (json){
            //console.log("YEY!!!!", json);
            FT.table.querySelector(".cell.last.working").classList.remove("working");
            if (json.hasOwnProperty("error")){
                var errs = document.getElementById("uploader_errors");
                errs.innerHTML = json['error'];
                errs.style.display = "block";
                return;
            }
            json['files'].forEach(function (e, i){
                var selCell = FT.add_cell();
                var img = selCell.getElementsByTagName("img")[0];
                img.setAttribute("src", e);
            });
        }, function (err){
            console.log("Uploading files FAILED:", err);
        });
    }

    this.flex_table = function(){ return FT; }

    function create_hiden_file_input(){
        var input = document.createElement('input');
        FT.table.getElementsByClassName("adder")[0].appendChild(input);
        input.setAttribute("type","file");
        input.setAttribute("name","fileForUpload");
        input.style.display = "none";
        input.addEventListener("change", function (ev){
            self.file_selection_done(input);
        });
        return input;
    }

    this.mark = function(event, el){
        var cell = event.target.parentNode.parentNode;
                [].forEach.call(cell.parentNode.getElementsByClassName("default"),
                    function(e, i){ e.classList.remove("default");});
        cell.classList.add("default");
        event.stopPropagation();
    }

    this.remove = function(event, el){
        var cell = event.target.parentNode.parentNode;
        cell.parentNode.removeChild(cell);
        event.stopPropagation();
    }

    this.done = function(event, el){
        var data = grab();
        //console.log(data);
        self.show(false);
        if (self.onDone) self.onDone(data);
    }

    /*
    function init(){
        document.getElementById(self.id).addEventListener("click", function(event){
            if (event.target.classList.contains("marker")){
                var cell = event.target.parentNode.parentNode;
                [].forEach.call(cell.parentNode.getElementsByClassName("default"),
                    function(e, i){ e.classList.remove("default");});
                cell.classList.add("default");
            }
            else if (event.target.classList.contains("remover")){
                var cell = event.target.parentNode.parentNode;
                cell.parentNode.removeChild(cell);
            }
            else if (event.target.classList.contains("done")){
                var data = grab();
                //console.log(data);
                self.show(false);
                if (self.onDone) self.onDone(data);
            }
        }, true);
    }
    */

    function grab(){
        var files = Array();
        var def_src = "", def_ix = -1;
        [].forEach.call(FT.table.querySelectorAll(".cell img"), function (e, i){
            if (i === 0) return;
            if (def_ix === -1 && e.parentElement.classList.contains("default")){
                def_ix = i - 1; //first is the model cell
                def_src = e.src;
            }
            files.push(e.src);
        });
        var modified = files !== initial_data["files"]
                || def_src !== initial_data["def"]
                || def_ix !== initial_data["def_ix"];
        return {
            files: files,
            def: def_src,
            def_ix: def_ix,
            modified: modified
        }
    }

    this.fill = function(data){
        FT.clear();
        var count = data["files"].length;
        var def_ix = data["def_ix"];
        for (var i = 0; i < count; i++){
            var selCell = FT.add_cell();
            var img = selCell.getElementsByTagName("img")[0];
            var p = data["files"][i];
            img.setAttribute("src", p);
            if (i === def_ix){
                selCell.classList.add("default");
            }
        }
        initial_data = data;
    }

    this.show = function(flag, data){
        if (flag) document.body.classList.add("modal-open");
        else document.body.classList.remove("modal-open");
        document.getElementById('uploader_win').style.display = flag ? 'block' : 'none';
    }
}

var __uploader_table_insts = {};
function UploaderTableInst(id){
    if (!__uploader_table_insts[id]){
        __uploader_table_insts[id] = new UploaderTable(id);
    }
    return __uploader_table_insts[id];
}

//------------------------------------------------------------------------------
function UploaderProcess(){
    var __page = "/uploader/uploader.php";

    this.upload_files__fetch = function(input_element){
        var inp = input_element;
        var fd = new FormData();
        for (var i = 0; i < inp.files.length; i++){
            fd.append(i, inp.files[i]);
            //console.log(i, inp.files[i]);
        }
        fd.append('SOME_CONTENT__ULF', inp.files.length);
        return fetch(__page, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
        })
        .then(function(resp){
            if (resp.status !== 200){
                throw resp.status + ' ' + resp.statusText;
            }
            return resp.json();
        })
        .catch (function(e){console.log("ERROR:",e)});
    }
}

var __uploader_proc_inst;
function UploaderProcessInst(){
    if (!__uploader_proc_inst)
        __uploader_proc_inst = new UploaderProcess();
    return __uploader_proc_inst;
}

//==============================================================================
//TESTS
var TEST_PICS = [
    "URSS.png",
    "MADMAX.jpg",
    "EUROPE.png",
    "LH.jpg",
    "COW.png",
    "BOAT.jpg",
    "BIA.png"
];
function get_random_test_pic() {
    return "./testpics/" + TEST_PICS[Math.floor(Math.random() * TEST_PICS.length)];
}
function test__random_inserts(target_id, count){
    var UT = UploaderTableInst(target_id)
    while(count--){
        var selCell = UT.flex_table().add_cell();
        //selCell_content = selCell.getElementsByClassName("content")[0];
        //selCell_content.innerHTML = input.value;
        var img = selCell.getElementsByTagName("img")[0];
        img.setAttribute("src", get_random_test_pic());
    }
}