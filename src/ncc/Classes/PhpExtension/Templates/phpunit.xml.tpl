<phpunit bootstrap="bootstrap.php">
    <testsuites>
        <testsuite name="%ASSEMBLY.NAME% Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <php>
        <ini name="error_reporting" value="-1"/>
        <server name="KERNEL_DIR" value="app/"/>
    </php>
</phpunit>
