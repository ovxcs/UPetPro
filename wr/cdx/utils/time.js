function server_ts_now(){
    //var ts = parseInt(await ( await (fetch('utils/time.php'))).text());
    //console.log("server_ts", ts, typeof (ts));
    return fetch('/utils/time.php')
    .then(function(r){ return r.text() })
    .then(function(t){ return parseInt(t) });
}

function __local_dt_from_server_ts(sts, lang, _sts_now){
    let _sts = parseInt(sts);
    let _lts_now = (+new Date())/1000;
    let _lst = _lts_now - _sts_now + _sts;
    let dt = new Date(_lst * 1000);
    if (lang) return translate_dt(dt, lang);
    return dt;
}

function local_dt_from_serv_ts(sts, lang, sts_now){
    if (sts_now)
        return __local_dt_from_server_ts(sts, lang, sts_now);
    else
        return server_ts_now()
        .then(function (now) {
            return __local_dt_from_server_ts(sts, lang, now)});
}

function __eta_from_server_ts(sts, _sts_now){
    if (!sts) sts = (+new Date())/1000;
    let _sts = parseInt(sts);
    let eta = Math.abs(_sts - _sts_now);//usually _sts should be bigger than sts_now;
    let Ds = Math.floor(eta / 3600 / 24);
    let h = Math.floor((eta - Ds*24*3600)/3600);
    let hs = Math.floor(eta / 3600);
    let m = Math.floor((eta - hs*3600)/60);
    if (m < 10) m = '0' + m;
    let s = Math.floor(eta - hs*3600 - m*60);
    if (s < 10) s = '0' + s;
    let x;
    if (hs > 24){
        x = ' ' + Ds + 'D ' + h + 'h';
    }
    else if (hs > 3)
        x = ' ' + hs + 'h ' + m + 'm';
    else
        x = ' ' + hs + ':' + m + ':' + s; 
    if (_sts < _sts_now) x = '-' + x; //past, already accompl.
    return x;
}

function eta_from_server_ts(sts, sts_now){
    if (sts_now)
        return __eta_from_server_ts(sts, sts_now);
    else
        return server_ts_now()
        .then(function (now) {
            __eta_from_server_ts(sts, now)});
}
//-------------------------------------------------------------------------------------------------
DAYS = {
    'de':['Sonntag','Montag', 'Dienstag', 'Mittwoch','Donnerstag','Freitag','Samstag'],
    'hu':['hétfő','kedd','szerda','csütörtök','péntek','szombat','vasárnap'],
}

DAYS_ABR = {
    'en':['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
    'ro':['Du', 'Lu', 'Ma', 'Mi', 'Joi', 'Vi', 'S.'],
    'hu':['Vas.', 'H.', 'K.', 'Sze.', 'Csüt.', 'P.', 'Szo.'],
    'fr':['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],
    'de':['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
    'pt':['2ª', '3ª', '4ª', '5ª', '6ª', 'Sá', 'Do'],
    'es':['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
}

MONTHS = {
    'en':['January','February','Mars','April','May','June','July','August','September','Octomber','November','December'],
    'ro':['ianuarie','februarie','martie','aprilie','mai','iunie','iulie','august','septembrie','octombrie','noiembrie','decembrie'],
    'hu':['január','február','március','április','május','június','július','augusztus','szeptember','október','november','december'],
    'de':['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
}

function translate_dt(dt, lang){
    if ((typeof(dt)=== 'string' && (!isNaN(dt))) || typeof(dt) === 'number'){//is number
        dt = new Date(parseInt(dt) * 1000);
    }
    var ts = Math.floor(dt.getTime()/1000);
    if (!lang) lang = 'en';
    var dts = dt.toString();
    var m = dt.getMonth();
    var d = dt.getDay();
    var D = dt.getDate();
    var y = dt.getFullYear();
    var t = dt.toTimeString().substring(0, 8);
    var m_name = MONTHS[lang] ? MONTHS[lang][m].substr(0, 3) : m;
    var now = new Date();
    var same_day = Math.abs(now - dt) < 24 * 3600 * 1000;
    var same_year = (now.getFullYear() === y);
    var date_str = DAYS_ABR[lang][d] + ',&nbsp;' + D + ' ' + m_name;
    if (!same_year){
        date_str += ' ' + y;
    }
    var str;
    if (same_day){
        str = t + ' ' + date_str;
    }else{
        str = date_str + '&nbsp;&nbsp;&nbsp;' + t;
    }
    /*dt.__str__ = str;
    dt.__ts__ = ts;*/
    return [str, ts];
}