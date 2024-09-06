<?php
declare(strict_types=1);

use Cake\Upgrade\Rector\Rector\MethodCall\OptionsArrayToNamedParametersRector;
use Cake\Upgrade\Rector\Rector\MethodCall\RemoveMethodCallRector;
use Cake\Upgrade\Rector\Rector\MethodCall\TableRegistryLocatorRector;
use Cake\Upgrade\Rector\ValueObject\OptionsArrayToNamedParameters;
use Cake\Upgrade\Rector\ValueObject\RemoveMethodCall;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;
use Rector\TypeDeclaration\ValueObject\AddPropertyTypeDeclaration;

# @see https://book.cakephp.org/5/en/appendices/5-0-migration-guide.html
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(
        OptionsArrayToNamedParametersRector::class,
        [
            new OptionsArrayToNamedParameters('Cake\ORM\Table', ['find']),
            new OptionsArrayToNamedParameters('Cake\ORM\Query', ['find']),
            new OptionsArrayToNamedParameters('Cake\ORM\Query\SelectQuery', ['find']),
            new OptionsArrayToNamedParameters('Cake\ORM\Association', ['find']),
            new OptionsArrayToNamedParameters('Cake\ORM\Table', ['get', 'rename' => ['key' => 'cacheKey']]),
        ]
    );

    $arrayType = new ArrayType(new MixedType(), new MixedType());
    $stringNull = new UnionType([new StringType(), new NullType()]);
    $stringType = new StringType();
    $boolType = new BooleanType();
    $rectorConfig->ruleWithConfiguration(
        AddPropertyTypeDeclarationRector::class,
        [
            // Entity properties
            new AddPropertyTypeDeclaration('Cake\ORM\Entity', '_hidden', $arrayType),
            new AddPropertyTypeDeclaration('Cake\ORM\Entity', '_accessible', $arrayType),
            new AddPropertyTypeDeclaration('Cake\ORM\Entity', '_virtual', $arrayType),

            // Plugin properties
            new AddPropertyTypeDeclaration('Cake\Core\BasePlugin', 'bootstrapEnabled', $boolType),
            new AddPropertyTypeDeclaration('Cake\Core\BasePlugin', 'consoleEnabled', $boolType),
            new AddPropertyTypeDeclaration('Cake\Core\BasePlugin', 'middlewareEnabled', $boolType),
            new AddPropertyTypeDeclaration('Cake\Core\BasePlugin', 'servicesEnabled', $boolType),
            new AddPropertyTypeDeclaration('Cake\Core\BasePlugin', 'routesEnabled', $boolType),
            new AddPropertyTypeDeclaration('Cake\Core\BasePlugin', 'path', $stringNull),
            new AddPropertyTypeDeclaration('Cake\Core\BasePlugin', 'classPath', $stringNull),
            new AddPropertyTypeDeclaration('Cake\Core\BasePlugin', 'configPath', $stringNull),
            new AddPropertyTypeDeclaration('Cake\Core\BasePlugin', 'templatePath', $stringNull),
            new AddPropertyTypeDeclaration('Cake\Core\BasePlugin', 'name', $stringNull),

            // Helper properties
            new AddPropertyTypeDeclaration('Cake\View\Helper\FormHelper', '_defaultWidgets', $arrayType),
            new AddPropertyTypeDeclaration('Cake\View\Helper', '_defaultConfig', $arrayType),
            new AddPropertyTypeDeclaration('Cake\View\Helper', '_defaultConfig', $arrayType),
            new AddPropertyTypeDeclaration('Cake\View\Helper', 'helpers', $arrayType),

            // ORM properties
            new AddPropertyTypeDeclaration('Cake\ORM\Behavior', '_defaultConfig', $arrayType),

            // Controller properties
            new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'name', $stringType),
            new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'paginate', $arrayType),
            new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'plugin', $stringNull),
            new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'autoRender', $boolType),
            new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'middlewares', $arrayType),
            new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'viewClasses', $arrayType),
            new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'modelClass', $stringNull),
            new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'defaultTable', $stringNull),

            // Component properties
            new AddPropertyTypeDeclaration('Cake\Controller\Component', '_defaultConfig', $arrayType),
            new AddPropertyTypeDeclaration('Cake\Controller\Component', 'components', $arrayType),

            // View properties
            new AddPropertyTypeDeclaration('Cake\View\View', 'layout', $stringType),
            new AddPropertyTypeDeclaration('Cake\View\Widget\BasicWidget', 'defaults', $arrayType),

            // TestSuite properties
            new AddPropertyTypeDeclaration('Cake\TestSuite\TestCase', 'fixtures', $arrayType),
            new AddPropertyTypeDeclaration('Cake\TestSuite\Fixture\TestFixture', 'connection', $stringType),
            new AddPropertyTypeDeclaration('Cake\TestSuite\Fixture\TestFixture', 'table', $stringType),
            new AddPropertyTypeDeclaration('Cake\TestSuite\Fixture\TestFixture', 'records', $arrayType),

            // Cell properties
            new AddPropertyTypeDeclaration('Cake\View\Cell', '_validCellOptions', $arrayType),

            // Mailer
            new AddPropertyTypeDeclaration('Cake\Mailer\Mailer', 'name', $stringType),
        ]
    );

    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Cake\I18n\FrozenDate' => 'Cake\I18n\Date',
        'Cake\I18n\FrozenTime' => 'Cake\I18n\DateTime',
    ]);

    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [
        new MethodCallRename('Cake\Database\Query', 'order', 'orderBy'),
        new MethodCallRename('Cake\Database\Query', 'orderAsc', 'orderByAsc'),
        new MethodCallRename('Cake\Database\Query', 'orderDesc', 'orderByDesc'),
        new MethodCallRename('Cake\Database\Query', 'group', 'groupBy'),
        new MethodCallRename('Cake\Database\Query\SelectQuery', 'group', 'groupBy'),
    ]);

    $rectorConfig->ruleWithConfiguration(RemoveMethodCallRector::class, [
        new RemoveMethodCall('Cake\TestSuite\TestCase', 'useCommandRunner'),
        new RemoveMethodCall('Cake\TestSuite\TestCase', 'useHttpServer'),
    ]);

    $rectorConfig->rule(TableRegistryLocatorRector::class);
};
