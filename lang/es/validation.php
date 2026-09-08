<?php

declare(strict_types=1);

return [
    'accepted' => 'Debe confirmar: :attribute.',
    'array' => ':attribute no es válido.',
    'boolean' => ':attribute debe ser sí o no.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => ':attribute no es una fecha válida.',
    'email' => ':attribute no es un correo válido.',
    'exists' => ':attribute no es válido.',
    'image' => ':attribute debe ser una imagen.',
    'integer' => ':attribute debe ser un número entero.',
    'max' => [
        'file' => ':attribute no puede superar :max KB.',
        'numeric' => ':attribute no puede ser mayor que :max.',
        'string' => ':attribute no puede tener más de :max caracteres.',
        'array' => ':attribute no puede tener más de :max elementos.',
    ],
    'min' => [
        'numeric' => ':attribute debe ser al menos :min.',
        'string' => ':attribute debe tener al menos :min caracteres.',
        'array' => ':attribute debe tener al menos :min elementos.',
        'file' => ':attribute debe ser de al menos :min KB.',
    ],
    'numeric' => ':attribute debe ser un número.',
    'required' => ':attribute es obligatorio.',
    'required_without' => ':attribute es obligatorio.',
    'string' => ':attribute no es válido.',
];
