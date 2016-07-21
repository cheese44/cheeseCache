<?php

  namespace cheeseCache\app;

  use cheeseCache\app\exceptions\InvalidCacheParameter;
  use cheeseCache\app\exceptions\InvalidCollisionMode;
  use cheeseCache\interfaces as cheeseInterfaces;

  /**
   * @author cheese44
   */
  class Cache implements cheeseInterfaces\ICache {

    /**
     * this key is a reserved value for $cacheParams
     * and is used for branching the cache paths from the actual cache values
     */
    const RESERVED_CACHE_KEY = 'reserved_cache_key';

    private $cache = array();
    private $collisionMode = self::COLLISION_MODE_IGNORE;
    private $debug = false;
    private $memoryLimit = 0;

    /**
     * @param array          $cacheParams
     * @param callable|mixed $cacheable  //if callable, method must return value to be cached.
     * @param bool           $renewCache //cached value will be overwritten if true
     *
     * @return mixed
     */
    public function cache($cacheParams, $cacheable, $renewCache = false) {
      $cacheParams = (array)$cacheParams;
      
      $this->validateCacheParameters($cacheParams);

      if(!$renewCache && $this->isCacheSet($cacheParams)):
        $value = $this->geCacheValue($cacheParams);
      else:
        if(is_callable($cacheable)):
          $value = $cacheable();
        else:
          $value = $cacheable;
        endif;

        $this->setCacheValue($cacheParams, $value);
      endif;

      return $value;
    }

    /**
     * @param array $cacheParams
     */
    public function clearCache($cacheParams = array()) {
      $cacheParams = (array)$cacheParams;

      $this->validateCacheParameters($cacheParams);

      if(empty($cacheParams)):
        $this->cache = array();
      else:
        $cache = &$this->cache;
        while(!empty($cacheParams)):
          $cacheParam = array_shift($cacheParams);

          $cache = &$cache[$cacheParam];
        endwhile;

        unset($cache[self::RESERVED_CACHE_KEY]);
      endif;
    }

    /**
     * @return array
     */
    public function getValidCollisionModes() {
      return array(
        self::COLLISION_MODE_IGNORE,
        self::COLLISION_MODE_ERROR,
        self::COLLISION_MODE_LOG
      );
    }

    /**
     * @param int $mode
     *
     * collision mode will only take effect when debugging is activated
     */
    public function setCollisionMode($mode = self::COLLISION_MODE_IGNORE) {
      $this->validateCollisionMode($mode);

      $this->collisionMode = $mode;
    }

    /**
     * @param bool $debug
     */
    public function setDebugging($debug = false) {
      $this->debug = (bool)$debug;
    }

    /**
     * @param int $memoryLimit
     * memory limit in MB that will be applied to the object.
     * the cache will try not to occupy more memory by deleting previously cached values.
     *
     * if the limit is set to 0 no limitation will be applied
     */
    public function setMemoryLimit($memoryLimit = 0) {
      $this->memoryLimit = (int)$memoryLimit;
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    private function cleanValue($value) {
      if(is_object($value)):
        $value = clone $value;
      endif;

      return $value;
    }

    /**
     * @param $cacheParams
     *
     * @return mixed
     */
    private function geCacheValue($cacheParams) {
      if($this->isCacheSet($cacheParams)):
        
        $cacheParams[] = self::RESERVED_CACHE_KEY;
        $cache = $this->cache;
        while(count($cacheParams) != 0):
          $cacheParam = array_shift($cacheParams);

          $cache = $cache[$cacheParam];
        endwhile;
      else:
        $cache = null;
      endif;

      $cleanValue = $this->cleanValue($cache);

      return $cleanValue;
    }

    /**
     * @param $cacheParams
     *
     * @return bool
     */
    private function isCacheSet($cacheParams) {
      $cache = &$this->cache;

      $cacheParams[] = self::RESERVED_CACHE_KEY;
      
      while(!empty($cacheParams)):
        $cacheParam = array_shift($cacheParams);

        if(!isset($cache[$cacheParam])):
          return false;
        else:
          $cache = &$cache[$cacheParam];
        endif;
      endwhile;

      return true;
    }

    /**
     * @param $cacheParams
     * @param $value
     */
    private function setCacheValue($cacheParams, $value) {
      $cache = &$this->cache;
      while(!empty($cacheParams)):
        $cacheParam = array_shift($cacheParams);

        $cache = &$cache[$cacheParam];
      endwhile;

      $value = $this->cleanValue($value);

      $cache[self::RESERVED_CACHE_KEY] = $value;
    }

    private function validateCacheParameters($cacheParams) {
      if(in_array(self::RESERVED_CACHE_KEY, $cacheParams)):
        throw new InvalidCacheParameter(self::RESERVED_CACHE_KEY);
      endif;
    }

    /**
     * @param int $mode
     *
     * @throws InvalidCollisionMode
     */
    private function validateCollisionMode($mode) {
      if(!in_array($mode, $this->getValidCollisionModes())):
        throw new InvalidCollisionMode($mode);
      endif;
    }
  }