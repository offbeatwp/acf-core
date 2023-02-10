<?php
namespace OffbeatWP\AcfCore\Fields;

use OffbeatWP\Form\Fields\AbstractField;

class AcfField extends AbstractField {
    public const FIELD_TYPE = 'acffield';

    public function getFieldType(): string
    {
        return self::FIELD_TYPE;
    }
}