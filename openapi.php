<?php
require("vendor/autoload.php");

if (php_sapi_name() != 'cli') {
    exit('Run in commandline only' . PHP_EOL);
}

$openapi = \OpenApi\Generator::scan([__DIR__ .'/app/'], [
    'aliases' => ['OA' => 'OpenApi\\Attributes'],
    'namespaces' => ['OpenApi\\Attributes\\']
]);


header('Content-Type: application/x-yaml');
echo $openapi->toYaml();
