<?php
namespace OffbeatWP\AcfCore;

class ComponentFields {
    public static function get($componentId)
    {
        $fields = [];
        $component = offbeat('components')->get($componentId);

        if (!method_exists($component, 'settings')) return [];
        $componentSettings = $component::settings();

        $formFields = $component::getForm();
        if (empty($formFields)) $formFields = [];

        if (!empty($formFields)) {
            $fieldsMapper = new FieldsMapper($formFields, $componentSettings['slug']);
            $mappedFields = $fieldsMapper->map();

            if (!empty($mappedFields)) {
                $fields = $mappedFields;
            }
        }

        $acfDefinedFields = self::getAcfDefinedFields($componentId);

        if (!empty($acfDefinedFields)) {
            $fields = array_merge($acfDefinedFields, $fields);
        }

        return $fields;
    }

    public static function getAcfDefinedFields($component)
    {
        $fieldGroups = acf_get_field_groups(['offbeatwp_component' => $component]);

        if (empty($fieldGroups)) return false;

        $fields = [];

        foreach ($fieldGroups as $fieldGroup) {
            $fieldGroupFields = acf_get_fields($fieldGroup['key']);

            if(!empty($fieldGroupFields))
                $fields = array_merge($fields, $fieldGroupFields);
        }

        return $fields;
    }
}