function TopBarSticker(middle_el, top_el, callback){
    var transitionable_elements = '.menu_buttons';

    var elem, elem_bg, elem_ml;
    var te, te_bg, te_ml;

    var __init;
    function init(a, b){
        if (__init) return;
        elem = document.querySelector(middle_el);
        elem_bg = elem.style.background;
        elem_ml = elem.querySelector(transitionable_elements).style.marginLeft;

        te = document.querySelector(top_el);
        te_bg = te.style.background;
        te_ml = te.querySelector(transitionable_elements).style.marginLeft;
        __init = true;
    }
    init();

    var status = 0;

    var __height;
    function top_height(){
        if (__height === undefined || __height < 10){
            var te_bcr = te.getBoundingClientRect();
            __height = Math.round(te_bcr.bottom - te_bcr.top) + 1;
        }
        return __height;
    }

    function ev_handler2(ev, el){
        var bcr = elem.getBoundingClientRect();
        if (bcr.top < 1 && status !== -1){
            status = -1;
            if (te.style.display === 'none') te.style.display = 'block';
            te.style.top = '0px';
            //te.style.background = te_bg;
            //elem.style.background = te_bg;
            te.querySelector(transitionable_elements).style.marginLeft = te_ml;
            elem.querySelector(transitionable_elements).style.marginLeft = te_ml;
            if (callback) callback(status);
        }
        else if (bcr.top >= 0 && status !== 1){
            status = 1;
            te.style.top = - top_height();
            //te.style.background = elem_bg;
            //elem.style.background = elem_bg;
            te.querySelector(transitionable_elements).style.marginLeft = elem_ml;
            elem.querySelector(transitionable_elements).style.marginLeft = elem_ml;
            if (callback) callback(status);
        }
        //var st = document.documentElement.scrollTop || document.body.scrollTop;
    }

    const ev_handler = ev_handler2;

    addEventListener('DOMContentLoaded', ev_handler, false);
    addEventListener('load', ev_handler, false);
    addEventListener('scroll', ev_handler, false);
    addEventListener('resize', ev_handler, false);
}

