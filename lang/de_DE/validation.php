<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Das :attribute muss akzeptiert werden.',
    'accepted_if' => 'Das:attribute muss akzeptiert werden wenn :other ist :value.',
    'active_url' => 'Das :attributeist keine valide URL.',
    'after' => 'Das :attribute muss an einem Datum nach den :date liegen.',
    'after_or_equal' => 'Das :attribute muss ein Datum nach oder gleich mit :date sein.',
    'alpha' => 'Das :attribute darf nur Buchstaben enthalten.',
    'alpha_dash' => 'Das :attribute darf nur Buchstaben, Nummern, Binde- und Unterstriche erhalten.',
    'alpha_num' => 'Das :attribute darf nur Buchstaben und Zahlen enthalten.',
    'array' => 'Das :attribute muss ein Array sein.',
    'before' => 'Das :attribute muss ein Datum vor :date sein.',
    'before_or_equal' => 'Das :attribute muss ein Datum vor oder gleich :date sein.',
    'between' => [
        'array' => 'Das :attribute muss zwischen :min und :max Artikel haben.',
        'file' => 'Das :attribute muss zwischen :min und :max Kilobyte sein.',
        'numeric' => 'Das :attribute muss zwischen :min und :max sein.',
        'string' => 'Das :attribute muss zwischen :min und :max Zeichen sein.',
    ],
    'boolean' => 'Das :attribute Feld muss wahr oder falsch sein.',
    'confirmed' => 'Die :attribute Bestätigung stimmt nicht überein.',
    'current_password' => 'Das Passwort ist falsch.',
    'date' => 'Das :attribute ist kein gültiges Datum.',
    'date_equals' => 'Das :attribute muss ein Datum gleich mit :date sein.',
    'date_format' => 'Das :attribute entspricht nicht dem Format: :format.',
    'declined' => 'Das :attribute muss abgelehnt werden.',
    'declined_if' => 'Das :attribute muss abgelehnt werden wenn :other :value ist.',
    'different' => 'Das :attribute und :other müssen unterschiedlich sein.',
    'digits' => 'Das :attribute muss :digits Ziffern haben.',
    'digits_between' => 'Das :attribute muss zwischen :min und :max Ziffern sein.',
    'dimensions' => 'Das :attribute hat ungültige Bildabmessungen.',
    'distinct' => 'Das :attribute hat einen doppelten Wert.',
    'doesnt_start_with' => 'Das :attribute darf nicht mit einem der folgenden Werte beginnen: :values.',
    'email' => 'Das :attribute muss eine gültige Email-Adresse sein.',
    'ends_with' => 'Das :attribute darf nicht mit einem der folgenden Werte enden: :values.',
    'english_keyboard_characters' => 'Das :attribute muss Zeichen von der englischen Tastatur enthalten.',
    'enum' => 'Das gewählte :attribute ist ungültig.',
    'exists' => 'Das gewählte :attribute ist ungültig.',
    'file' => 'Das :attribute muss eine Datei sein.',
    'filled' => 'Das :attribute Feld muss einen Wert haben.',
    'fqdn' => [
        'https_and_ip' => 'Das :attribute darf keine IP-Adresse sein, wenn HTTPS aktiviert ist.',
        'unresolvable' => 'Das :attribute ist keine gültige IP-Adresse.',
    ],
    'gt' => [
        'array' => 'Das :attribute muss mehr als :value Artikel haben.',
        'file' => 'Das :attribute muss größer als :value Kilobyte sein.',
        'numeric' => 'Das :attribute muss größer als :value sein.',
        'string' => 'Das :attribute muss größer als :value Zeichen sein.',
    ],
    'gte' => [
        'array' => 'Das :attribute muss :value Artikel oder mehr enthalten.',
        'file' => 'Das :attribute muss größer oder gleich :value Kilobyte sein.',
        'numeric' => 'Das :attribute muss größer oder gleich :value sein.',
        'string' => 'The :attribute must be greater than or equal to :value characters.',
    ],
    'hostname' => 'The :attribute must be a valid hostname.',
    'image' => 'The :attribute must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field does not exist in :other.',
    'integer' => 'The :attribute must be an integer.',
    'ip' => 'Das :attribute muss eine gültige IP-Adresse sein.',
    'ipv4' => 'Das :attribute muss eine gültige IPv4-Adresse sein.',
    'ipv6' => 'Das :attribute muss eine gültige IPv6-Adresse sein.',
    'json' => 'Das :attribute muss eine gültiger JSON-String sein.',
    'lt' => [
        'array' => 'The :attribute must have less than :value items.',
        'file' => 'Das :attribute darf nur weniger als :value Kilobyte sein.',
        'numeric' => 'Das :attribute darf nur weniger als :value sein.',
        'string' => 'Das :attribute darf nur weniger als :value Zeichen haben.',
    ],
    'lte' => [
        'array' => 'The :attribute must not have more than :value items.',
        'file' => 'The :attribute must be less than or equal to :value kilobytes.',
        'numeric' => 'The :attribute must be less than or equal to :value.',
        'string' => 'The :attribute must be less than or equal to :value characters.',
    ],
    'mac_address' => 'The :attribute must be a valid MAC address.',
    'max' => [
        'array' => 'The :attribute must not have more than :max items.',
        'file' => 'The :attribute must not be greater than :max kilobytes.',
        'numeric' => 'The :attribute must not be greater than :max.',
        'string' => 'The :attribute must not be greater than :max characters.',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'mimetypes' => 'The :attribute must be a file of type: :values.',
    'min' => [
        'array' => 'The :attribute must have at least :min items.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'numeric' => 'The :attribute must be at least :min.',
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'multiple_of' => 'The :attribute must be a multiple of :value.',
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute format is invalid.',
    'numeric' => 'The :attribute must be a number.',
    'password' => [
        'default' => 'The :attribute must contain 8 - 50 characters, 1 uppercase, 1 lowercase, 1 number and 1 special character.',
        'letters' => 'The :attribute must contain at least one letter.',
        'mixed' => 'The :attribute must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The :attribute must contain at least one number.',
        'symbols' => 'The :attribute must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],
    'present' => 'The :attribute field must be present.',
    'prohibited' => 'The :attribute field is prohibited.',
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.',
    'prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.',
    'prohibits' => 'The :attribute field prohibits :other from being present.',
    'regex' => 'The :attribute format is invalid.',
    'required' => 'The :attribute field is required.',
    'required_array_keys' => 'The :attribute field must contain entries for: :values.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute and :other must match.',
    'size' => [
        'array' => 'The :attribute must contain :size items.',
        'file' => 'The :attribute must be :size kilobytes.',
        'numeric' => 'The :attribute must be :size.',
        'string' => 'The :attribute must be :size characters.',
    ],
    'starts_with' => 'The :attribute must start with one of the following: :values.',
    'string' => 'The :attribute must be a string.',
    'timezone' => 'The :attribute must be a valid timezone.',
    'unique' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'url' => 'The :attribute must be a valid URL.',
    'uuid' => 'The :attribute must be a valid UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
