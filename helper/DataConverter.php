<?php

namespace Access2Me\Helper;


/**
 * Converts input data according to rules
 *
 * @package Access2Me\Helper
 */
class DataConverter
{
    /**
     * @example 'xx' => srcKey = dstkey = 'xx', conv = 'strval', def = null
     * @example ['src' => 'xx', 'dst' => 'yy', 'conv' => 'intval', def = 123] => srcKey = 'xx', dstkey = 'yy', conv = 'intval', def = 123
     * @var array
     */
    protected $rules;

    public function __construct($rules)
    {
        $this->rules = $rules;
    }

    /**
     * Convert data
     * Default conv function is strval
     *
     * @param $data
     * @param null $dstObject
     * @return array|object object if $dstObject is not null
     */
    public function convert($data, $dstObject = null)
    {
        $converted = [];
        foreach ($this->rules as $rule) {
            // get params
            if (is_string($rule))
                $srcKey = $rule;
            else if (isset($rule['src']))
                $srcKey = $rule['src'];
            else
                continue;       // invalid input

            $dstKey = isset($rule['dst']) ? $rule['dst'] : $srcKey;
            $converter = isset($rule['conv']) ? $rule['conv'] : 'strval';
            $def = isset($rule['def']) ? $rule['def'] : null;

            // convert value
            $value = isset($data[$srcKey]) ? $data[$srcKey] : $def;
            $converted[$dstKey] = call_user_func($converter, $value);
        }

        // fill object
        if ($dstObject) {
            foreach ($converted as $key=>$val) {
                $dstObject->{$key} = $val;
            }

            return $dstObject;
        }

        return $converted;
    }
}
