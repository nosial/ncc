<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'ncc\\thirdparty\\symfony\\uid\\abstractuid' => '/AbstractUid.php',
                'ncc\\thirdparty\\symfony\\uid\\binaryutil' => '/BinaryUtil.php',
                'ncc\\thirdparty\\symfony\\uid\\command\\generateulidcommand' => '/Command/GenerateUlidCommand.php',
                'ncc\\thirdparty\\symfony\\uid\\command\\generateuuidcommand' => '/Command/GenerateUuidCommand.php',
                'ncc\\thirdparty\\symfony\\uid\\command\\inspectulidcommand' => '/Command/InspectUlidCommand.php',
                'ncc\\thirdparty\\symfony\\uid\\command\\inspectuuidcommand' => '/Command/InspectUuidCommand.php',
                'ncc\\thirdparty\\symfony\\uid\\factory\\namebaseduuidfactory' => '/Factory/NameBasedUuidFactory.php',
                'ncc\\thirdparty\\symfony\\uid\\factory\\randombaseduuidfactory' => '/Factory/RandomBasedUuidFactory.php',
                'ncc\\thirdparty\\symfony\\uid\\factory\\timebaseduuidfactory' => '/Factory/TimeBasedUuidFactory.php',
                'ncc\\thirdparty\\symfony\\uid\\factory\\ulidfactory' => '/Factory/UlidFactory.php',
                'ncc\\thirdparty\\symfony\\uid\\factory\\uuidfactory' => '/Factory/UuidFactory.php',
                'ncc\\thirdparty\\symfony\\uid\\nilulid' => '/NilUlid.php',
                'ncc\\thirdparty\\symfony\\uid\\niluuid' => '/NilUuid.php',
                'ncc\\thirdparty\\symfony\\uid\\ulid' => '/Ulid.php',
                'ncc\\thirdparty\\symfony\\uid\\uuid' => '/Uuid.php',
                'ncc\\thirdparty\\symfony\\uid\\uuidv1' => '/UuidV1.php',
                'ncc\\thirdparty\\symfony\\uid\\uuidv3' => '/UuidV3.php',
                'ncc\\thirdparty\\symfony\\uid\\uuidv4' => '/UuidV4.php',
                'ncc\\thirdparty\\symfony\\uid\\uuidv5' => '/UuidV5.php',
                'ncc\\thirdparty\\symfony\\uid\\uuidv6' => '/UuidV6.php'
            );
        }
        $cn = strtolower($class);
        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    },
    true,
    false
);
// @codeCoverageIgnoreEnd
