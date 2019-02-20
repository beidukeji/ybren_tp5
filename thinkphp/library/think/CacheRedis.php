<?php
/**
 * Created by 钟文宇
 * Time: 2017/5/4 16:47
 */
namespace think;

/**
 * Redis缓存类
 * 要求安装phpredis扩展
 */

class CacheRedis{
    protected $options = array(
        'host'       => '192.168.100.123',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 1,
        'expire'     => 3600,
        'persistent' => false,
        'prefix'     => '',   // 测试环境加ceshi
    );
    protected $handler;  // 当前操作句柄
    protected $prefix = '';

    /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = array())
    {
        if (!extension_loaded('redis')) {
            throw new \Exception('not support: redis');
        }

        if(empty($options)){
            $options =  Config::iniGet('redisConnect');
        }else{
            $options = array_merge($this->options, $options);
        }

        $this->options = $options;
        $func = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler = new \Redis;
        $this->handler->$func($options['host'], $options['port'], $options['timeout']);

        if ('' != $options['password']) {
            $this->handler->auth($options['password']);
        }
        if (isset($options['select']) && 0 != $options['select']) {
            $this->handler->select($options['select']);
        }
    }

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has($name)
    {
        return $this->handler->get($this->getCacheKey($name)) ? true : false;
    }

    /**
     * 获取原始数据
     * @param $name
     * @return mixed
     */
    public function getUnChange($name){
        return $this->handler->get($this->getCacheKey($name));
    }

    /**
     * 保存原始数据
     * @param $name
     * @return mixed
     */
    public function setUnChange($name , $value , $expire = null){
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $key = $this->getCacheKey($name);
        if (is_int($expire) && $expire) {
            $result = $this->handler->setex($key, $expire, $value);
        } else {
            $result = $this->handler->set($key, $value);
        }
        return $result;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get($name, $default = false)
    {
        $value = $this->handler->get($this->getCacheKey($name));
        if (is_null($value)) {
            return $default;
        }
      //  $jsonData = json_decode($value, true);
        // 检测是否为JSON数据 true 返回JSON解析数组, false返回源数据 byron sampson<xiaobo.sun@qq.com>
     //   return (null === $jsonData) ? $value : $jsonData;
        return msgpack_unpack($value);
    }

    /**
     * 写入缓存
     * @access public
     * @param string    $name 缓存变量名
     * @param mixed     $value  存储数据
     * @param integer   $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        if (is_null($expire) || empty($expire)) {
            $expire = $this->options['expire'];
        }
        $key = $this->getCacheKey($name);
        //对数组/对象数据进行缓存处理，保证数据完整性  byron sampson<xiaobo.sun@qq.com>
      //  $value = (is_object($value) || is_array($value)) ? msgpack_pack($value) : $value;
        $value = msgpack_pack($value);
        if (is_int($expire) && $expire) {
            $result = $this->handler->setex($key, $expire, $value);
        } else {
            $result = $this->handler->set($key, $value);
        }
        return $result;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public function inc($name, $step = 1)
    {
        $key = $this->getCacheKey($name);
        return $this->handler->incrby($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public function dec($name, $step = 1)
    {
        $key = $this->getCacheKey($name);
        return $this->handler->decrby($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name)
    {
        return $this->handler->delete($this->getCacheKey($name));
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public function clear()
    {
        return $this->handler->flushDB();
    }

    /**
     * 返回句柄对象，可执行其它高级方法
     *
     * @access public
     * @return object
     */
    public function handler()
    {
        return $this->handler;
    }

    /**
     * 获取实际的缓存标识
     * @access public
     * @param string $name 缓存名
     * @return string
     */
    public function getCacheKey($name)
    {
        if(!empty($this->options['prefix']) && strpos($name , $this->options['prefix']) !== 0){
            return $this->options['prefix'] . $name;
        }else{
            return $name;
        }
    }

    public function expire($key , $expire = 3600){
        return $this->handler->expire($this->getCacheKey($key) , $expire);
    }

    public function lPop($key){
        return $this->handler->lPop($this->getCacheKey($key));
    }

    public function incr($key){
        return $this->handler->incr($this->getCacheKey($key));
    }

    public function decr($key){
        return $this->handler->decr($this->getCacheKey($key));
    }

    public function rPush($key , $val){
        return $this->handler->rPush($this->getCacheKey($key) , $val);
    }

    public function hGet($key , $k1){
        return $this->handler->hGet($this->getCacheKey($key) , $k1);
    }

    public function hSet($key , $k1 , $v1){
        return $this->handler->hSet($this->getCacheKey($key) , $k1 , $v1);
    }

    public function hGetAll($key){
        return $this->handler->hGetAll($this->getCacheKey($key));
    }

    public function hDel($key , $k1){
        return $this->handler->hDel($this->getCacheKey($key) , $k1);
    }

    public function keys($key){
        $cacheKey = $this->getCacheKey($key);
        return $this->handler->keys($cacheKey . '*');
    }

    public function setNx($key , $expire = 300 , $value = 1){
        $cacheKey = $this->getCacheKey($key);
        $result = $this->handler->setNx($cacheKey , $value);
        if($result){
            $this->handler->expire($cacheKey , $expire);
        }
        return $result;
    }
}