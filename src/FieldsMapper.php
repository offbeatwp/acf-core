<?php
namespace OffbeatWP\AcfCore;

class FieldsMapper {
    public $form = [];
    public $mappedFields = [];
    public $keyPrefix = '';

    public function __construct($form, $keyPrefix = '')
    {
        $this->fields = $form;
        $this->keyPrefix = $keyPrefix;
    }

    public function map($form = null, $global = true)
    {
        $root = false;
        $mapping = null;        

        if(is_null($form)) {
            $root = true;
            $form = $this->fields;
        }

        if ($form->getType() == 'field') {
            $mapping[] = $this->mapField($form, $global);
        } else {
            $form->each(function ($entry) use (&$mapping, $global) {
                switch($entry->getType()) {
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

    public function mapField($field, $global)
    {
        $fieldType = $field->getType();

        if (method_exists($field, 'getFieldType')) {
            $fieldType = $field->getFieldType();
        }

        $mappedField = [
            'key'           => $this->suffixId('field', $field->getId()),
            'label'         => $field->getLabel(),
            'name'          => $field->getId(),
            'type'          => $this->mapFieldType($fieldType),
            'required'      => 0,
        ];

        if ($field->getAttribute('default')) 
            $mappedField['default_value'] = $field->getAttribute('default');

        if ($field->getAttribute('placeholder')) 
            $mappedField['placeholder'] = $field->getAttribute('placeholder');

        if ($field->getAttribute('multiple')) 
            $mappedField['multiple'] = $field->getAttribute('multiple');

        if ($field->getAttribute('class')) 
            $mappedField['wrapper']['class'] = $field->getAttribute('class');

        switch ($fieldType) {
            case 'repeater':
                $mappedField['layout'] = 'block';
                $mappedField['sub_fields'] = $this->map($field, false);

                if ($field->getAttribute('collapsed')) {
                    $mappedField['collapsed'] = $this->suffixId('field', $field->getAttribute('collapsed'));
                }
                break;
            case 'select':
            case 'checkbox':
                $mappedField['choices'] = $field->getOptions();
                $mappedField['return_format'] = 'value';
                break;
            case 'post_object':
                $mappedField['post_type'] = [];

                if ($field->getAttribute('post_types')) {
                    $mappedField['post_type'] = array_merge($mappedField['post_type'], $field->getAttribute('post_types'));
                }

                if (isset($field['data']) && !empty($field['data'])) {
                    $mappedField['post_type'][] = $field['data'];
                }

                $mappedField['return_format'] = 'id';
                break;
            case 'taxonomy':
                $mappedField['taxonomy'] = $field->getAttribute('taxonomies');
                $mappedField['return_format'] = 'id';
                break;
            case 'image':
                $mappedField['return_format'] = 'id';
                break;
            case 'flexible_content':
                $mappedField['layouts'] = $field->getAttribute('layouts');
                $mappedField['button_label'] = $field->getAttribute('button_label');
                break;
         }

         if ($global)
            $this->mappedFields[] = $mappedField;

        return $mappedField;
    }

    public function mapFieldType($fieldType, $global = true)
    {  
        switch ($fieldType) {
            case 'editor':
                $fieldType = 'wysiwyg';
                break;
            case 'posts':
                $fieldType = 'post_object';
                break;
            case 'terms':
                $fieldType = 'taxonomy';
                break;
        }
        return $fieldType;
    }

    public function mapSection($section, $global = true)
    {
        $mappedSection = [
           'key'           => $this->suffixId('section', $section->getId()),
           'name'          => $section->getId(),
           'label'         => $section->getLabel(),
           'type'          => 'group',
           'layout'        => 'block',
        ];

        if ($section->isNotEmpty()) {
           $mappedSection['sub_fields'] = $this->map($section, false);
        }

        if ($global)
           $this->mappedFields[] = $mappedSection;

        return $mappedSection;
        // return [];
    }

    public function mapTab($tab, $global = true)
    {
        $mappedTab = [
            'key'   => $this->suffixId('tab', $tab->getId()),
            'label' => $tab->getLabel(),
            'name'  => '',
            'type'  => 'tab',
            'placement' => 'top',
            'endpoint' => 0,
        ];

        if ($global)
            $this->mappedFields[] = $mappedTab;

        if ($tab->isNotEmpty()) {
            $this->map($tab);
        }

        return $mappedTab;
    }

    public function suffixId($type, $key) {
        $suffix = !empty($this->keyPrefix) ? '_' . $this->keyPrefix : '';
        return $type . $suffix . '_' . $key;
    }
}