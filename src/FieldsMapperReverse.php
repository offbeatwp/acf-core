<?php
namespace OffbeatWP\AcfCore;

use \OffbeatWP\Form\Fields;

class FieldsMapperReverse {
    public static function map($fields, $form)
    {
        $formField = null; 
        if (!empty($fields)) foreach($fields as $field) {
            switch($field['type']) {
                case 'repeater':
                    $form->addRepeater($field['name'], $field['label']);

                    FieldsMapperReverse::map($field['sub_fields'], $form);

                    break;

                    case 'clone':
                    // $form->addRepeater($field['name'], $field['label']);

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
                    

                case 'font-awesome':
                    // $form->addRepeater($field['name'], $field['label']);

                    break;

                case 'oembed':
                    // $form->addRepeater($field['name'], $field['label']);

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