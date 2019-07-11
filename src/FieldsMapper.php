<?php
namespace OffbeatWP\AcfCore;

class FieldsMapper {
    public $form = [];
    public $mappedFields = [];
    public $keyPrefix = '';
    public $namePrefix = '';
    public $context = null;

    public function __construct($form, $keyPrefix = '')
    {
        $this->fields = $form;
        $this->keyPrefix = $keyPrefix;
    }

    public function map($form = null, $global = true)
    {
        $root = false;
        $mapping = [];        

        if(is_null($form)) {
            $root = true;
            $form = $this->fields;
        }

        if ($form->getType() == 'field') {
            $mapping[] = $this->mapField($form, $global);
        } else {
            $form->each(function ($entry) use (&$mapping, $global) {
                switch($entry->getType()) {
                    case 'form':
                        $formFields = $this->mapForm($entry, $global);

                        if (!empty($formFields)) foreach ($formFields as $formField) {
                            if ($global) {
                                $this->mappedFields[] = $formField;
                            } else {
                                $mapping[] = $formField;
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

    public function mapForm($form, $global = true)
    {
        $idPrefixes = [];
        $idPrefixes[] = $this->fields->getFieldPrefix();
        $idPrefixes[] = $form->getFieldPrefix();
        $idPrefixes = array_filter($idPrefixes);

        $fieldsMapper = new self($form, implode('_', $idPrefixes));

        if ($this->getContext()) {
            $fieldsMapper->setContext($this->getContext());
        }

        $mappedFields = $fieldsMapper->map();

        return $mappedFields;
    }

    public function mapField($field, $global)
    {
        $fieldType = $field->getType();

        if (method_exists($field, 'getFieldType')) {
            $fieldType = $field->getFieldType();
        }

        $key = $field->getAttribute('key');
        if (!$key) {
            $key = $this->prefixId('field', $field->getId());
        } else {
            $prefix = !empty($this->keyPrefix) ? $this->keyPrefix . '_' : '';
            $key = $prefix . $key;
        }

        if ($this->getContext()) {
            $key = $key . '_' . $this->getContext();
        }

        $mappedField = [
            'key'           => $key,
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

        if ($field->getAttribute('layout')) 
            $mappedField['layout'] = $field->getAttribute('layout');

        if ($field->getAttribute('class')) 
            $mappedField['wrapper']['class'] = $field->getAttribute('class');

        if ($field->getAttribute('width')) 
            $mappedField['wrapper']['width'] = $field->getAttribute('width');

        if ($field->getAttribute('id')) 
            $mappedField['wrapper']['id'] = $field->getAttribute('id');

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

         if ($returnFormat = $field->getAttribute('return_format')) {
            $mappedField['return_format'] = $returnFormat;
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
           'key'           => $this->prefixId('section', $section->getId()),
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
            'key'   => $this->prefixId('tab', $tab->getId()),
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

    public function prefixId($type, $key) {
        $prefix = !empty($this->keyPrefix) ? '_' . $this->keyPrefix : '';
        return $type . $prefix . '_' . $key;
    }

    public function prefixName($name) {
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
}