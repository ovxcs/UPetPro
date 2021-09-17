
function PagesSelector(selector){

    var self = this;
    if (typeof selector === 'string')
        this.element = document.querySelector(selector);
    else this.element = selector;
    var arrows = this.element.getElementsByClassName('arrows');
    var DAL = arrows[0].children[0]; //double arrow left;
    var SAL = arrows[0].children[1]; //simple arrow left;
    var SAR = arrows[1].children[0]; //simple arrow right;
    var DAR = arrows[1].children[1]; //double arrow right;
    var _arrows_ids = ['fbw', 'p', 'n', 'ffw'];
    var LST = this.element.getElementsByClassName('list')[0];
    var PPS = this.element.getElementsByClassName('perPage')[0];
    var AGE = this.element.getElementsByClassName('pasts')[0];
    //var TOT = this.element.getElementsByClassName('total')[0];
    var model = LST.children[0].cloneNode(true);
    var total = 0;
    var perPage = 0;
    var offset = 0;
    var query = '';
    var delta = 2;
    var age = 0;
    var fetch_and_update = 0;
    var __related_list_loader_singleton = 0;

    function related_list_loader_singleton(){
        if (!__related_list_loader_singleton){
            var node = self.element.parentNode;
            while (true){
                if (node.hasAttribute('data-list_loader_singleton')){
                    __related_list_loader_singleton = eval(node.getAttribute('data-list_loader_singleton'));
                    break;
                }
                node = node.parentNode;
                if (!node) break;
            }
        }
        return __related_list_loader_singleton;
    }

    function init(){
        LST.innerHTML = "";
        self.element.style.display = 'none';
        related_list_loader_singleton().attach_pages_selector(self);
        self.build(related_list_loader_singleton().pages,
                related_list_loader_singleton().request_list);
    }

    this.build = function(json, updater){
        if (json === 0){
            self.element.style.display = 'none';
            return;
        }
        fetch_and_update = updater;
        //console.log(json);
        total = json[0];
        perPage = json[1];
        offset = json[2];
        query = json[3];
        url = json[4]; // NOT USED
        age = json[5];
        //TOT.innerHTML = total;
        AGE.value = age;
        PPS.value = perPage;
        var delta = window.matchMedia("(max-width: 700px)").matches ? 1 : 2;
        LST.innerHTML = "";
        [].forEach.call(self.element.querySelectorAll('.arrows span'), function(e, i){
            e.style.display = 'none';
            e.setAttribute('data-pagesSelectorItem', _arrows_ids[i]);
            e.onclick = go_to_clicked_page;
        });
        PPS.onchange = go_to_clicked_page;
        var tps = Math.ceil(total/perPage);
        var cp = Math.floor(offset/perPage); // crt. page
        var m = cp - delta, M = cp + delta; //displayed pages indexes limits
        if (m < 0 && M >= tps){ m = 0; M = tps - 1; }
        else if (m < 0) M -= m;
        else if (M >= tps) m -= M-tps+1;
        if (m < 0) m = 0;
        if (M >= tps) M = tps - 1;
        //if (m > 0)
            SAL.style.display = "inline";
        //if (M < tps - 1)
            SAR.style.display = "inline";
        for (var i=m; i<=M; i++){
            var c = model.cloneNode(true);
            LST.appendChild(c);
            c.innerHTML = i + 1;
            c.setAttribute('data-pagesSelectorItem', i);
            c.onclick = go_to_clicked_page;
            if (i === cp){
                c.classList.add('current');
            }
        }
        self.element.style.display = 'flex';
    }

    function resize_page_length(evt){
        var el = evt.target;
        fetch_and_update(
            {
                q:query
            }, {
                cpp: perPage,       //current items per page
                cos: offset,        //current start
                pp: el.value,             //desired items per page
                age: age,
                pg: 'c'         //desired page
            }
        );
    }

    function modify_results_ages(evt){
        var el = evt.target;
        fetch_and_update(
            {
                q:query
            }, {
                cpp: perPage,       //current items per page
                cos: offset,        //current start
                pp: perPage,             //desired items per page
                age: el.value,
                pg: 'c'         //desired page
            }
        );
    }

    function go_to_clicked_page(evt){
        var el = evt.target;
        //el.style.color = 'red';
        var selId = el.getAttribute('data-pagesSelectorItem');
        self.go_to_page(selId);
    }
    this.go_to_page = function(page_id){
        //console.log("**", page_id);
        fetch_and_update(
            {
                q:query
            }, {
                cpp: perPage,       //current items per page
                cos: offset,        //current start
                pp: PPS.value,      //desired items per page
                pg: page_id,         //desired page
                age: AGE.value
            }
        ).then(function (arg){
                //console.log("PAGE UPDATE DONE", arg);
            }, function (err){
                console.log("ERROR - PAGE UPDATE FAILED:", err);
            }
        );
    }
    init();
}

var __pagesSelector_insts;
var __pagesSelector_insts_counter;
function PagesSelectorInst(el){
    if (!__pagesSelector_insts) __pagesSelector_insts = {};
    if (!__pagesSelector_insts_counter) __pagesSelector_insts_counter = 0;
    if (typeof el === 'string') el = document.querySelector(el);
    
    if (!el.hasAttribute('data-pagesSelector_id')){
        el.setAttribute('data-pagesSelector_id',
            ++__pagesSelector_insts_counter);
    }
    
    var id = 'id'+ el.getAttribute('data-pagesSelector_id');
    
    if (!__pagesSelector_insts.hasOwnProperty(id)){
        __pagesSelector_insts[id] = new PagesSelector(el);
    }
    
    return __pagesSelector_insts[id];
}

window.addEventListener('load', function(){
    [].forEach.call(document.getElementsByClassName('___pagesSelector___'), function (e, i){
        PagesSelectorInst(e);
    });

});




