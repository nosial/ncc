<?php

    namespace ncc\Interfaces;

    interface CompilerInterface
    {
        public function prepare(array $options);

        public function build(array $options);
    }