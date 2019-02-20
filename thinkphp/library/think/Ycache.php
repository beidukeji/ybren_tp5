<?php
namespace think;

class Ycache{

    public static $handler = null;
    public static $time = null;

    /**
     * @title 初始化
     */
    protected static function init(){
        if(is_null(self::$handler)){
            self::$handler = new \Yac();
            self::$time = time();
        }
    }

    /**
     * set
     * @param $key
     * @param $value
     * @param int $expire
     * @return mixed
     */
    public static function set($key , $value , $expire = 30){
        self::init();

        $data = [
            'value' => $value,
            'expire' => self::$time + $expire
        ];

        return self::$handler->set($key , msgpack_pack($data));
    }

    /**
     * get
     * @param $key
     * @param null $default
     * @return null
     */
    public static function get($key , $default = null){
        self::init();

        $value = self::$handler->get($key);
        if($value === false){
            return $default;
        }

        try{
            $value = msgpack_unpack($value);
        }catch (\Exception $e){
            return $default;
        }

        if($value['expire'] < self::$time){
            self::$handler->delete($key);
            return $default;
        }

        return $value['value'];
    }

    public static function rm($key){
        self::init();

        return self::$handler->delete($key);
    }

    public function flushDb(){
        self::init();

        return self::$handler->flush();
    }
}