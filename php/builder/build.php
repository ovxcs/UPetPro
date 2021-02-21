<?php

/*
Creates full pages from common and specific "submodules" and eliminate meta code
uses "files.json" - see exemples of "files.json" inside builds
*/

class MyDOMDoc{

private $head = 0;

function __construct($file_path, &$pool = null){
    $this->doc = new DOMDocument();

    $this->doc->preserveWhiteSpace = false;
    $this->doc->formatOutput = true;

    $this->doc->loadHTMLFile($file_path);
    $this->file_path = $file_path;

    $this->dir_path = str_replace('\\','/', realpath(dirname($file_path)));

    $this->root_path = self::root();

    $this->scripts = array(); /*scripts with src*/
    $this->styles = array();
    $this->links = array(); /*links with href*/
    $this->dictionaries = array(); /*meta with class='dictionaries'*/
    if ($pool === null){
        print_r ("creating NEW PO0L");
        $this->pool = array();
    }else{
        $this->pool = &$pool;
    }
    $this->pool[] = $file_path;
    /*all loaded html - transimited to submodules*/
}

static private $__root;
static function root(){
    //always assuming this script in '<httproot>/builder'
    if(!self::$__root)
        self::$__root = str_replace('\\','/', realpath(__DIR__."/../../wr"));
    return self::$__root;
}

function head(){
    if (!$this->head){
        $this->head = $this->doc->getElementsByTagName('head')[0];
    }
    return $this->head;
}

function update_element($one, $another){
    //error_log("\nUPDATE DEST:".$one->nodeName." FROM SRC:".$another->nodeName);
    $to_append = array();
    foreach($another->childNodes as $cn){
        if ($cn->nodeName === 'meta') continue;
        array_push($to_append, $cn);
        //$another->appendChild($cn);
    }
    foreach($to_append as $cn){
        $node = $this->doc->importNode($cn, true);
        $one->appendChild($node);
    }
}

function update($another, $holder){
    $this->import_refs($another);
    $heads = $another->doc->getElementsByTagName('head');
    if(count($heads)){
        $this->update_element($this->head(), $heads[0]);
    }
    $bodies = $another->doc->getElementsByTagName('body');
    $another_body = count($bodies)? $bodies[0] : $another->doc;
    $this->update_element($holder, $another_body);
}

function import_refs($another){
    foreach($another->scripts as $p => $el){
        if (!isset($this->scripts[$p])){
            $node = $this->doc->importNode($el, false);
            $this->scripts[$p] = $node;
        }
    }
    foreach($another->links as $p => $el){
        if (!isset($this->links[$p])){
            $node = $this->doc->importNode($el, false);
            $this->links[$p] = $node;
        }
    }
    foreach($another->styles as $el){
        $el->setAttribute("data-origin_module", basename($another->file_path));
        $node = $this->doc->importNode($el, false);
        $this->styles = $node;
    }
    foreach($another->dictionaries as $p => $el){
        if (!isset($this->dictionaries[$p])){
            $node = $this->doc->importNode($el, false);
            $this->dictionaries[$p] = $node;
        }
    }
}

function rebuild(){
    $head = $this->head();
    foreach($this->links as $ap => $elx){
        $elx->setAttribute("href", $ap);
        $head->appendChild($elx);
        $head->appendChild($this->doc->createTextNode("\n"));
    }
    foreach($this->scripts as $ap => $elx){
        $elx->setAttribute("src", $ap);
        $head->appendChild($elx);
        $head->appendChild($this->doc->createTextNode("\n"));
    }
    foreach($this->dictionaries as $ap => $elx){
        $elx->setAttribute("content", $ap); //modify 'content' to an abs path
        $head->appendChild($elx);
        $head->appendChild($this->doc->createTextNode("\n"));
    }
    foreach($this->styles as $elx){
        $head->appendChild($elx);
        $head->appendChild($this->doc->createTextNode("\n"));
    }
}

function dump($destination){
    if (!$destination) $destination = $this->file_path.".dmp.html";
    $this->rebuild();
    $this->doc->saveHTMLFile($destination);
    $this->last_dump = $destination;
}

function absolute_local_path($path){
    $path = trim($path);
    if (!$path) return $this->dir_path;
    if ($path[0] === '/') return $this->root_path.$path;
    if (substr($path, 0, 4) === 'http') return $path;
    $alp = $this->dir_path.'/'.$path;
    $ralp = realpath($alp);
    if(!$ralp){
        error_log("WARNING: the file `$alp` seems inaccesible");
    }
    return $ralp ? $ralp : $alp;
}

function absolute_remote_path($path){
    $alp = $this->absolute_local_path($path);
    $x = str_replace('\\','/', substr($alp, strlen($this->root_path)));
    return $x;
}

function process_meta(){
    $to_remove = array();
    foreach($this->doc->getElementsByTagName("meta") as $elx){
        $class = $elx->getAttribute("class");
        if (stripos($class, "dictionaries") !== false){
            $path = trim($elx->getAttribute("content"));
            if ($path){
                $arp = $this->absolute_remote_path($path);
                $this->dictionaries[$arp] = $elx;
                array_push($to_remove, $elx);
            }
        }
    }
    foreach($to_remove as $elx) $elx->parentNode->removeChild($elx);
}

function process_components_holders(){
    foreach($this->doc->getElementsByTagName("div") as $elx){
        $class = trim($elx->getAttribute("class"));
        if (stripos($class, "component_holder") !== false){
            $src = trim($elx->getAttribute("data-src"));
            if ($src){
                print_r("\n holder for $src in $this->file_path");
                $alp = $this->absolute_local_path($src);
                if (!in_array($alp, $this->pool)){
                    $comp = new MyDOMDoc($alp, $this->pool);
                    $comp->process();
                    $this->update($comp, $elx);
                    $elx->setAttribute("data-src-ld", $src);
                    $elx->removeAttribute("data-src");
                }else{
                    print_r(" ALREAY PROCESSED. SKIP!");
                }
            }
        }
    }
}

function process_scripts(){
    $to_remove = array();
    foreach($this->doc->getElementsByTagName("script") as $elx){
        $src = $elx->getAttribute("src"); 
        if ($src){
            //error_log($src);
            $arp_src = $this->absolute_remote_path($src);
            $this->scripts[$arp_src] = $elx;
            array_push($to_remove, $elx);
        }
    }
    foreach($to_remove as $elx) $elx->parentNode->removeChild($elx);
}

function process_links(){
    $to_remove = array();
    foreach($this->doc->getElementsByTagName("link") as $elx){
        $href = $elx->getAttribute("href");
        if ($href && $elx->getAttribute("rel") === "stylesheet"){
            //error_log($href);
            $arp_href = $this->absolute_remote_path($href);
            $this->links[$arp_href] = $elx;
            array_push($to_remove, $elx);
        }
    }
    foreach($to_remove as $elx) $elx->parentNode->removeChild($elx);
}

function grab_styles(){
    $to_remove = array();
        $to_remove = array();
    foreach($this->doc->getElementsByTagName("style") as $elx){
            $this->styles[] = $elx;
            $to_remove[] = $elx;
            //$elx->setAttribute("data-origin_module", "this");
    }
    foreach($to_remove as $elx) $elx->parentNode->removeChild($elx);
}

function process(){
    $this->process_meta();
    $this->process_scripts();
    $this->grab_styles();
    $this->process_links();
    $this->process_components_holders();
}

static function build($file_path, $destination, $target, $rel_path){
    $obj = new MyDOMDoc($file_path);
    $obj->process();
    $obj->dump($destination);
    //$dest2 = realpath(__DIR__."/../../builds/$target")."/wr/$rel_path";
    //rc_mkdir(dirname($dest2));
    //copy($destination, $dest2);
    return $obj;
}
}//doc proc. class end

function rc_rmdir($dir){
  $files = array_diff(scandir($dir), array('.','..'));
  foreach ($files as $file){
    $p = "$dir/$file";
    (is_dir($p) && !is_link($p)) ? rc_rmdir($p) : unlink($p);
  }
  return rmdir($dir);
}

function rc_mkdir($dir){
    if (!file_exists($dir)) mkdir($dir, 0777, true);
    return $dir;
}

function clean_outputs_path($path){
    $files = array_diff(scandir($path), array('.','..'));
    $dirs = array();
    foreach($files as $f){
        if(intval($f)) array_push($dirs, $f);
    }
    rsort($dirs, SORT_NUMERIC);
    for($k = count($dirs)-1; $k>=5; $k--){
        rc_rmdir($path.'/'.$dirs[$k]);
    }
}

function build($target, callable $callback = null){
    $out = 'out';
    $target_path = realpath(__DIR__."/../../builds/$target")
        or die("ERROR: The build '$target_path' seems unavaialable. ".error_get_last());
    $files_array = json_decode(file_get_contents("$target_path/HTMLs.json"))
        or die("ERROR: Loading HTMLs.json for '$target' failed");
    $outs = rc_mkdir("$target_path/$out");
    clean_outputs_path($outs);
    $crt_build_number = 'w'.(floor(microtime(true)/3600) - 439100);
    $crt_build_path = rc_mkdir("$outs/$crt_build_number/wr");
    foreach($files_array as $rel_path){
        if ($rel_path[0] === '-'){
            error_log("skip $rel_path");
            continue;
        }
        $page_path = realpath(__DIR__."/../$rel_path")
            or die("ERROR: The source '$rel_path' seems unavailable" );
        error_log("\nprocessing $rel_path ($page_path)");
        $dest = "$crt_build_path/$rel_path";
        rc_mkdir(dirname($dest));
        $obj = MyDOMDoc::build($page_path, $dest, $target, $rel_path);
        if ($callback) call_user_func($callback, $obj, $crt_build_path);
    }
    return $crt_build_path;
}

if (!count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)))
{
    $build_root = build($argv[1]);
    error_log("\nbuild root: ".$build_root);
}




?>