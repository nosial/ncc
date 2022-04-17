<?php

    namespace ncc\Utilities;

    class Security
    {
        /**
         * @param string $input
         * @param bool $beautify
         * @return string
         * @author Marc Gutt <marc@gutt.it>
         */
        public static function sanitizeFilename(string $input, bool $beautify=true): string
        {
            // sanitize filename
            $input = preg_replace(
                '~
        [<>:"/\\\|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
        [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
        [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
        [#\[\]@!$&\'()+,;=]|     # URI reserved https://www.rfc-editor.org/rfc/rfc3986#section-2.2
        [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
        ~x',
                '-', $input);
            // avoids ".", ".." or ".hiddenFiles"
            $input = ltrim($input, '.-');
            // optional beautification
            if ($beautify) $input = self::beautifyFilename($input);
            // maximize filename length to 255 bytes http://serverfault.com/a/9548/44086
            $ext = pathinfo($input, PATHINFO_EXTENSION);
            $input = mb_strcut(pathinfo($input, PATHINFO_FILENAME), 0, 255 - ($ext ? strlen($ext) + 1 : 0), mb_detect_encoding($input)) . ($ext ? '.' . $ext : '');
            return $input;
        }

        /**
         * @param string $input
         * @return string
         * @author Marc Gutt <marc@gutt.it>
         */
        public static function beautifyFilename(string $input): string
        {
            // reduce consecutive characters
            $input = preg_replace(array(
                // "file   name.zip" becomes "file-name.zip"
                '/ +/',
                // "file___name.zip" becomes "file-name.zip"
                '/_+/',
                // "file---name.zip" becomes "file-name.zip"
                '/-+/'
            ), '-', $input);
            $input = preg_replace(array(
                // "file--.--.-.--name.zip" becomes "file.name.zip"
                '/-*\.-*/',
                // "file...name..zip" becomes "file.name.zip"
                '/\.{2,}/'
            ), '.', $input);
            // lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
            $input = mb_strtolower($input, mb_detect_encoding($input));
            // ".file-name.-" becomes "file-name"
            $input = trim($input, '.-');

            return $input;
        }
    }