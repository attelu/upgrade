<?php
declare(strict_types=1);

namespace Cake\Upgrade\Rector\Rector\MethodCall;

use Cake\Upgrade\Rector\ValueObject\OptionsArrayToNamedParameters;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class OptionsArrayToNamedParametersRector extends AbstractRector implements ConfigurableRectorInterface
{
    public const OPTIONS_TO_NAMED_PARAMETERS = 'options_to_named_parameters';

    /**
     * @var array<\Cake\Upgrade\Rector\ValueObject\OptionsArrayToNamedParameters>
     */
    private array $optionsToNamed = [];

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Converts trailing options arrays into named parameters. Will preserve all other arguments.',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
    use Cake\ORM\TableRegistry;

    $articles = TableRegistry::get('Articles');

    $query = $articles->find('list', ['field' => ['title']]);
    $query = $articles->find('all', ['conditions' => ['Articles.title' => $title]]);
    CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
    use Cake\ORM\TableRegistry;

    $articles = TableRegistry::get('Articles');

    $query = $articles->find('list', field: ['title']]);
    $query = $articles->find('all', conditions: ['Articles.title' => $title]);
    CODE_SAMPLE
                    ,
                    [
                        [
                            new OptionsArrayToNamedParameters('Table', ['find']),
                        ],
                    ]
                ),
            ]
        );
    }

    /**
     * @return array<class-string<\PhpParser\Node>>
     */
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    /**
     * @param \PhpParser\Node\Expr\MethodCall $node
     */
    public function configure(array $configuration): void
    {
        $this->optionsToNamed = $configuration;
    }

    /**
     * @param \PhpParser\Node\Expr\MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        foreach ($this->optionsToNamed as $optionsToNamed) {
            if (!$this->matchTypeAndMethodName($optionsToNamed, $node)) {
                continue;
            }

            return $this->replaceMethodCall($optionsToNamed, $node);
        }

        return null;
    }

    private function matchtypeAndMethodName(
        OptionsArrayToNamedParameters $optionsToNamed,
        MethodCall $methodCall
    ): bool {
        if (!$this->isObjectType($methodCall->var, $optionsToNamed->getObjectType())) {
            return false;
        }

        return $methodCall->name == $optionsToNamed->getMethod();
    }

    private function replaceMethodCall(
        OptionsArrayToNamedParameters $optionsToNamed,
        MethodCall $methodCall
    ): ?MethodCall {
        $argCount = count($methodCall->args);
        // Only modify method calls that have exactly two arguments.
        // This is important for idempotency.
        if ($argCount !== 2) {
            return null;
        }
        $optionsParam = $methodCall->args[$argCount - 1];
        if (!$optionsParam->value instanceof Array_ || $optionsParam->name instanceof Identifier) {
            return null;
        }
        // Create a copy of the arguments and remove the options array.
        $argNodes = $methodCall->args;
        unset($argNodes[$argCount - 1]);

        $renames = $optionsToNamed->getRenames();

        foreach ($optionsParam->value->items as $param) {
            $key = $param->key->value;
            if (isset($renames[$key])) {
                $key = $renames[$key];
            }
            $argNodes[] = new Arg($param->value, name: new Identifier($key));
        }
        $methodCall->args = $argNodes;

        return $methodCall;
    }
}
