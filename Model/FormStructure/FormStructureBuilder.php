<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormStructure;

use Hyva\Admin\Model\FormEntity\FormLoadEntity;
use Hyva\Admin\Model\HyvaFormDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterface as FieldDefinition;
use Hyva\Admin\ViewModel\HyvaForm\FormFieldDefinitionInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaForm\FormGroupInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaForm\FormSectionInterfaceFactory;

class FormStructureBuilder
{
    /**
     * @var FormFieldDefinitionInterfaceFactory
     */
    private $formFieldDefinitionFactory;

    /**
     * @var FormGroupInterfaceFactory
     */
    private $formGroupFactory;

    /**
     * @var FormSectionInterfaceFactory
     */
    private $formSectionFactory;

    public function __construct(
        FormFieldDefinitionInterfaceFactory $formFieldDefinitionFactory,
        FormGroupInterfaceFactory $formGroupFactory,
        FormSectionInterfaceFactory $formSectionFactory
    ) {
        $this->formFieldDefinitionFactory = $formFieldDefinitionFactory;
        $this->formGroupFactory = $formGroupFactory;
        $this->formSectionFactory = $formSectionFactory;
    }

    public function buildStructure(
        HyvaFormDefinitionInterface $formDefinition,
        FormLoadEntity $formEntity
    ): FormStructure {
        $fields = $this->mergeFieldDefinitions($formEntity->getFieldDefinitions(), $formDefinition->getFieldDefinitions());
        $fieldsByGroup = [];
        foreach ($fields as $field) {
            $field = $this->formFieldDefinitionFactory->create(array_merge($field->toArray(), [
                'formName' => $formDefinition->getFormName(),
            ]));
            $fieldsByGroup[$field->getGroupId()][] = $field;
        }

        $sections = [];
        foreach ($this->sortConfig($formDefinition->getSectionsConfig()) as $sectionConfig) {
            $groups = [];
            foreach ($this->sortConfig($sectionConfig['groups'] ?? []) as $groupConfig) {
                $groupId = $groupConfig['id'];
                $groups[] = $this->formGroupFactory->create([
                    'id' => $groupId,
                    'label' => $groupConfig['label'] ?? null,
                    'fields' => $fieldsByGroup[$groupId] ?? [],
                ]);
                unset($fieldsByGroup[$groupId]);
            }
            $sections[] = $this->formSectionFactory->create([
                'formName' => $formDefinition->getFormName(),
                'id' => $sectionConfig['id'],
                'label' => $sectionConfig['label'] ?? null,
                'groups' => $groups,
            ]);
        }

        if ($fieldsByGroup) {
            $groups = [];
            foreach ($fieldsByGroup as $groupId => $groupFields) {
                $groups[] = $this->formGroupFactory->create([
                    'id' => (string) $groupId,
                    'fields' => $groupFields,
                ]);
            }
            $sections[] = $this->formSectionFactory->create([
                'formName' => $formDefinition->getFormName(),
                'id' => 'default',
                'label' => null,
                'groups' => $groups,
            ]);
        }

        return new FormStructure($formDefinition->getFormName(), $sections);
    }

    /**
     * @param FieldDefinition[] $fieldsFromEntity
     * @param FieldDefinition[] $fieldsFromConfig
     * @return FieldDefinition[]
     */
    private function mergeFieldDefinitions(array $fieldsFromEntity, array $fieldsFromConfig): array
    {
        $fields = $fieldsFromConfig;
        foreach ($fieldsFromEntity as $name => $field) {
            $fields[$name] = isset($fieldsFromConfig[$name]) ? $field->merge($fieldsFromConfig[$name]) : $field;
        }
        return $fields;
    }

    private function sortConfig(array $config): array
    {
        $config = array_values($config);
        $positions = array_keys($config);
        usort($positions, function (int $a, int $b) use ($config): int {
            return ((int) ($config[$a]['sortOrder'] ?? 0) <=> (int) ($config[$b]['sortOrder'] ?? 0)) ?: $a <=> $b;
        });
        return array_map(function (int $position) use ($config): array {
            return $config[$position];
        }, $positions);
    }
}
