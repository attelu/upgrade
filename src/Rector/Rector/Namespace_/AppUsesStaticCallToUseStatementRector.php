<?php
declare(strict_types=1);

namespace Cake\Upgrade\Rector\Rector\Namespace_;

use Cake\Upgrade\Rector\ShortClassNameResolver;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeTraverser;
use PHPStan\Type\ObjectType;
use Rector\Contract\PhpParser\Node\StmtsAwareInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PhpParser\Node\CustomNode\FileWithoutNamespace;
use Rector\PhpParser\Node\Value\ValueResolver;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see https://github.com/cakephp/upgrade/blob/756410c8b7d5aff9daec3fa1fe750a3858d422ac/src/Shell/Task/AppUsesTask.php
 * @see https://github.com/cakephp/upgrade/search?q=uses&unscoped_q=uses
 *
 * @see \Cake\Upgrade\Rector\Tests\Rector\Namespace_\AppUsesStaticCallToUseStatementRector\AppUsesStaticCallToUseStatementRectorTest
 */
final class AppUsesStaticCallToUseStatementRector extends AbstractRector
{
    public function __construct(
        private ShortClassNameResolver $shortClassNameResolver,
        private BetterNodeFinder $betterNodeFinder,
        private ValueResolver $valueResolver
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change App::uses() to use imports', [
            new CodeSample(
                <<<'CODE_SAMPLE'
App::uses('NotificationListener', 'Event');

CakeEventManager::instance()->attach(new NotificationListener());
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
use Event\NotificationListener;

CakeEventManager::instance()->attach(new NotificationListener());
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return array<class-string<\PhpParser\Node>>
     */
    public function getNodeTypes(): array
    {
        return [StmtsAwareInterface::class];
    }

    /**
     * @param \Rector\Contract\PhpParser\Node\StmtsAwareInterface $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node->stmts === null) {
            return null;
        }

        $appUsesStaticCalls = $this->collectAppUseStaticCalls($node);
        if ($appUsesStaticCalls === []) {
            return null;
        }

        $names = $this->resolveNamesFromStaticCalls($appUsesStaticCalls);
        $uses = [];
        foreach ($names as $name) {
            $useUse = new UseUse(new Name($name));
            $uses[] = new Use_([$useUse]);
        }

        $this->removeCallLikeStmts($node, $node->stmts, $appUsesStaticCalls);

        if ($node instanceof Namespace_) {
            $node->stmts = array_merge($uses, $node->stmts);
        }

        if ($node instanceof FileWithoutNamespace) {
            $this->refactorFile($node, $uses);
        }

        return $node;
    }

    /**
     * @param array<\PhpParser\Node\Stmt> $stmts
     * @param array<\PhpParser\Node\Expr\StaticCall> $appUsesStaticCalls
     */
    private function removeCallLikeStmts(StmtsAwareInterface $node, array $stmts, array $appUsesStaticCalls): void
    {
        $currentStmt = null;
        $this->traverseNodesWithCallable(
            $stmts,
            function (Node $subNode) use ($node, $appUsesStaticCalls, &$currentStmt) {
                // only lookup each of current stmts, avoid too deep traversal
                if ($subNode instanceof StmtsAwareInterface) {
                    return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
                }

                if ($subNode instanceof Stmt) {
                    $currentStmt = $subNode;

                    return null;
                }

                if (! $subNode instanceof StaticCall) {
                    return null;
                }

                if (! in_array($subNode, $appUsesStaticCalls, true)) {
                    return null;
                }

                /** @var \PhpParser\Node\Stmt $currentStmt */
                unset($node->stmts[$currentStmt->getAttribute(AttributeKey::STMT_KEY)]);

                return null;
            }
        );
    }

    /**
     * @return array<\PhpParser\Node\Expr\StaticCall>
     */
    private function collectAppUseStaticCalls(StmtsAwareInterface $node): array
    {
        /** @var array<\PhpParser\Node\Expr\StaticCall> $appUsesStaticCalls */
        $appUsesStaticCalls = $this->betterNodeFinder->find($node, function (Node $node): bool {
            if (! $node instanceof StaticCall) {
                return false;
            }

            $callerType = $this->nodeTypeResolver->getType($node->class);
            if (! $callerType->isSuperTypeOf(new ObjectType('App'))->yes()) {
                return false;
            }

            return $this->isName($node->name, 'uses');
        });

        return $appUsesStaticCalls;
    }

    /**
     * @param array<\PhpParser\Node\Expr\StaticCall> $staticCalls
     * @return array<string>
     */
    private function resolveNamesFromStaticCalls(array $staticCalls): array
    {
        $names = [];
        foreach ($staticCalls as $staticCall) {
            $names[] = $this->createFullyQualifiedNameFromAppUsesStaticCall($staticCall);
        }

        return $names;
    }

    /**
     * @param array<\PhpParser\Node\Stmt\Use_> $fileWithoutNamespace
     */
    private function refactorFile(FileWithoutNamespace $fileWithoutNamespace, array $uses): ?FileWithoutNamespace
    {
        $hasDeclare = $this->betterNodeFinder->findFirstInstanceOf($fileWithoutNamespace->stmts, Declare_::class);
        if ($hasDeclare !== null) {
            return $this->refactorFileWithDeclare($fileWithoutNamespace, $uses);
        }

        $fileWithoutNamespace->stmts = array_merge($uses, $fileWithoutNamespace->stmts);

        return $fileWithoutNamespace;
    }

    private function createFullyQualifiedNameFromAppUsesStaticCall(StaticCall $staticCall): string
    {
        /** @var string $shortClassName */
        $shortClassName = $this->valueResolver->getValue($staticCall->args[0]->value);

        /** @var string $namespaceName */
        $namespaceName = $this->valueResolver->getValue($staticCall->args[1]->value);

        return $this->shortClassNameResolver->resolveShortClassName(
            $namespaceName,
            $shortClassName
        );
    }

    /**
     * @param array<\PhpParser\Node\Stmt\Use_> $fileWithoutNamespace
     */
    private function refactorFileWithDeclare(
        FileWithoutNamespace $fileWithoutNamespace,
        array $uses
    ): FileWithoutNamespace {
        foreach ($fileWithoutNamespace->stmts as $key => $stmt) {
            if ($stmt instanceof Declare_) {
                foreach ($uses as $use) {
                    array_splice($fileWithoutNamespace->stmts, $key + 1, 0, [$use]);
                }
            }
        }

        return $fileWithoutNamespace;
    }
}
