<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\FormStructure;

use Hyva\Admin\Model\FormEntity\FormLoadEntity;
use Hyva\Admin\Model\FormStructure\FormStructureBuilder;
use Hyva\Admin\Model\HyvaFormDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinition;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaForm\FormGroup;
use Hyva\Admin\ViewModel\HyvaForm\FormGroupInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaForm\FormSection;
use Hyva\Admin\ViewModel\HyvaForm\FormSectionInterfaceFactory;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\TestCase;

class FormStructureBuilderTest extends TestCase
{
    public function testBuildsOrderedSectionsAndKeepsUnassignedFields(): void
    {
        $layout = $this->createStub(LayoutInterface::class);
        $fieldFactory = $this->createStub(FormFieldDefinitionInterfaceFactory::class);
        $fieldFactory->method('create')->willReturnCallback(function (array $data) use ($fieldFactory, $layout) {
            return new FormFieldDefinition(
                $fieldFactory,
                $layout,
                $data['name'],
                $data['options'] ?? [],
                $data['inputType'] ?? null,
                $data['groupId'] ?? null,
                $data['template'] ?? null,
                $data['isEnabled'] ?? null,
                $data['isExcluded'] ?? null,
                $data['valueProcessor'] ?? null,
                $data['formName'] ?? ''
            );
        });
        $groupFactory = $this->createStub(FormGroupInterfaceFactory::class);
        $groupFactory->method('create')->willReturnCallback(function (array $data) use ($layout) {
            return new FormGroup($layout, $data['id'], $data['fields'], $data['label'] ?? null);
        });
        $sectionFactory = $this->createStub(FormSectionInterfaceFactory::class);
        $sectionFactory->method('create')->willReturnCallback(function (array $data) use ($layout) {
            return new FormSection($data['formName'], $data['groups'], $data['label'], $layout, $data['id']);
        });
        $entityField = new FormFieldDefinition($fieldFactory, $layout, 'title', [], 'text', 'original');
        $override = new FormFieldDefinition($fieldFactory, $layout, 'title', [], 'select', 'details');
        $extra = new FormFieldDefinition($fieldFactory, $layout, 'extra');
        $configOnly = new FormFieldDefinition($fieldFactory, $layout, 'custom', [], 'text', 'details');
        $entity = $this->createStub(FormLoadEntity::class);
        $entity->method('getFieldDefinitions')->willReturn(['title' => $entityField, 'extra' => $extra]);
        $definition = $this->createStub(HyvaFormDefinitionInterface::class);
        $definition->method('getFormName')->willReturn('example');
        $definition->method('getFieldDefinitions')->willReturn(['title' => $override, 'custom' => $configOnly]);
        $definition->method('getSectionsConfig')->willReturn([
            ['id' => 'later', 'sortOrder' => 20],
            ['id' => 'first', 'sortOrder' => 10, 'label' => 'First', 'groups' => [
                ['id' => 'empty', 'sortOrder' => 20],
                ['id' => 'details', 'sortOrder' => 10, 'label' => 'Details'],
                ['id' => 'same-order', 'sortOrder' => 10],
            ]],
        ]);

        $builder = new FormStructureBuilder($fieldFactory, $groupFactory, $sectionFactory);
        $sections = $builder->buildStructure($definition, $entity)->getSections();

        $this->assertSame(['example_first', 'example_later', 'example_default'], array_map(function ($section) {
            return $section->getId();
        }, $sections));
        $groups = $sections[0]->getGroups();
        $this->assertSame(['details', 'same-order', 'empty'], array_map(function ($group) {
            return $group->getId();
        }, $groups));
        $this->assertSame('Details', $groups[0]->getLabel());
        $fields = $groups[0]->getFields();
        $this->assertSame(['title', 'custom'], array_map(function ($field) {
            return $field->getName();
        }, $fields));
        $this->assertSame('select', $fields[0]->getInputType());
        $this->assertSame('example', $fields[0]->toArray()['formName']);
        $this->assertSame('extra', $sections[2]->getGroups()[0]->getFields()[0]->getName());
    }

    public function testEmptyFormReturnsAnEmptyStructure(): void
    {
        $fieldFactory = $this->createMock(FormFieldDefinitionInterfaceFactory::class);
        $fieldFactory->expects($this->never())->method('create');
        $groupFactory = $this->createMock(FormGroupInterfaceFactory::class);
        $groupFactory->expects($this->never())->method('create');
        $sectionFactory = $this->createMock(FormSectionInterfaceFactory::class);
        $sectionFactory->expects($this->never())->method('create');
        $entity = $this->createStub(FormLoadEntity::class);
        $entity->method('getFieldDefinitions')->willReturn([]);
        $definition = $this->createStub(HyvaFormDefinitionInterface::class);
        $definition->method('getFormName')->willReturn('empty');
        $definition->method('getFieldDefinitions')->willReturn([]);
        $definition->method('getSectionsConfig')->willReturn([]);

        $builder = new FormStructureBuilder($fieldFactory, $groupFactory, $sectionFactory);
        $this->assertSame([], $builder->buildStructure($definition, $entity)->getSections());
    }
}
