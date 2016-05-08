<?php

namespace Directus\Installation\Steps;

use Directus\Installation\Data;

abstract class AbstractStep implements StepInterface
{
    static protected $instance = null;
    /**
     * @var \Directus\Installation\Data
     */
    protected $data;
    protected $isDone = false;
    protected $number;
    protected $name;
    protected $title;
    protected $shortTitle;
    protected $viewName;
    protected $fields = [];
    protected $response;

    public function __construct()
    {
        $this->data = new Data();
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getShortTitle()
    {
        return $this->shortTitle;
    }

    public function getViewName()
    {
        return $this->viewName;
    }

    public function isDone()
    {
        return (bool) $this->isDone;
    }

    public function isPending()
    {
        return ! $this->isDone();
    }

    public function setDone($done = true)
    {
        $this->isDone = (bool) $done;
    }

    public function setData(Data $data)
    {
        $this->data = $data;
    }

    public function getData($key = null)
    {
        if ($key != null) {
            return $this->data ? $this->data->get($key) : null;
        }

        return $this->data ? $this->data->get() : [];
    }

    public function getField($name)
    {
        foreach($this->fields as $field) {
            if ($field['name'] == $name) {
                return $field;
            }
        }

        return false;
    }

    public function validate($data)
    {
        $fields = $this->fields;
        foreach ($fields as $field) {
//            $name = $field['name'];
//            $value = array_key_exists($name, $data) ? $data[$name] : null;
//            if (!$value) {
//                $label = $field['label'];
//                throw new \InvalidArgumentException("The $label parameter is required");
//            }

            if (isset($field['rules'])) {
                foreach(explode('|', $field['rules']) as $rule) {
                    $name = $field['name'];
                    $value = array_key_exists($name, $data) ? $data[$name] : null;

                    $validated = $this->validateRule($field, $data, $rule, $value);
                    if ($validated !== true) {
                        throw new \InvalidArgumentException($validated);
                    }

                    $this->data->set($name, $value);
                }
            }
        }
    }

    protected function validateRule($field, $data, $rule, $value)
    {
        $response = true;

        if (strpos($rule, '|') !== FALSE) {
            list($field, $attribute) = explode('|', $rule);
            $response = call_user_func_array([$this, 'validate'.ucwords($field)], [$field, $value, $attribute]);
        } elseif (preg_match("/(.+)\[(.*)\]/", $rule, $matches)) {
            $matchFieldName = $matches[2];
            if (!($matchField = $this->getField($matchFieldName))) {
                return "$matchField doesn't exists.";
            }

            $matchValue = isset($data[$matchFieldName]) ? $data[$matchFieldName] : null;
            $response = call_user_func_array([$this, 'validateMatch'], [$field, $value, $matchField, $matchValue]);
        } elseif ($rule == 'required') {
            $response = call_user_func_array([$this, 'validateRequired'], [$field, $value]);
        }

        return $response;
    }

    protected function validateRequired($field, $value)
    {
        if ($value) {
            return true;
        }

        return sprintf('%s is required', $field['label']);
    }

    protected function validateEmail($field, $value)
    {
        if ($value && filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {
            return true;
        }

        return sprintf('%s is not a valid email format', $field['label']);
    }

    protected function validateMatch($fieldOne, $valueOne, $fieldTwo, $valueTwo)
    {
        if ($valueOne === $valueTwo) {
            return true;
        }

        return sprintf('%s does not match %s', $fieldOne['label'], $fieldTwo['label']);
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function preRun(&$state)
    {
        return false;
    }

    public function run($formData, $step, &$state)
    {
        $this->response = $response = new StepResponse($formData);

        try {
            if (!is_array($this->fields) || count($this->fields) <= 0) {
                throw new \InvalidArgumentException("{$this->title} fields are empty");
            }

            $this->validate($formData);
        } catch(\Exception $e) {
            $response->setError([
                'message' => $e->getMessage()
            ]);
        }

        return $response;
    }

    public function __get($name)
    {
        return $this->{$name};
    }

    public static function __callStatic($name, $arguments)
    {
        if (static::$instance == null) {
            static::$instance = new static();
        }

        return call_user_func_array([static::$instance, $name], $arguments);
    }
}
