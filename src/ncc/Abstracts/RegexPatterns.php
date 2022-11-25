<?php

    namespace ncc\Abstracts;

    /**
     * @author Zi Xing Narrakas
     * @copyright Copyright (C) 2022-2022. Nosial - All Rights Reserved.
     */
    abstract class RegexPatterns
    {
        const UUID = '{^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$}Di';

        const PackageNameFormat = '/^[a-z][a-z0-9_]*(\.[a-z0-9_]+)+[0-9a-z_]$/';

        const ComposerVersionFormat = '/^([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?$/';

        const PythonVersionFormat = '/^([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?$/';

        const SemanticVersioning2 = '/^(?P<major>0|[1-9]\d*)\.(?P<minor>0|[1-9]\d*)\.(?P<patch>0|[1-9]\d*)(?:-(?P<prerelease>(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+(?P<buildmetadata>[0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/m';

        const UnixPath = '/^(((?:\.\/|\.\.\/|\/)?(?:\.?\w+\/)*)(\.?\w+\.?\w+))$/m';

        const WindowsPath = '/^(([%][^\/:*?<>""|]*[%])|([a-zA-Z][:])|(\\\\))((\\\\{1})|((\\\\{1})[^\\\\]([^\/:*?<>""|]*))+)$/m';

        const ConstantName = '/^([^\x00-\x7F]|[\w_\ \.\+\-]){2,64}$/';

        const ExecutionPolicyName = '/^[_$a-zA-Z\xA0-\uFFFF][_$a-zA-Z0-9\xA0-\uFFFF]*$/m';
    }