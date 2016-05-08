<?php

namespace Directus\Installation;

class Data
{
    /**
     * All data attributes
     * @var array
     */
    protected $attributes = [];

    /**
     * Data constructor.
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        foreach($attributes as $key => $attribute) {
            $this->attributes[$key] = $attribute;
        }
    }

    /**
     * Get an attribute value by key or all if key is empty
     * @param null $key
     * @return array|null
     */
    public function get($key = null)
    {
        if ($key != null) {
            return array_key_exists($key, $this->attributes) ? $this->attributes[$key] : null;
        }

        return $this->attributes;
    }

    /**
     * Set a new data attribute
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->attributes[$key] = $value;
    }
}
