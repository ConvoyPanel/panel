<?php

return [
    'errors' => [
        'invalid_type' => 'Oczekiwano {{expected}}, otrzymano {{received}}',
        'invalid_type_received_undefined' => 'Wymagane',
        'invalid_literal' => 'Nieprawidłowa wartość, oczekiwana {{expected}}',
        'unrecognized_keys' => 'Nierozpoznane klucze w obiekcie: {{- keys}}',
        'invalid_union' => 'Nieprawidłowe dane',
        'invalid_union_discriminator' => 'Nieprawidłowa wartość, oczekiwana {{- options}}',
        'invalid_enum_value' => 'Nieprawidłowa wartość. Oczekiwano {{- options}}, otrzymano \'{{received}}\'',
        'invalid_arguments' => 'Nieprawidłowe argumenty',
        'invalid_return_type' => 'Nieprawidłowy typ zwróconej funkcji',
        'invalid_date' => 'Nieprawidłowa data',
        'custom' => 'Nieprawidłowe dane',
        'invalid_intersection_types' => 'Nie można połączyć wyników',
        'not_multiple_of' => 'Liczba musi być wielokrotnością liczby {{multipleOf}}',
        'not_finite' => 'Liczba musi być skończona',
        'invalid_string' => [
            'email' => 'Nieprawidłowe {{validation}}',
            'url' => 'Nieprawidłowe {{validation}}',
            'uuid' => 'Nieprawidłowe {{validation}}',
            'cuid' => 'Nieprawidłowe {{validation}}',
            'regex' => 'Nieprawidłowe',
            'datetime' => 'Nieprawidłowe {{validation}}',
            'startsWith' => 'Nieprawidłowe dane: muszą się zacząć z "{{startsWith}}"',
            'endsWith' => 'Nieprawidłowe dane: muszą się kończyć z "{{endsWith}}"',
            'hostname' => 'Invalid hostname',
            'us_keyboard_characters' => 'Invalid US keyboard characters',
            'password' => 'Musi zawierać 8 znaków, jedną wielką literę, jedną małą literę, jedną cyfrę i jeden znak specjalny',
            'ip_address' => 'Invalid IP address',
            'mac_address' => 'Invalid MAC address',
        ],
        'too_small' => [
            'array' => [
                'exact' => 'Lista musi zawierać dokładnie {{minimum}} elementów',
                'inclusive' => 'Lista musi zawierać co najmniej {{minimum}} elementów',
                'not_inclusive' => 'Lista musi zawierać więcej niż {{minimum}} elementów',
            ],
            'string' => [
                'exact' => 'Tekst musi zawierać dokładnie {{minimum}} znaków',
                'inclusive' => 'Tekst musi zawierać co najmniej {{minimum}} znaków',
                'not_inclusive' => 'Tekst musi zawierać więcej niż {{minimum}} znaków',
            ],
            'number' => [
                'exact' => 'Liczba musi wynosić dokładnie {{minimum}}',
                'inclusive' => 'Liczba musi być większa lub równa {{minimum}}',
                'not_inclusive' => 'Liczba musi być większa od {{minimum}}',
            ],
            'set' => [
                'exact' => 'Nieprawidłowe dane',
                'inclusive' => 'Nieprawidłowe dane',
                'not_inclusive' => 'Nieprawidłowe dane',
            ],
            'date' => [
                'exact' => 'Data musi być dokładna {{- minimum, datetime}}',
                'inclusive' => 'Data musi być większa lub równa od {{- minimum, datetime}}',
                'not_inclusive' => 'Data musi być większa od {{- minimum, datetime}}',
            ],
        ],
        'too_big' => [
            'array' => [
                'exact' => 'Lista musi zawierać dokładnie {{maximum}} elementów',
                'inclusive' => 'Lista musi zawierać co najwyżej {{maximum}} elementów',
                'not_inclusive' => 'Lista musi zawierać mniej niż {{maximum}} elementów',
            ],
            'string' => [
                'exact' => 'Tekst musi zawierać dokładnie {{maximum}} znaków',
                'inclusive' => 'Tekst musi zawierać co najwyżej {{maximum}} znaków',
                'not_inclusive' => 'Tekst musi zawierać mniej niż {{maximum}} znaków',
            ],
            'number' => [
                'exact' => 'Liczba musi wynosić dokładnie {{maximum}}',
                'inclusive' => 'Liczba musi być mniejsza lub równa {{maximum}}',
                'not_inclusive' => 'Liczba musi być mniejsza od {{maximum}}',
            ],
            'set' => [
                'exact' => 'Nieprawidłowe dane',
                'inclusive' => 'Nieprawidłowe dane',
                'not_inclusive' => 'Nieprawidłowe dane',
            ],
            'date' => [
                'exact' => 'Data musi być dokładna {{- maximum, datetime}}',
                'inclusive' => 'Data musi być mniejsza lub równa od {{- maximum, datetime}}',
                'not_inclusive' => 'Data musi być mniejsza od {{- maximum, datetime}}',
            ],
        ],
    ],
    'validations' => [
        'email' => 'email',
        'url' => 'url',
        'uuid' => 'uuid',
        'cuid' => 'cuid',
        'regex' => 'regex',
        'datetime' => 'datetime',
    ],
    'types' => [
        'function' => 'function',
        'number' => 'number',
        'string' => 'string',
        'nan' => 'nan',
        'integer' => 'integer',
        'float' => 'float',
        'boolean' => 'boolean',
        'date' => 'date',
        'bigint' => 'bigint',
        'undefined' => 'undefined',
        'symbol' => 'symbol',
        'null' => 'null',
        'array' => 'array',
        'object' => 'object',
        'unknown' => 'unknown',
        'promise' => 'promise',
        'void' => 'void',
        'never' => 'never',
        'map' => 'map',
        'set' => 'set',
    ],
];
