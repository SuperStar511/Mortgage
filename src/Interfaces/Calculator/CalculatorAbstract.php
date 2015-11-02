<?php

namespace FinanCalc\Interfaces\Calculator {

    use Exception;
    use FinanCalc\Interfaces\Serializer\SerializerInterface;

    /**
     * Interface CalculatorAbstract
     * @package FinanCalc\Interfaces
     */
    abstract class CalculatorAbstract {
        protected $propResultArray = null;

        /**
         * @param $name
         * @param $value
         * @param null $callbackBefore
         * @throws Exception
         */
        protected final function setProperty($name, $value, $callbackBefore = null) {
            if (is_callable($callbackBefore)) {
                $callbackBefore($value);
            }

            if (property_exists($this, $name)) {
                if (is_object($value) || is_null($value)) {
                    $this->$name = $value;
                } else {
                    $this->$name = (string) $value;
                }
                return;
            }

            throw new Exception("The property " . $name . " doesn't exist in the class " . get_class($this) . "!");
        }

        /**
         * @param array $propResultArray
         * @return array
         */
        public final function getResultAsArray(array $propResultArray = null) {
            if ($propResultArray === null) {
                if ($this->propResultArray !== null && is_array($this->propResultArray)) {
                    $propResultArray = $this->propResultArray;
                } else {
                    error_log('$propResultArray has not been supplied neither by the argument, nor by the class field');
                    return false;
                }
            }

            $processArray = function ($inputArray) use (&$processArray) {
                $processedArray = array();
                foreach ($inputArray as $key => $prop) {
                    if (is_string($prop)) {
                        $propGetter = "get" . ucfirst($prop);
                        if (method_exists($this, $propGetter)) {
                            $processedArray[is_string($key) ? $key : $prop] = call_user_func(array($this, $propGetter));
                        } else {
                            error_log("Method '" . $propGetter . "()' doesn't exist in the class " . get_class($this));
                        }
                    }
                    if (is_array($prop)) {
                        $processedArray[$key] = $processArray($prop);
                    }
                }

                return $processedArray;
            };


            return $processArray($propResultArray);
        }

        /**
         * @param SerializerInterface $serializer
         * @return mixed
         */
        public final function getSerializedResult(SerializerInterface $serializer) {
            return $serializer->serializeArray($this->getResultAsArray());
        }
    }
}
