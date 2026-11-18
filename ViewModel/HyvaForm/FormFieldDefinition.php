<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

use function array_merge as merge;

class FormFieldDefinition implements FormFieldDefinitionInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array|null
     */
    private $options;

    /**
     * @var string|null
     */
    private $inputType;

    /**
     * @var string|null
     */
    private $groupId;

    /**
     * @var string|null
     */
    private $template;

    /**
     * @var bool|null
     */
    private $enabled;

    /**
     * @var bool|null
     */
    private $excluded;

    /**
     * @var string|null
     */
    private $valueProcessor;

    /**
     * @var FormFieldDefinitionInterfaceFactory
     */
    private $formFieldDefinitionFactory;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var string
     */
    private $formName;

    public function __construct(
        FormFieldDefinitionInterfaceFactory $formFieldDefinitionFactory,
        LayoutInterface $layout,
        string $name,
        ?array $options = [],
        ?string $inputType = null,
        ?string $groupId = null,
        ?string $template = null,
        ?bool $isEnabled = null,
        ?bool $isExcluded = null,
        ?string $valueProcessor = null,
        string $formName = ''
    ) {
        $this->formFieldDefinitionFactory = $formFieldDefinitionFactory;
        $this->name                       = $name;
        $this->options                    = $options;
        $this->inputType                  = $inputType;
        $this->groupId                    = $groupId;
        $this->template                   = $template;
        $this->enabled                    = $isEnabled;
        $this->excluded                   = $isExcluded;
        $this->valueProcessor             = $valueProcessor;
        $this->layout                     = $layout;
        $this->formName                   = $formName;
    }

    public function toArray(): array
    {
        return [
            'name'           => $this->name,
            'formName'       => $this->formName,
            'options'        => $this->options,
            'inputType'      => $this->inputType,
            'groupId'        => $this->groupId,
            'template'       => $this->template,
            'isEnabled'      => $this->enabled,
            'isExcluded'     => $this->excluded,
            'valueProcessor' => $this->valueProcessor,
        ];
    }

    public function merge(FormFieldDefinitionInterface $field): FormFieldDefinitionInterface
    {
        return $this->formFieldDefinitionFactory->create(merge($this->toArray(), $field->toArray()));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHtml(): string
    {
        /** @var Template $block */
        $block = $this->layout->createBlock(Template::class, '', [
            'data' => ['template' => $this->template ?? 'Hyva_Admin::form/field/' . $this->getInputType() . '.phtml'],
        ]);
        $block->assign('field', $this);

        return $block->toHtml();
    }

    public function getLabel(): string
    {
        return ucfirst(str_replace('_', ' ', $this->name));
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    public function getOptions(): array
    {
        return $this->options ?? [];
    }

    public function getGroupId(): string
    {
        return $this->groupId ?? 'default';
    }

    public function getInputType(): string
    {
        return $this->inputType ?? 'text';
    }

    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }
}
