<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\String_\RenameStringRector;

# @see https://book.cakephp.org/5/en/appendices/5-1-migration-guide.html
return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->ruleWithConfiguration(RenameStringRector::class, [
        // Rename the cache configuration used by translations.
        '_cake_core_' => '_cake_translations_',
    ]);
};
