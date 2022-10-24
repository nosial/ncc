<?php

    namespace ncc\Abstracts;

    abstract class ComponentDataType
    {
        /**
         * Indicates whether the component is represented as an AST representation
         */
        const AST = 'ast';

        /**
         * Indicates whether the component is represented as plaintext
         */
        const Plain = 'plain';

        /**
         * Indicates whether the component is represented as bytecode
         */
        const Bytecode = 'bytecode';

        /**
         * Indicates whether the component is represented as binary or executable
         */
        const Binary = 'binary';

        /**
         * Indicates whether the component is represented as as a base64 encoded string (Raw bytes' representation)
         */
        const b64encoded = 'b64enc';
    }