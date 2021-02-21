function __load_wip_gui() //work in progres
{
    var wip = document.createElement("div");
    wip.setAttribute('id', '__wip__');
    fetch(coderoot() + '/wip/wip.html')
    .then(function(r){ return r.text();})
    .then(function(txt){
        wip.innerHTML = txt;
        /*if(!document.body){
            document.appendChild(wip);
        }else*/
            document.body.appendChild(wip);
    }).catch(function(e){
        console.log('loading wip failed:', e)
    });
}
window.addEventListener('load', __load_wip_gui);