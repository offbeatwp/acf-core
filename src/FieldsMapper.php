<?php

namespace OffbeatWP\AcfCore;

class FieldsMapper
{
    public $form = [];
    public $mappedFields = [];
    public $keyPrefix = '';
    public $namePrefix = '';
    public $context = null;
    public $fields;
    private $keySuffix;

    /**
     * @param $form
     * @param string $keyPrefix
     * @param string $keySuffix Only applied when translating keys for conditional logic
     */
    public function __construct($form, string $keyPrefix = '', string $keySuffix = '')
    {
        $this->fields = $form;
        $this->keyPrefix = $keyPrefix;
        $this->keySuffix = $keySuffix;
    }

    public function map($form = null, bool $global = true): array
    {
        $root = false;
        $mapping = [];

        if ($form === null) {
            $root = true;
            $form = $this->fields;
        }

        if ($form->getType() === 'field') {
            $mapping[] = $this->mapField($form, $global);
        } else {
            $form->each(function ($entry) use (&$mapping, $global) {
                switch ($entry->getType()) {
                    case 'form':
                        $formFields = $this->mapForm($entry, $global);

                        if (!empty($formFields)) {
                            foreach ($formFields as $formField) {
                                if ($global) {
                                    $this->mappedFields[] = $formField;
                                } else {
                                    $mapping[] = $formField;
                                }
                            }
                        }

                        break;
                    case 'tab':
                        $mapping[] = $this->mapTab($entry);
                        break;
                    case 'section':
                        $mapping[] = $this->mapSection($entry);
                        break;
                    case 'repeater':
                    case 'field':
                        $mapping[] = $this->mapField($entry, $global);
                        break;
                }
            });
        }

        if ($root) {
            return $this->mappedFields;
        }

        return $mapping;
    }

    public function mapForm($form, bool $global = true): array
    {
        $idPrefixes = [];
        $idPrefixes[] = $this->fields->getFieldPrefix();
        $idPrefixes[] = $form->getFieldPrefix();
        $idPrefixes = array_filter($idPrefixes);

        $fieldsMapper = new self($form, implode('_', $idPrefixes));

        if ($this->getContext()) {
            $fieldsMapper->setContext($this->getContext());
        }

        return $fieldsMapper->map();
    }

    public function mapField($field, bool $global): array
    {
        $fieldType = $field->getType();

        if (method_exists($field, 'getFieldType')) {
            $fieldType = $field->getFieldType();
        }

        $key = $field->getAttribute('key');

        if ($key) {
            $prefix = ($this->keyPrefix) ? $this->keyPrefix . '_' : '';
            $key = $prefix . $key;
        } else {
            $key = $this->prefixId('field', $field->getId());
        }

        if ($this->getContext()) {
            $key .= '_' . $this->getContext();
        }

        $mappedField = [
            'key' => $key,
            'label' => $field->getLabel(),
            'name' => $field->getId(),
            '_name' => $field->getId(),
            'type' => $this->mapFieldType($fieldType),
            'required' => 0,
        ];

        if ($field->getAttribute('required')) {
            $mappedField['required'] = $field->getAttribute('required');
        }

        if ($field->getAttribute('default')) {
            $mappedField['default_value'] = $field->getAttribute('default');
        }

        if ($field->getAttribute('placeholder')) {
            $mappedField['placeholder'] = $field->getAttribute('placeholder');
        }

        if ($field->getAttribute('multiple')) {
            $mappedField['multiple'] = $field->getAttribute('multiple');
        }

        if ($field->getAttribute('ui')) {
            $mappedField['ui'] = $field->getAttribute('ui');
        }

        if ($field->getAttribute('ajax')) {
            $mappedField['ajax'] = $field->getAttribute('ajax');
        }

        if ($field->getAttribute('field_type')) {
            $mappedField['field_type'] = $field->getAttribute('field_type');
        }

        if ($field->getAttribute('layout')) {
            $mappedField['layout'] = $field->getAttribute('layout');
        }

        if ($field->getAttribute('new_lines')) {
            $mappedField['new_lines'] = $field->getAttribute('new_lines');
        }

        if ($field->getAttribute('allowed_file_types')) {
            $mappedField['mime_types'] = $field->getAttribute('allowed_file_types');
        }

        if ($field->getAttribute('rows')) {
            $mappedField['rows'] = $field->getAttribute('rows');
        }

        if ($field->getAttribute('min')) {
            $mappedField['min'] = $field->getAttribute('min');
        }

        if ($field->getAttribute('max')) {
            $mappedField['max'] = $field->getAttribute('max');
        }

        if ($field->getAttribute('new_lines')) {
            $mappedField['new_lines'] = $field->getAttribute('new_lines');
        }

        if ($field->getAttribute('description')) {
            $mappedField['instructions'] = $field->getAttribute('description');
        }

        if ($field->getAttribute('conditional_logic')) {
            $mappedField['conditional_logic'] = $field->getAttribute('conditional_logic');
        }

        if ($field->getAttribute('allow_null')) {
            $mappedField['allow_null'] = $field->getAttribute('allow_null');
        }

        if ($field->getAttribute('class')) {
            $mappedField['wrapper']['class'] = $field->getAttribute('class');
        }

        if ($field->getAttribute('width')) {
            $mappedField['wrapper']['width'] = $field->getAttribute('width');
        }

        if ($field->getAttribute('id')) {
            $mappedField['wrapper']['id'] = $field->getAttribute('id');
        }

        switch ($fieldType) {
            case 'repeater':
                $mappedField['layout'] = 'block';
                $mappedField['sub_fields'] = $this->map($field, false);

                if ($field->getAttribute('collapsed')) {
                    $mappedField['collapsed'] = $this->prefixId('field', $field->getAttribute('collapsed'));
                }
                break;
            case 'select':
            case 'button_group':
            case 'checkbox':
            case 'radio':
                $mappedField['choices'] = $field->getOptions();
                $mappedField['return_format'] = 'value';
                break;
            case 'post_object':
            case 'post':
            case 'posts':
                $mappedField['post_type'] = [];

                if ($field->getAttribute('post_types')) {
                    $mappedField['post_type'] = array_merge($mappedField['post_type'], $field->getAttribute('post_types'));
                }

                if ($fieldType === 'posts') {
                    $mappedField['multiple'] = 1;
                }

                $mappedField['return_format'] = 'id';
                break;
            case 'taxonomy':
            case 'terms':
                $mappedField['taxonomy'] = $field->getAttribute('taxonomy');
                $mappedField['return_format'] = 'id';
                break;
            case 'term':
                $mappedField['taxonomy'] = $field->getAttribute('taxonomy');
                $mappedField['return_format'] = 'id';
                $mappedField['field_type'] = 'select';
                break;
            case 'image':
            case 'file':
                $mappedField['return_format'] = 'id';
                break;
            case 'flexible_content':
            case 'offbeat_components':
                $mappedField['layouts'] = $field->getAttribute('layouts');
                $mappedField['button_label'] = $field->getAttribute('button_label');
                break;
            case 'acffield':
                $acfFieldContent = $field->getAttribute('acffield');

                if (!empty($acfFieldContent) && is_array($acfFieldContent)) {
                    unset($acfFieldContent['key']);
                    $mappedField = array_merge($mappedField, $acfFieldContent);
                }

                break;
        }

        if (!empty($mappedField['conditional_logic'])) {
            $mappedField['conditional_logic'] = $this->transformKeysConditionalLogic($mappedField['conditional_logic']);
        }

        $returnFormat = $field->getAttribute('return_format');
        if ($returnFormat) {
            $mappedField['return_format'] = $returnFormat;
        }

        if ($global) {
            $this->mappedFields[] = $mappedField;
        }


        return $mappedField;
    }

    public function mapFieldType(string $fieldType, bool $global = true): string
    {
        switch ($fieldType) {
            case 'editor':
                $fieldType = 'wysiwyg';
                break;
            case 'posts':
            case 'post':
                $fieldType = 'post_object';
                break;
            case 'terms':
            case 'term':
                $fieldType = 'taxonomy';
                break;
        }

        return $fieldType;
    }

    public function mapSection($section, bool $global = true): array
    {
        $mappedSection = [
            'key' => $this->prefixId('section', $section->getId()),
            'name' => $section->getId(),
            '_name' => $section->getId(),
            'label' => $section->getLabel(),
            'type' => 'group',
            'layout' => 'block'
        ];

        if ($section->isNotEmpty()) {
            $mappedSection['sub_fields'] = $this->map($section, false);
        }

        if ($global) {
            $this->mappedFields[] = $mappedSection;
        }

        return $mappedSection;
    }

    public function mapTab($tab, bool $global = true): array
    {
        $mappedTab = [
            'key' => $this->prefixId('tab', $tab->getId()),
            'label' => $tab->getLabel(),
            'name' => '',
            'type' => 'tab',
            'placement' => 'top',
            'endpoint' => 0
        ];

        if ($global) {
            $this->mappedFields[] = $mappedTab;
        }

        if ($tab->isNotEmpty()) {
            $this->map($tab);
        }

        return $mappedTab;
    }

    public function prefixId(string $type, string $key): string
    {
        $prefix = !empty($this->keyPrefix) ? '_' . $this->keyPrefix : '';

        return $type . $prefix . '_' . $key;
    }

    public function prefixName(string $name): string
    {
        $prefix = !empty($this->namePrefix) ? $this->namePrefix . '_' : '';
        return $prefix . $name;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @private
     * @param string[][][] $conditionalLogic
     * @return string[][][]
     */
    public function transformKeysConditionalLogic(array $conditionalLogic): array
    {
        foreach ($conditionalLogic as $groupIndex => $conditions) {
            foreach ($conditions as $conditionIndex => $condition) {
                $prefix = ($this->keyPrefix) ? $this->keyPrefix . '_' : '';
                $fieldKey = 'field_' . $prefix . $condition['field'];

                if ($this->keySuffix) {
                    $fieldKey .= '_' . $this->keySuffix;
                }

                if ($this->getContext()) {
                    $fieldKey .= '_' . $this->getContext();
                }

                $conditions[$conditionIndex]['field'] = $fieldKey;
            }

            $conditionalLogic[$groupIndex] = $conditions;
        }

        return $conditionalLogic;
    }
}
