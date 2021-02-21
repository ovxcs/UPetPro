//require ../utils/utils.js
//require ../lang/lang.js

function load_commons(){
    console.log("loading commons ... ");
    var body = document.querySelector('body');
    var top_holder = document.createElement('DIV');
    body.insertBefore(top_holder, body.firstChild);
    var topbar_holder = document.createElement('DIV');
    body.insertBefore(topbar_holder, top_holder.nextSibling);//insertAfter
    body.style.zIndex = 100;
    var bottom_holder = document.createElement('DIV');
    body.insertBefore(bottom_holder, body.lastChild.nextSibling);
    load_chain([
        //["/cdx/common/topbar.html", topbar_holder],
        ["/cdx/common/default__top.html", topbar_holder],
        //["/cdx/common/top.html", top_holder],
        ["/cdx/common/bottom.html", bottom_holder]
    ], 0, function(){
        console.log(" common components loaded");
    });
}

window.addEventListener('load', function(event){
    load_commons();
});
