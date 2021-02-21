
//------------------------- DROP DOWNS --------------------------------------------------

//--------------------------------
(function() { //pollyfill for CustomEvent
  if ( typeof window.CustomEvent === "function" ) return false;
  function CustomEvent (event, params) {
        params = params || { bubbles: false, cancelable: false, detail: null };
        var evt = document.createEvent( 'CustomEvent' );
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt;
    }
    window.CustomEvent = CustomEvent;
})();
//------------------------

function toggle_dropdown_event_dispatch(el, details){
    if (typeof CustomEvent !== 'undefined'){
        var _event = new CustomEvent('toggle_dropdown_event', { detail: details });
        el.dispatchEvent(_event);
    }
}

function toggle_dropdown(elem, mode){
    //console.log("called with", elem);
    if (elem !== 'all' && typeof elem === 'string') elem = document.querySelector(elem);
    var elem_status = elem === 'all' ? 'unk' : getComputedStyle(elem, null).getPropertyValue('display');
    //var elem_status = elem === 'all' ? 'unk' : elem.classList.contains("hidden_drop_down");
    
    var all_visible = document.querySelectorAll('[data-visible_drop_down="true"]');
    [].forEach.call(all_visible, function(dd_el){
        dd_el.style.display = 'none';
        dd_el.removeAttribute('data-visible_drop_down');
        //dd_el.classList.add("hidden_drop_down");
        toggle_dropdown_event_dispatch(dd_el, {status:'none'});
    });
    window.removeEventListener('click', __hide_all_dropdowns__);
    window.removeEventListener('scroll', __hide_all_dropdowns__);
    //if (all_visible.length)
        
    if (elem === 'all'){
        return; //mode doesn't matter int this case - all just won't be shown at once !!!
    }
    var next_mode = (mode === !!mode) ? mode : (elem_status === 'none') ? 'block' : 'none';
    elem.setAttribute('data-visible_drop_down', 'true');
    //elem.classList.remove("hidden_drop_down");
    elem.style.display = next_mode;
    toggle_dropdown_event_dispatch(elem, {status: next_mode});
    if (next_mode !== 'none'){
        setTimeout(function(){
            window.addEventListener('click', __hide_all_dropdowns__);
            window.addEventListener('scroll', __hide_all_dropdowns__);
        }, 200);
    }
    return false;
}

function __hide_all_dropdowns__(){
    return toggle_dropdown('all', false); 
}

var dropdown_toggle_event;

window.addEventListener('load', function(){
    [].forEach.call(document.querySelectorAll('.dropdown'), function(dd, i){
        dd.style.display = 'none';
    });
});
