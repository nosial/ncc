<?php

    namespace SimpleLibrary;

    class Calculator
    {
        /**
         * Adds two numbers
         *
         * @param float $a
         * @param float $b
         * @return float
         */
        public static function add(float $a, float $b): float
        {
            return $a + $b;
        }

        /**
         * Subtracts two numbers
         *
         * @param float $a
         * @param float $b
         * @return float
         */
        public static function subtract(float $a, float $b): float
        {
            return $a - $b;
        }

        /**
         * Multiplies two numbers
         *
         * @param float $a
         * @param float $b
         * @return float
         */
        public static function multiply(float $a, float $b): float
        {
            return $a * $b;
        }

        /**
         * Divides two numbers
         *
         * @param float $a
         * @param float $b
         * @return float
         * @throws \InvalidArgumentException
         */
        public static function divide(float $a, float $b): float
        {
            if ($b === 0.0) {
                throw new \InvalidArgumentException('Division by zero');
            }
            return $a / $b;
        }
    }
