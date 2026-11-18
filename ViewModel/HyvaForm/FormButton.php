<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class FormButton implements FormButtonInterface
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
     * @var string|null
     */
    private $label;

    public function __construct(LayoutInterface $layout, string $id, ?string $label = null)
    {
        $this->layout = $layout;
        $this->id = $id;
        $this->label = $label;
    }

    public function getLabel(): string
    {
        return $this->label ?? ucfirst($this->id);
    }

    public function getHtml(): string
    {
        /** @var Template $block */
        $block = $this->layout->createBlock(Template::class, '', [
            'data' => ['template' => 'Hyva_Admin::form/button.phtml'],
        ]);
        $block->assign('button', $this);

        return $block->toHtml();
    }
}
