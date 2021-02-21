
function Top_toggler(middle_selector, top_selector, items_to_exchange, callback){
    this.self = this;

    const ARROW_UP = '&#xFE3F';
    const ARROW_DOWN = '&#xFE40';

    const mid_el = document.querySelector(middle_selector);
    const top_el = document.querySelector(top_selector);
    const __aprox_top_height = top_el.style.top;
    const content = document.querySelector('#__bar_widget__');
    var __status = 1;
    
    var __elements = [];
    var __exchange_content = false;
    items_to_exchange.forEach(function(e, i){
    if (e === true){ __exchange_content = true; return;}
        __elements.push(document.querySelectorAll(e));
    });
    
    
    
    function ev_handler2(){
        var prev_stat = __status;
        var bcr = mid_el.getBoundingClientRect();
        if (bcr.top < 1 && __status !== -1){
            //console.log("top menu bar should appear");
            toggle(mid_el, top_el);
            top_el.style.top = 0;
            __status = -1;
        }else if (bcr.top > 1 && __status !== 1){
            //console.log("in page menu bar should appear");
            toggle(top_el, mid_el);
            top_el.style.top = __aprox_top_height; //reset to hidden position
            __status = 1;
        }
        
        if (prev_stat != __status){
            if (items_to_exchange && prev_stat != 0)
                __elements.forEach(function(p, i){
                    if (p.length < 2) return;
                    var src = p[0].innerHTML.length > p[1].innerHTML.length ? src = 0 : 1;
                    var dst = 1 - src;
                    console.log(Math.round((+new Date())/1000), "src:", src, p[src].id, p[src].innerHTML.length,
                        "   DST:", dst, p[dst].id, p[dst].innerHTML.length);
                    console.log(p[src].childNodes.length);
                    var child_nodes = [];
                    [].forEach.call(p[src].childNodes, function(e,j){
                        child_nodes.push(e);
                    });
                    child_nodes.forEach(function (e,j){
                        p[dst].appendChild(e);
                    });
                    p[dst].parentNode.style.visible = 'hidden';
                    p[dst].parentNode.style.visible = '';
                });
                
            if (callback) callback(__status);
        }
    }
    
    function toggle(src, dest){
        if (__exchange_content) dest.appendChild(content);
    }
    
    
    const ev_handler = ev_handler2;

    addEventListener('DOMContentLoaded', ev_handler, false);
    addEventListener('load', ev_handler, false);
    addEventListener('scroll', ev_handler, false);
    addEventListener('resize', ev_handler, false);

}






