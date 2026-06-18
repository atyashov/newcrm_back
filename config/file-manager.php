<?php

use Alexusmai\LaravelFileManager\Services\ACLService\ConfigACLRepository;
use Alexusmai\LaravelFileManager\Services\ConfigService\DefaultConfigRepository;

return [
    'configRepository' => DefaultConfigRepository::class,
    'aclRepository' => ConfigACLRepository::class,

    'routePrefix' => 'api/admin/file-manager',

    'diskList' => ['images'],
    'leftDisk' => 'images',
    'rightDisk' => null,
    'leftPath' => null,
    'rightPath' => null,

    'windowsConfig' => 2,
    'maxUploadFileSize' => null,
    'allowFileTypes' => [],
    'hiddenFiles' => true,

    'middleware' => ['api', 'auth:api'],

    'acl' => false,
    'aclHideFromFM' => true,
    'aclStrategy' => 'blacklist',
    'aclRulesCache' => null,

    'aclRules' => [
        null => [],
    ],

    'slugifyNames' => false,
];
