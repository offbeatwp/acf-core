<?php
namespace OffbeatWP\AcfCore;

class ComponentFields {
    public static function get(?string $componentId, ?string $suffix = ''): array
    {
        $fields = [];
        $component = offbeat('components')->get($componentId);

        if (!method_exists($component, 'settings')) {
            return [];
        }
        $componentSettings = $component::settings();

        $formFields = $component::getForm();
        if (!$formFields) {
            $formFields = [];
        }

        if ($formFields) {
            $fieldsMapper = new FieldsMapper($formFields, $componentSettings['slug'], 'block');
            $mappedFields = $fieldsMapper->map();

            if ($mappedFields) {
                $fields = $mappedFields;
            }
        }

        $fields = self::normalizeFields($fields);        

        if ($suffix) {
            $fields = self::suffixFieldKeys($fields, $suffix);
        }

        return $fields;
    }

    public static function getAcfDefinedFields(string $component): ?array
    {
        $fieldGroups = acf_get_field_groups(['offbeatwp_component' => $component]);

        if (empty($fieldGroups)) {
            return null;
        }

        $fields = [];

        foreach ($fieldGroups as $fieldGroup) {
            $fieldGroupFields = acf_get_fields($fieldGroup['key']);

            if($fieldGroupFields) {
                $fields = array_merge($fields, $fieldGroupFields);
            }
        }

        return $fields;
    }

    public static function normalizeFields(array $fields): array
    {
        foreach ($fields as $fieldKey => $field) {
            if (isset($field['parent'])) {
                unset($fields[$fieldKey]['parent']);
            }

            if (isset($field['sub_fields']) && is_array($field['sub_fields'])) {
                $fields[$fieldKey]['sub_fields'] = self::normalizeFields($field['sub_fields']);
            }
        }

        return $fields;
    }


    public static function suffixFieldKeys(array $fields, string $suffix): array
    {
        foreach ($fields as $fieldKey => $field) {
            if (isset($field['key'])) {
                $fields[$fieldKey]['key'] .= "_{$suffix}";
            }

            if (isset($field['sub_fields']) && is_array($field['sub_fields'])) {
                $fields[$fieldKey]['sub_fields'] = self::suffixFieldKeys($field['sub_fields'], $suffix);
            }

            if (isset($field['conditional_logic']) && is_array($field['conditional_logic'])) {
                foreach ($field['conditional_logic'] as $conditionalLogicIndex => $conditionalLogicRules) {
                    foreach ($conditionalLogicRules as $conditionalLogicRuleKey => $conditionalLogicRule) {
                        $fields[$fieldKey]['conditional_logic'][$conditionalLogicIndex][$conditionalLogicRuleKey]['field'] .= "_{$suffix}";
                    }
                }
            }
        }

        return $fields;
    }
}
