<?php

declare(strict_types=1);

use Cake\Upgrade\Rector\Rector\MethodCall\OptionsArrayToNamedParametersRector;
use Cake\Upgrade\Rector\Tests\Rector\MethodCall\OptionsArrayToNamedParametersRector\Source\ConfigurableClass;
use Cake\Upgrade\Rector\ValueObject\OptionsArrayToNamedParameters;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(__DIR__ . '/../../../../../../config/rector/config.php');

    $rectorConfig->ruleWithConfiguration(OptionsArrayToNamedParametersRector::class, [
        new OptionsArrayToNamedParameters(ConfigurableClass::class, ['find'])
    ]);
};
