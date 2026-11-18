<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class FormGroup implements FormGroupInterface
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var string|null
     */
    private $label;

    public function __construct(LayoutInterface $layout, string $id, array $fields = [], ?string $label = null)
    {
        $this->layout = $layout;
        $this->id = $id;
        $this->fields = $fields;
        $this->label = $label;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label ?? '';
    }

    public function hasLabel(): bool
    {
        return $this->getLabel() !== '';
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getHtml(): string
    {
        /** @var Template $block */
        $block = $this->layout->createBlock(Template::class, '', [
            'data' => ['template' => 'Hyva_Admin::form/group.phtml'],
        ]);
        $block->assign('group', $this);

        return $block->toHtml();
    }
}
