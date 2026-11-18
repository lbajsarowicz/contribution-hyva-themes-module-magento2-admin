<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaForm;

class FormFieldOption implements FormFieldOptionInterface
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $label;

    public function __construct(string $value, string $label)
    {
        $this->value = $value;
        $this->label = $label;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
