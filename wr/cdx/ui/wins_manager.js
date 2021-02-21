var __managed_win_style = "\
    background:rgba(0,0,0,0.5);width:100%;z-index:99;display:none;\
    position:absolute;left:0;top:0;min-height:100%;overflow-y:visible";

function WindowsManager(){
    var wins = {};
    var stack = [];
    var self = this;

    this.show_win = function(win_sel, close_sel, show_callback){
        console.log("get some win related data and push it to a stack:", win_sel);
        win_el = document.querySelector(win_sel);
        if (!win_el)
            throw "Failed to find 'WINDOW' element for " + win_sel;
        var close_el = null;
        if (close_sel !== null){
            var close_el = win_el.querySelector(close_sel);
            if (!close_el)
                throw "Failed to find 'CLOSE BUTTON' element for " + close_sel;
            close_el.addEventListener('click', self.close_last);
        }
        var __win = {
            sel: win_sel,
            el: win_el,
            close_sel: close_sel,
            close_el: close_el,
            show_callback: show_callback
        }
        hide_stack();
        stack.push(__win);
        win_el.style.display = 'block';
        if (show_callback) show_callback(1);
    }

    this.close_last = function(ev, el){ //close last
        var __win = stack.pop();
        if (__win.close_el != null)
            __win.close_el.removeEventListener('click', self.close_last);
        __win.el.style.display = 'none';
        //hide_stack(false);
        if(stack){
            //redispay previous
            var w = stack[stack.length - 1];
            w.el.style.display = 'block';
        }
        if (__win.show_callback) __win.show_callback(0);
    }

    function hide_stack (flag){//this should be used only with 'true'
        if (flag !== false) flag = true;
        stack.forEach(function(e, i){
            e.el.style.display = flag ? 'block' : 'block';
        });
    }
}

var __windows_manager_inst;
function WindowsManagerInst(){
    if (!__windows_manager_inst) __windows_manager_inst = new WindowsManager();
    return __windows_manager_inst
}
















