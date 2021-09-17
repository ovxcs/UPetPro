function Canvas(id, callback){
    var self = this;
    this.canvas = document.getElementById(id);
    const ctx = canvas.getContext('2d');
    this.image = document.getElementById('testing_img');
    if (this.image === null){
        this.image = new Image();
        this.image.setAttribute('crossOrigin', "");
        this.image.id = 'testing_img';
        this.image.ondragstart = function () { return false };
        this.image.ondragend = function () { return false };
    }
    this.sel = {}; sel_enabled = false;
    this.selections = [];
    var rect = {};

function load_img(draw_selections){
    canvas.width = self.image.naturalWidth;
    canvas.height = self.image.naturalHeight;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(self.image, 0, 0, self.image.width, self.image.height);
    if (draw_selections === true)
        self.selections.forEach(e => { draw_sel(e); });
    else{ //reset
        self.selections = [];
    }
}

this.image.addEventListener('load', e => {
    load_img();
});

canvas.addEventListener('mousedown', mouseDown, false);
canvas.addEventListener('mouseup', mouseUp, false);
canvas.addEventListener('mousemove', mouseMove, false);


function mouseDown(e){
    rect.startX = e.pageX - this.offsetLeft;
    rect.startY = e.pageY - this.offsetTop;
    sel_enabled = true;
}

function mouseUp(){ sel_enabled = false;
    if (rect.w < 0){ rect.w = -rect.w; rect.startX -= rect.w; }
    if (rect.h < 0){ rect.h = -rect.h; rect.startY -= rect.h; }
    self.selections.push(Object.assign(self.sel, rect));
    self.selections.push(Object.assign({}, rect));
    if (callback){ callback(self);}
}

function mouseMove(e) {
    if (! sel_enabled) return;
    rect.w = (e.pageX - this.offsetLeft) - rect.startX;
    rect.h = (e.pageY - this.offsetTop) - rect.startY ;
    load_img(false);
    draw_sel(rect);
}

function draw_sel(r) {
  //ctx.fillRect(rect.startX, rect.startY, rect.w, rect.h);
  ctx.beginPath();
  ctx.rect(r.startX-1, r.startY-1, r.w+2, r.h+2);
  ctx.stroke();
}
return this;
}

function get_img_data(srcEl, xtl, ytl, w, h){
    srcCvs = srcEl.tagName == 'CANVAS' ? srcEl : srcEl.getElementsByTagName('canvas')[0];
    var s_ctx = srcCvs.getContext('2d');
    var w1 = w < 0 ? srcCvs.width : w;
    var h1 = h < 0 ? srcCvs.height : h;
    return s_ctx.getImageData(xtl, ytl, w1, h1);
}

function copy_clip(srcEl, destEl, xtl, ytl, width, height){
    var img_data = get_img_data(srcEl, xtl, ytl, width, height);
    var d_cv = destEl.getElementsByTagName('canvas');
    if (d_cv.length < 1){
        d_cv = document.createElement('canvas');
        destEl.appendChild(d_cv);
    }
    else d_cv = d_cv[0];

    d_cv.width = width;
    d_cv.height = height;

    var d_ctx = d_cv.getContext('2d');
    d_ctx.putImageData(img_data, 0, 0);
}

function process_img(canvas_el){
    var imgd = get_img_data(canvas_el, 0, 0, -1, -1);
    var w = imgd.width, h = imgd.height;
    var rgba = imgd.data;
    var ra = 0, ga = 0, ba = 0, aa = 0;
    var big_r = 0, big_g = 0, big_b = 0;
    for (var px=0, ct=w*h*4; px<ct; px+=4){
        var r = rgba[px  ]; ra+=r;
        var g = rgba[px+1]; ga+=g;
        var b = rgba[px+2]; ba+=b;
        var a = rgba[px+3]; aa+=a;
        //process
    }
    var x = {count : w*h*4, acc_r : ra, acc_g : ga, acc_b : ba, acc_a : aa};
    return x;
}

function process(name, canvas_obj){
    var holder = document.getElementById(name);
    var sel = canvas_obj.sel;
    copy_clip(canvas_obj.canvas , holder,
                sel.startX, sel.startY, sel.w, sel.h
        );
    x = process_img(holder);
    var numbers = holder.getElementsByClassName("numbers");
    if (numbers.length == 0){ numbers = document.createElement('div');
        holder.appendChild(numbers);
        numbers.className = "numbers";
    }else{
        numbers = numbers[0];
    }
    numbers.innerHTML = "R:" + Math.floor(x.acc_r/x.count) +
                        ', G:' + Math.floor(x.acc_g/x.count) +
                        ', B:' + Math.floor(x.acc_b/x.count);
}

function update_image(){
    var url = document.getElementsByTagName('input')[0].value;
    TEST_CANVAS.image.src = url;
}

var TEST_CANVAS;
function on_body_load(){
    TEST_CANVAS = Canvas('main_canvas', function (cobj) { (process('dest__div', cobj))});
    var image_url = 'test.png';
    TEST_CANVAS.image.src = image_url;
    console.log("obl done");

}