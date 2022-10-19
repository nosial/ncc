<?php

    namespace ncc\Interfaces;

    interface CompilerInterface
    {
        public function prepare(array $options, string $src);

        public function build(array $options, string $src);
    }