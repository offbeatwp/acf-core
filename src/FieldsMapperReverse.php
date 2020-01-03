<?php
namespace OffbeatWP\AcfCore;

use \OffbeatWP\Form\Fields;
use \OffbeatWP\AcfCore\Fields\AcfField;

class FieldsMapperReverse {
    public static function map($fields, $form)
    {
        if (!empty($fields)) foreach($fields as $field) {
            $formField = null; 

            switch($field['type']) {
                case 'repeater':
                    $form->addRepeater($field['name'], $field['label']);

                    if (!isset($field['sub_fields'])) {
                        $field['sub_fields'] = [];
                    }

                    FieldsMapperReverse::map($field['sub_fields'], $form);

                    break;

                case 'text':
                    $formField = Fields\Text::make($field['name'], $field['label']);

                    break;
                case 'email':
                    $formField = Fields\Email::make($field['name'], $field['label']);

                    break;
                case 'number':
                    $formField = Fields\Number::make($field['name'], $field['label']);

                    break;

                case 'textarea':
                    $formField = Fields\Textarea::make($field['name'], $field['label']);

                    break;
                    

                case 'wysiwyg':
                    $formField = Fields\Editor::make($field['name'], $field['label']);

                    break;

                case 'select':
                    $formField = Fields\Select::make($field['name'], $field['label'])
                        ->addOptions($field['choices']);

                    break;

                case 'radio':
                    $formField = Fields\Radio::make($field['name'], $field['label'])
                        ->addOptions($field['choices']);

                    break;

                case 'button_group':
                    $formField = Fields\ButtonGroup::make($field['name'], $field['label'])
                        ->addOptions($field['choices']);

                    break;

                case 'link':
                    $formField = Fields\Link::make($field['name'], $field['label']);

                    break;

                case 'true_false':
                    $formField = Fields\TrueFalse::make($field['name'], $field['label']);

                    break;
                
                case 'image':
                    $formField = Fields\Image::make($field['name'], $field['label']);

                    break;
                    
                default:
                    $formField = AcfField::make($field['name'], $field['label']);
                    $formField->setAttribute('acffield', $field);

                    break;
            }

            if (isset($formField)) {
                if (isset($field['required'])) {
                    $formField->setRequired($field['required'] ? true : false);
                }                

                if (isset($field['key'])) {
                    $formField->setAttribute('key', $field['key']);
                }

                if (isset($field['layout'])) {
                    $formField->setAttribute('layout', $field['layout']);
                }

                if (isset($field['return_format'])) {
                    $formField->setAttribute('return_format', $field['return_format']);
                }

                if (isset($field['default_value'])) {
                    $formField->setAttribute('default', $field['default_value']);
                }

                if (isset($field['placeholder'])) {
                    $formField->setAttribute('placeholder', $field['placeholder']);
                }

                if (isset($field['conditional_logic']) && !empty($field['conditional_logic'])) {
                    $formField->setAttribute('conditional_logic', $field['conditional_logic']);
                }
        
                if (isset($field['wrapper'])) {
                    if (isset($field['wrapper']['width'])) {
                        $formField->setAttribute('width', $field['wrapper']['width']);
                    }

                    if (isset($field['wrapper']['class'])) {
                        $formField->setAttribute('class', $field['wrapper']['class']);
                    }

                    if (isset($field['wrapper']['id'])) {
                        $formField->setAttribute('id', $field['wrapper']['id']);
                    }
                }

                $form->addField($formField);
            }
        }
    }
}