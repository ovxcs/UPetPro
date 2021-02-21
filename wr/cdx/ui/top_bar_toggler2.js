function TopBarToggler(middle_selector, top_selector, items_to_exchange, callback){
    var mid_el = document.querySelector(middle_selector);
    var top_el = document.querySelector(top_selector);
    var __aprox_top_height = top_el.style.top;
    var content = document.querySelector('#__bar_widget__');
    var treshold = -100;
    var th_delta = 50; //anti-trembling
    __status = 1;
    var __is_disabled = -7;
    function is_disabled(){
        if (__is_disabled === -7)
            __is_disabled = getComputedStyle(
                    document.documentElement).getPropertyValue('--top_bar_toggler') === 'disabled';
            return __is_disabled;
    }
    function ev_handler(){
        if (is_disabled()) return;
        var prev_stat = __status;
        var bcr = mid_el.getBoundingClientRect();
        if (bcr.top < (treshold - th_delta) && __status !== -1){
            //console.log("top menu bar should appear");
            toggle(mid_el, top_el);
            top_el.style.top = 0;
            __status = -1;
        }else if (bcr.top >= (treshold + th_delta) && __status !== 1){
            //console.log("in page menu bar should appear");
            toggle(top_el, mid_el);
            top_el.style.top = __aprox_top_height; //reset to hidden position
            __status = 1;
        }
        if (prev_stat != __status){
            if (callback) callback(__status);
        }
    }
    function toggle(src, dest){
        dest.appendChild(content);
    }
    function evHnd(){
        ev_handler();
    }
    if (is_disabled()) return;
    //VERY IMPORTANT !!! DO NOT ADD 'window.' here because it will be replaced by loaders
    addEventListener('DOMContentLoaded', evHnd, false);
    addEventListener('load', evHnd, false);
    addEventListener('scroll', evHnd, false);
    addEventListener('resize', evHnd, false);
}
window.addEventListener('load', function(){
    //default - MUST BE USED WHEN USED INSIDE COMPONENT PAGE
    var tbt = new TopBarToggler('#middle__bar_widget_holder', '#top__bar_widget_holder', [], 
        function (stat){
            document.querySelector('#bar_widget_logo').style.display = (stat === -1) ? '' : 'none';
        });
});










