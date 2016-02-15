<?php
/**
 * FinanCalc
 *
 * A lightweight, simple and easy PHP library for calculating annuities (e.g., mortgages)
 * and other financial instruments according to various input data
 *
 *
 * DISCLAIMER
 * You are free to use/modify/extend the library as you please - for it to serve your purpose.
 * As per the (un)license, the software is provided as is and the original author cannot be held liable
 * for any losses/damages directly or indirectly resulting from using thereof.
 * Attribution is welcome, but certainly not required.
 *
 * NOTE
 * The library is currently work-in-progress and it is certain that new features will be added in the process.
 * Consider this, therefore, as a preview product prone to abrupt and extensive changes that may affect functionality
 * of an external code adapted to a prior version(s) of the library.
 * Always explore the provisional compatibility of the library with your project in case you upgrade to a new version
 * of the library (by means of an extensive testing of the code in which you are exerting the library's features).
 *
 * PREREQUISITES
 * PHP 5.5+
 * Module php-bcmath
 *
 * @author Václav Uruba
 * @version 0.3
 * @license http://unlicense.org The Unlicense
 */
namespace FinanCalc {
    use Exception;
    use FinanCalc\Interfaces\Calculator\CalculatorFactoryAbstract;
    use FinanCalc\Utils\Config;
    use FinanCalc\Calculators\Factories;
    use ReflectionClass;
    use ReflectionException;

    /**
     * Class FinanCalc
     * @package FinanCalc
     */
    class FinanCalc {
        private $factoryClasses = array();
        private static $instance = null;

        /**
         *
         * Serve the class as a singleton
         *
         */

        private function __construct() {
            $this->populateFactoryClassesArray();
        }

        protected function __clone() {
            // we do not want the singleton object to be cloned
        }

        /**
         * @return FinanCalc
         */
        public static function getInstance() {
            if (static::$instance === null) {
                static::$instance = new FinanCalc();
            }
            return static::$instance;
        }

        /**
         * @return string
         */
        public static function getPath() {
            return __DIR__;
        }

        /**
         *
         * Business logic IMPLEMENTATION
         *
         */

        /**
         * @return CalculatorFactoryAbstract[]
         */
        public function getFactories() {
            return $this->factoryClasses;
        }

        /**
         * @param $factoryClassName
         * @return CalculatorFactoryAbstract
         * @throws Exception
         */
        public function getFactory($factoryClassName) {
            if (array_key_exists($factoryClassName, $this->factoryClasses))
                return $this->factoryClasses[$factoryClassName];
            else
                throw new Exception("Factory class " . $factoryClassName . " is not initialized!");
        }

        /**
         * @param $configArray
         */
        public function setConfig($configArray = null) {
            Config::init($configArray);
        }

        /**
         *
         * PRIVATE functions
         *
         */

        private function populateFactoryClassesArray() {
            $factoryFiles = glob(__DIR__ . Config::getConfigField('factories_relative_path') . '/*.php');
            $factoriesNamespace = Config::getConfigField('factories_namespace');

            foreach ($factoryFiles as $factoryFile) {
                if (file_exists($factoryFile)) {
                    $factoryFileContents = file_get_contents($factoryFile);
                } else {
                    continue;
                }
                $fileTokens = token_get_all($factoryFileContents);
                for ($i = 2; $i < count($fileTokens); $i++) {
                    if ($fileTokens[$i-2][0] == T_CLASS) {
                        $factoryClassName = $fileTokens[$i][1];
                        try {
                            /** @noinspection PhpIncludeInspection */
                            require_once($factoryFile);
                            $factoryClassReflector = new ReflectionClass($factoriesNamespace . '\\' . $factoryClassName);
                        } catch (ReflectionException $e) {
                            error_log("The factory class "
                                . $factoryClassName
                                . " must be in the "
                                . $factoriesNamespace
                                . " namespace.");
                            continue;
                        }

                        $factoryAbstractClass = 'FinanCalc\\Interfaces\\Calculator\\CalculatorFactoryAbstract';

                        if ($factoryClassReflector->isSubclassOf($factoryAbstractClass)) {
                            $this->factoryClasses[$factoryClassName] = $factoryClassReflector->newInstance();
                            break;
                        } else {
                            error_log("The factory class " . $factoryClassName . " has to extend the abstract class " . $factoryAbstractClass . "!");
                        }
                    }
                }
            }
        }
    }


}