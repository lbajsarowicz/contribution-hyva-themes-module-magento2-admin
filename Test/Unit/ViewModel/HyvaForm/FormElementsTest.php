<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\ViewModel\HyvaForm;

use Hyva\Admin\ViewModel\HyvaForm\FormButton;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinition;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaForm\FormGroup;
use Hyva\Admin\ViewModel\HyvaForm\FormSection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\TestCase;

class FormElementsTest extends TestCase
{
    public function testRendersElementsWithTheirViewModelAndTemplate(): void
    {
        $layout = $this->createMock(LayoutInterface::class);
        $factory = $this->createStub(FormFieldDefinitionInterfaceFactory::class);
        $field = new FormFieldDefinition($factory, $layout, 'title', [], null, null, null, null, null, null, 'example');
        $custom = new FormFieldDefinition($factory, $layout, 'custom', [], null, null, 'Vendor_Module::custom.phtml');
        $button = new FormButton($layout, 'save', 'Save');
        $group = new FormGroup($layout, 'details', [$field], 'Details');
        $section = new FormSection('example', [$group], 'Example', $layout, 'main');
        $expectations = [
            ['button', $button, 'Hyva_Admin::form/button.phtml'],
            ['group', $group, 'Hyva_Admin::form/group.phtml'],
            ['section', $section, 'Hyva_Admin::form/section.phtml'],
            ['field', $field, 'Hyva_Admin::form/field/text.phtml'],
            ['field', $custom, 'Vendor_Module::custom.phtml'],
        ];
        $layout->expects($this->exactly(5))->method('createBlock')->willReturnCallback(
            function ($class, $name, $arguments) use (&$expectations) {
                [$variable, $model, $template] = array_shift($expectations);
                $this->assertSame(Template::class, $class);
                $this->assertSame('', $name);
                $this->assertSame(['data' => ['template' => $template]], $arguments);
                $block = $this->getMockBuilder(Template::class)->disableOriginalConstructor()
                    ->onlyMethods(['assign', 'toHtml'])->getMock();
                $block->expects($this->once())->method('assign')->with($variable, $model)->willReturnSelf();
                $block->expects($this->once())->method('toHtml')->willReturn('<div>rendered</div>');
                return $block;
            }
        );
        foreach ([$button, $group, $section, $field, $custom] as $model) {
            $this->assertSame('<div>rendered</div>', $model->getHtml());
        }
        $this->assertSame('Save', $button->getLabel());
        $this->assertSame('details', $group->getId());
        $this->assertTrue($group->hasLabel());
        $this->assertSame([$field], $group->getFields());
        $this->assertSame('example_main', $section->getId());
        $this->assertSame('Title', $field->getLabel());
        $this->assertSame('example', $field->getFormName());
    }

    public function testOptionalLabelsAndFieldMetadataHaveUsableDefaults(): void
    {
        $layout = $this->createStub(LayoutInterface::class);
        $factory = $this->createStub(FormFieldDefinitionInterfaceFactory::class);
        $this->assertSame('Reset', (new FormButton($layout, 'reset'))->getLabel());
        $group = new FormGroup($layout, 'default');
        $this->assertFalse($group->hasLabel());
        $this->assertSame('', $group->getLabel());
        $this->assertSame([], $group->getFields());
        $field = new FormFieldDefinition($factory, $layout, 'first_name', null);
        $this->assertSame('First name', $field->getLabel());
        $this->assertSame('default', $field->getGroupId());
        $this->assertSame('text', $field->getInputType());
        $this->assertSame([], $field->getOptions());
    }
}
