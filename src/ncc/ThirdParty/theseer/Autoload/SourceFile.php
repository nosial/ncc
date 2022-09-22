<?php
namespace ncc\ThirdParty\theseer\Autoload {

    use SplFileInfo;

    class SourceFile extends SplFileInfo {

        public function getTokens() {
            return token_get_all(file_get_contents($this->getRealPath()));
        }

    }

}
