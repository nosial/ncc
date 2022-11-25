<?php

    namespace ncc\Abstracts;

    abstract class LogLevel
    {
        const Silent = 'silent';

        const Verbose = 'verbose';

        const Debug = 'debug';

        const Info = 'info';

        const Warning = 'warn';

        const Error = 'error';

        const Fatal = 'fatal';

        const All = [
            self::Silent,
            self::Verbose,
            self::Debug,
            self::Info,
            self::Warning,
            self::Error,
            self::Fatal,
        ];
    }