
function Top_toggler(big_selector, small_selector, items_to_exchange, callback){
    this.self = this;

    const ARROW_UP = '&#xFE3F';
    const ARROW_DOWN = '&#xFE40';

    var __top_state = 1;
    var __top_big_height = '270px';
    var __top_big_max_height = '300px';
    var __top_small_y = 0;
    var __clones_added = false;
    var __small_top_empty_space;
    var big_top = document.querySelector(big_selector);
    var small_top = document.querySelector(small_selector);
    function toggle_top(){
        if (!__top_big_height) return;
        __top_state = 1 / (__top_state * 0.5);

        callback(__top_state, big_top, small_top);

        if(!__clones_added && items_to_exchange){
            items_to_exchange.forEach((e,i)=>{
                if (!Array.isArray(e)) return;
                var c = big_top.querySelector(e[1]).cloneNode(true);
                small_top.querySelector(e[0]).appendChild(c);
            });
            __clones_added = true;
        }

        if (__top_state == 1){
            //big_top.style.height = null;//__top_big_height;
            big_top.style.maxHeight = __top_big_max_height;
            small_top.style.top = __top_small_y;
            var tt_switch = small_top.querySelector('.top_toggler');
            if (tt_switch){
                tt_switch.innerHTML = ARROW_UP;
                tt_switch.style.padding = "6px 10px 9px 10px";
            }
        }else{
            /*__top_big_height = window.getComputedStyle(
                big_top, null).getPropertyValue('height');
                */
            big_top.style.position = null;
            big_top.style.zIndex = null;
            //big_top.style.height = 0;
            //big_top.style.maxHeight = 0;//__top_big_max_height;
            small_top.style.top = 0;
            var tt_switch = big_top.querySelector('.top_toggler');
            if (tt_switch){
                tt_switch.innerHTML = ARROW_DOWN;
                tt_switch.style.padding = "3px 10px 1px 10px";
                tt_switch.style.marginTop = "2px";
            }
            if (!__small_top_empty_space){
                var sh = window.getComputedStyle(small_top, null)
                    .getPropertyValue('height');
                __small_top_empty_space = 1*Math.ceil(sh.substring(0,sh.length-2))+'px';
            }

        }
        if (items_to_exchange){
            //setTimeout(() => {
                items_to_exchange.forEach(sel => {
                    if (Array.isArray(sel)) return;
                    var s, d;
                    if (__top_state == 1){
                        s = small_top; d = big_top;
                    }else{
                        s = big_top; d = small_top;
                    }
                    d.querySelector(sel).appendChild(
                        s.querySelector(sel).firstElementChild);
                        
                });
            //},25);
        }
    }

    this.user_toggle_top = function(){
        window.removeEventListener("scroll", on_scroll);
        toggle_top();
        if (__top_state == 1){
            var st = document.documentElement.scrollTop || document.body.scrollTop;
            if (st > 20){
                big_top.style.position = 'fixed';
                big_top.style.zIndex = window.getComputedStyle(
                    small_top, null).getPropertyValue('z-index');
            }
        }else{//(__top_state == 2)
            var test = __small_top_empty_space;
            console.log(test);
            big_top.style.maxHeight = test;
        }
        setTimeout(() => {
            window.addEventListener("scroll", on_scroll);
        }, 2000);
    }
    const __user_toggle_top = this.user_toggle_top;
    function on_scroll(){
        var sh = document.documentElement.scrollHeight;
        //console.log("****", sh, window.innerHeight);
        if (__top_state == 1){
            if (sh < window.innerHeight + 500) return;
        }
        window.removeEventListener("scroll", on_scroll);
        var st = document.documentElement.scrollTop || document.body.scrollTop;
        if (st > 200){
            if (__top_state == 1){
                toggle_top();
            }
        }else if(st < 200){
            if (__top_state == 2){
                toggle_top();
            }
        }
        window.addEventListener("scroll", on_scroll);
    }

    this.__init__ = function(){
        console.log("calling init");
        //__top_big_height = window.getComputedStyle(big_top, null).getPropertyValue('height');
        //console.log(__top_big_height); //hardcode this value above; 
        //big_top.style.height = __top_big_height;
        big_top.style.maxHeight = __top_big_max_height;
        __top_small_y = window.getComputedStyle(small_top,
                null).getPropertyValue('top');
        [].forEach.call(document.querySelectorAll('.top_toggler'), (e,i)=>{
            e.innerHTML = ARROW_UP;
            e.addEventListener('click', __user_toggle_top);
        });
    }

    window.addEventListener("scroll", on_scroll);

    this.__init__();
    //window.addEventListener('load', () => { setTimeout(init, 1000);});

}






