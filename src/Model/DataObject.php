<?php

namespace App\Model;

use App\Controller\MissingRequiredFieldException;
use JetBrains\PhpStorm\Pure;

class DataObject
{

    public function __construct(private object $data)
    {
    }

    /**
     * Get the field's value or default value if it does not exist.
     *
     * @param string $field
     * @param mixed|null $default
     * @return mixed
     */
    #[Pure] public function get(string $field, mixed $default = null): mixed
    {
        // Read field from data and use default value if it does not exist, or it is null
        $value = $this->data->$field ?? $default;

        // If the read value is an object, wrap it in another DataObject and return it; return the value itself otherwise.
        return is_object($value) ? new DataObject($value) : $value;
    }

    /**
     * Get the required field's value. If it does not exist throw a {@link MissingRequiredFieldException}.
     *
     * @param string $field
     * @return mixed
     * @throws MissingRequiredFieldException
     */
    public function getRequired(string $field): mixed
    {
        return $this->get($field)
            ?? throw new MissingRequiredFieldException($field);
    }
}
