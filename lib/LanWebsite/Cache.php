<?

class LanWebsite_Cache {

    public static $server;
    private static $init = false;

    public static function init() {
        if (self::$init) return;
        self::$server = new Memcached();
        self::$server->addServer('127.0.0.1', 11211);
        self::$init = true;
        
        if(isset($_GET['clearcache']) && $_GET['clearcache']) {
            self::$server->flush();
        }
    }
    
    public static function set($group, $elem, $value, $exp=0) {
        self::init();
        $key = md5($group . '.' . $elem);
        self::$server->set($key, $value, $exp);
    }
    
    public static function get($group, $elem, &$return) {
        self::init();
        $key = md5($group . '.' . $elem);
        $return = self::$server->get($key);
        return (self::$server->getResultCode() != Memcached::RES_NOTFOUND);
    }
    
    public static function delete($group, $elem) {
        self::init();
        $key = md5($group . '.' . $elem);
        self::$server->delete($key);
    }

}

?>