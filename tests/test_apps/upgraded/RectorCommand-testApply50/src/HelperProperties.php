<?php
declare(strict_types=1);

use Cake\Controller\Controller;
use Cake\ORM\Behavior;
use Cake\View\Helper\FormHelper;

class CustomFormHelper extends FormHelper
{
    protected array $_defaultWidgets = [];

    protected array $_defaultConfig = [];

    protected array $helpers = [];
}

class ArticlesController extends Controller
{
    use \Cake\Datasource\ModelAwareTrait;

    protected string $name = 'Articles';

    protected array $paginate = [];

    protected bool $autoRender = false;

    protected ?string $plugin = 'Admin';

    protected array $middlewares = [];

    protected array $viewClasses = [];

    protected ?string $modelClass = null;

    protected ?string $defaultTable = null;

    public function setSerialize(): void
    {
        $this->viewBuilder()->setOption('serialize', 'result');
    }
}

class CustomBehavior extends Behavior
{
    protected array $_defaultConfig = [];
}
