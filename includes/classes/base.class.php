<?php

class Base {
    public $__cache;
    
    public $error;
    public $stack_trace = array();
    public $logged_in = false;
    public $redirects = 0;
    
    public $email, $password;
    public $debug = false;
    public $timeout = 8;
    
    public $cookie_file = "../includes/login_cookies.jar";
    public $debug_file = "../includes/debug.log";
    public $stack_trace_file = "../includes/stack_trace.log";
    
    public $runtime = null;
    public $ip = null;
    public $format = "xml";
    public $version = null;
    
    public $errors = array(
        300 => "The key supplied was not valid.",
        301 => "The gamertag supplied was not valid.",
        302 => "The gameid supplied was not valid.",
        303 => "No version specified.",
        304 => "No method specified.",
        500 => "There was an internal server error.",
        501 => "The gamertag supplied does not exist on Xbox Live.",
        502 => "The gamertag supplied has never played this game or does not exist on Xbox Live.",
        600 => "Could not login to Windows Live. Please contact me immediately.",
        601 => "The Windows Live email address supplied was invalid.",
        602 => "The Windows Live password supplied was invalid.",
        603 => "Could not write to the cookie file.",
        604 => "Could not write to the debug file.",
        605 => "Could not write to the stack trace file.",
        606 => "Windows Live caused an infinite redirect loop.",
        607 => "The server timed out when trying to fetch some data."
    );
    
    function __construct($cache) {
        if($cache) $this->__cache = &$cache;
        
        $this->runtime = microtime(true);
        $this->ip = rand(60, 80) . "." . rand(60, 140) . "." . rand(80, 120) . "." . rand(120, 200);
    }
    
    /*!
     * ===
     * Public functions
     * ===
     */
    
    public function init($email, $password) {
        $this->email = str_replace("@", "%40", strtolower($email));
        $this->password = $password;
        
        if($this->check_login()) {
            $this->logged_in = true;
        } else {
            $this->perform_login();
        }
    }
    
    public function output_headers() {
        header("Content-Type: application/" . $this->format . "; charset=utf-8");
    }
    
    public function output_payload($data) {
        $payload = array(
            "status" => "success",
            "version" => $this->version,
            "data" => $data,
            "runtime" => round(microtime(true) - $this->runtime, 3) 
        );
        
        if($this->format == "xml") {
            return output_pretty_xml($payload);
        } else if($this->format == "json") {
            return output_pretty_json(json_encode($payload));
        }
        
        return false;
    }
    
    public function output_error($code) {
        $payload = array(
            "status" => "error",
            "version" => $this->version,
            "data" => array(
                "code" => $code,
                "message" => $this->errors[$code]
            ),
            "runtime" => round(microtime(true) - $this->runtime, 3) 
        );
        
        if($this->format == "xml") {
            return output_pretty_xml($payload);
        } else if($this->format == "json") {
            return output_pretty_json(json_encode($payload));
        }
        
        return false;
    }
    
    /*!
     * ===
     * Protected functions
     * ===
     */
    
    protected function perform_login() {
        if(empty($this->email)) {
            $this->error = 601;
            return false;
        } else if(empty($this->password)) {
            $this->error = 602;
            return false;
        } else {
            $this->add_cookie(".xbox.com", "MC0", time(), "/", time() + (60*60*24*365));
            $this->add_cookie(".xbox.com", "s_vi", "[CS]v1|26AD59C185011B4D-40000113004213F1[CE]", "/", time() + (60*60*24*365));
            $this->add_cookie(".xbox.com", "s_nr", "1297891791797", "/", time() + (60*60*24*365));
            $this->add_cookie(".xbox.com", "s_sess", "%20s_cc%3Dtrue%3B%20s_ria%3Dflash%252011%257Csilverlight%2520not%2520detected%3B%20s_sq%3D%3B", "/", time() + (60*60*24*365));
            $this->add_cookie(".xbox.com", "s_pers", "%20s_vnum%3D1352674046430%2526vn%253D4%7C1352674046430%3B%20s_lastvisit%3D1324587801077%7C1419195801077%3B%20s_invisit%3Dtrue%7C1324589873289%3B", "/", time() + (60*60*24*365));
            $this->add_cookie(".xbox.com", "UtcOffsetMinutes", "60", "/", time() + (60*60*24*365));
            $this->add_cookie(".xbox.com", "xbox_info", "t=6", "/", time() + (60*60*24*365));
            $this->add_cookie(".xbox.com", "PersistentId", "0a652e56e40f42caac3ac84fad02ed01", "/", time() + (60*60*24*365));
            
            $url = "https://login.live.com/login.srf?wa=wsignin1.0&rpsnv=11&ct=" . time() . "&rver=6.0.5286.0&wp=MBI&wreply=https://live.xbox.com:443/xweb/live/passport/setCookies.ashx%3Frru%3Dhttp%253a%252f%252fwww.xbox.com%252fen-US%252f&flc=3d1033&lc=1033&cb=reason=0&returnUrl=http%253a%252f%252fwww.xbox.com%252fen-US%252f%253flc%253d1033&id=66262";
            $result = $this->fetch_url($url, "http://www.xbox.com/en-US/");
            
            $this->stack_trace[] = array(
                "url" => $url,
                "postdata" => $post_data,
                "result" => $result
            );
            
            $this->add_cookie(".login.live.com", "WLOpt", "act=[1]", time() + (60*60*24*365));
            $this->add_cookie(".login.live.com", "CkTst", "G" . time(), "/", time() + (60*60*24*365));
            
            $url = $this->find($result, "urlPost:'", "',");
            $PPFT = $this->find($result, "name=\"PPFT\" id=\"i0327\" value=\"", "\"");
            $PPSX = $this->find($result, "srf_sRBlob='", "';");
            
            //$pwd_pad = "IfYouAreReadingThisYouHaveTooMuchFreeTime"; // I take offence.
            //$pwd_pad = substr($pwd_pad, 0, -(strlen($this->password))); // This was removed in May, 2011 or so. Quite liked it, actually.
            
            $post_data = "login=" . $this->email . "&passwd=" . $this->password . "&KMSI=1&mest=0&type=11&LoginOptions=3&PPSX=" . $PPSX . "&PPFT=" . $PPFT . "&idsbho=1&PwdPad=&sso=&i1=1&i2=1&i3=12035&i12=1&i13=1&i14=323&i15=3762&i18=__Login_Strings%7C1%2C__Login_Core%7C1%2C";
            $result = $this->fetch_url($url, "https://login.live.com/login.srf", null, $post_data);
            
            $this->stack_trace[] = array(
                "url" => $url,
                "post_data" => $post_data,
                "result" => $result
            );
            
            $this->add_cookie(".live.com", "wlidperf", "throughput=3&latency=961&FR=L&ST=1297790188859", "/", time() + (60*60*24*365));
            $this->add_cookie("login.live.com", "CkTst", time(), "/ppsecure/", time() + (60*60*24*365));
            
            $url = $this->find($result, "id=\"fmHF\" action=\"", "\"");
            $NAP = $this->find($result, "id=\"NAP\" value=\"", "\"");
            $ANON = $this->find($result, "id=\"ANON\" value=\"", "\"");
            $t = $this->find($result, "id=\"t\" value=\"", "\"");
            
            $this->add_cookie(".xbox.com", "ANON", $ANON, "/", time() + (60*60*24*365));
            $this->add_cookie(".xbox.com", "NAP", $NAP, "/", time() + (60*60*24*365));
            
            $post_data = "NAPExp=" . date("c", mktime(date("H"), date("i"), date("s"), date("n") + 1)) . "&NAP=" . $NAP . "&ANONExp=" . date("c", mktime(date("H"), date("i"), date("s"), date("n") + 1)) . "&ANON=" . $ANON . "&t=" . $t;
            $result = $this->fetch_url($url, "", 16, $post_data);
            
            $this->stack_trace[] = array(
                "url" => $url,
                "post_data" => $post_data,
                "result" => $result
            );
            
            $result = $this->fetch_url("http://www.xbox.com/en-US/");
            
            if(stripos($result, "currentUser.isSignedIn = true") !== false) {
                $this->logged_in = true;
                
                return true;
            } else {
                $this->error = 600;
                $this->save_stack_trace();
                
                return false;
            }
        }
    }
    
    protected function check_login() {
        if(file_exists($this->cookie_file)) {
            if(time() - filemtime($this->cookie_file) >= 3600 || filesize($this->cookie_file) == 0) {
                $this->empty_cookie_file();
                $this->logged_in = false;
                
                return false;
            } else {
                $this->logged_in = true;
                
                return true;
            }
        } else {
            $this->empty_cookie_file();
            $this->logged_in = false;
            
            return false;
        }
    }
    
    protected function force_new_login() {
        $this->empty_cookie_file();
        $this->logged_in = false;
        $this->perform_login();
        
        if($this->logged_in) {
            return true;
        }
        
        return false;
    }
    
    protected function fetch_url($url, $referer = "", $timeout = null, $post_data = null, $headers = null) {
        if($this->redirects > 4) {
            $this->error = 606;
            return false;
        }
        
        $ip = array(
            "REMOTE_ADDR: " . $this->ip, 
            "HTTP_X_FORWARDED_FOR: " . $this->ip
        );
        
        if($headers) {
            $headers = array_merge($ip, $headers);
        } else {
            $headers = $ip;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");
        curl_setopt($ch, CURLOPT_TIMEOUT, ($timeout) ? $timeout : $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        
        if(!empty($referer)) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        
        if(!empty($post_data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        
        $result = curl_exec($ch);
        
        if(stripos($result, "<title>Object moved</title>") !== false) {
            $follow = urldecode(trim($this->find($result, "<a href=\"", "\">")));
            
            if(substr($follow, 0, 1) == "/") {
                $this->redirects++;
                $result = $this->fetch_url("http://live.xbox.com" . $follow, $url);
            } else {
                $this->redirects++;
                $result = $this->fetch_url($follow, $url);
            }
        } else if(stripos($result, "<!-------Error Info") !== false) {
            $this->force_new_login();
            
            if($this->logged_in) {
                $this->redirects++;
                $result = $this->fetch_url($url, $referer);
            } else {
                $this->error = 600;
                return false;
            }
        } else if(stripos($result, "UserAcceptsNewTermsOfUse") !== false) {
            $follow = "https://live.xbox.com" . $this->find($result, "<form action=\"", "\" method=\"post\">");
            $token = $this->find($result, "<input name=\"__RequestVerificationToken\" type=\"hidden\" value=\"", "\" />");
            $post = "UserAcceptsNewTermsOfUse=true&__RequestVerificationToken=" . urlencode($token);
            
            $result = $this->fetch_url($follow, $url, null, $post);
            $result = $this->fetch_url($url, $referer, $timeout, $post_data, $headers);
        } else {
            $this->save_to_debug("Loaded URL: " . $url);
        }
        
        if(!$result) {
            $this->error = 607;
            return false;
        }
        
        curl_close($ch);
        
        return $result;
    }
    
    protected function find($haystack, $start, $finish) {
        $s = explode($start, $haystack);
        $s = explode($finish, $s[1]);
        return $s[0];
    }
    
    protected function clean($string) {
        $string = html_entity_decode($string, ENT_QUOTES, "UTF-8");
        $string = htmlentities(htmlentities($string, ENT_QUOTES, "UTF-8"));
        
        if(function_exists("mb_convert_encoding")) {
            $string = mb_convert_encoding($string, "UTF-8");
        } else {
            $string = utf8_decode($string);
        }
        
        return $string;
    }
    
    protected function add_cookie($domain, $name, $value, $path = "/", $expires = 0) {
        $file = fopen($this->cookie_file, "a");
        if(!$file) {
            $this->error = 603;
        } else {
            fwrite($file, $domain . "	TRUE	" . $path . "	FALSE	" . $expires . "	" . $name . "	" . $value . "\r\n");
            fclose($file);
        }
    }
    
    protected function empty_cookie_file() {
        $this->logged_in = false;
        
        if(file_exists($this->cookie_file)) {
            $f = fopen($this->cookie_file, "w");
            fclose($f);
        }
    }
    
    protected function save_to_debug($string) {
        $file = fopen($this->debug_file, "a+");
        if(!$file) {
            $this->error = 604;
        } else {
            fwrite($file, "[" . date("Y-m-d H:i:s") . "] (" . $this->version . ") " . $string . "\n");
            fclose($file);
        }
    }
    
    protected function save_stack_trace() {
        $file = fopen($this->stack_trace_file, "w");
        if(!$file) {
            $this->error = 605;
        } else {
            fwrite($file, print_r($this->stack_trace, true));
            fclose($file);
        }
    }
}

function array_filter_recursive($input) { 
    foreach($input as &$value) { 
        if(is_array($value)) {
            $value = array_filter_recursive($value); 
        }
    }
    
    return array_filter($input); 
}

/*!
 * See: http://recursive-design.com/blog/2008/03/11/format-json-with-php/
 */
function output_pretty_json($json) {
    $result = "";
    $pos = 0;
    $strLen = strlen($json);
    $indentStr = "    ";
    $newLine = "\n";
    $prevChar = "";
    $outOfQuotes = true;
    
    for($i = 0; $i <= $strLen; $i++) {
        $char = substr($json, $i, 1);
        if($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;
        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos--;
            for($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }
        
        $result .= $char;
        
        if(($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            
            if($char == '{' || $char == '[') {
                $pos++;
            }
            
            for($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        } else if($char == ":") {
            $result .= " ";
        }
        
        $prevChar = $char;
    }
    
    return $result;
}

/*!
 * See: http://stackoverflow.com/questions/1397036/how-to-convert-array-to-simplexml
 */
function output_pretty_xml($mixed, $xml = false) {
    if($xml === false) {
        $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\"?><xbox status=\"" . $mixed['status'] . "\" version=\"" . $mixed['version'] . "\" />");
    }
    
    foreach($mixed as $key => $value) {
        if(is_numeric($key)) {
            $key = rtrim($xml->getName(), "s");
        }
        
        if(is_array($value)) {
            output_pretty_xml($value, $xml->addChild($key));
        } else {
            $xml->addChild($key, $value);
        }
    }
    
    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    
    return preg_replace_callback('/^( +)</m', function($a) { 
        return str_repeat(' ', intval(strlen($a[1]) / 2) * 4) . "<";  
    }, $dom->saveXML());
}

?>