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

    'accepted' => ':attribute必须接受。',
    'accepted_if' => '当:other为:value时，:attribute必须接受。',
    'active_url' => ':attribute不是一个有效的URL。',
    'after' => ':attribute必须是:date之后的日期。',
    'after_or_equal' => ':attribute必须是等于或在:date之后的日期。',
    'alpha' => ':attribute只能包含字母。',
    'alpha_dash' => ':attribute只能包含字母、数字、破折号和下划线。',
    'alpha_num' => ':attribute只能包含字母和数字。',
    'array' => ':attribute必须是一个数组。',
    'before' => ':attribute必须是:date之前的日期。',
    'before_or_equal' => ':attribute必须是等于或在:date之前的日期。',
    'between' => [
        'array' => ':attribute必须在:min和:max之间。',
        'file' => ':attribute必须在:min和:maxKib之间。',
        'numeric' => ':attribute必须在:min和:max之间。',
        'string' => ':attribute必须在:min和:max个字符之间。',
    ],
    'boolean' => ':attribute字段必须是true或false。',
    'confirmed' => 'The :attribute confirmation does not match.',
    'current_password' => '密码不正确。',
    'date' => ':attribute不是一个有效的日期。',
    'date_equals' => ':attribute必须是等于:date的日期。',
    'date_format' => ':attribute与格式:format不匹配。',
    'declined' => ':attribute必须被拒绝。',
    'declined_if' => '当:other为:value时，:attribute必须被拒绝。',
    'different' => ':attribute和:other必须不同。',
    'digits' => ':attribute必须是:digits位数字。',
    'digits_between' => ':attribute必须在:min和:max位数字之间。',
    'dimensions' => ':attribute的图像尺寸无效。',
    'distinct' => ':attribute字段具有重复值。',
    'doesnt_start_with' => ':attribute不能以以下之一开始: :values。',
    'email' => ':attribute必须是一个有效的电子邮件地址。',
    'hostname' => ':attribute必须是一个有效的主机名。',
    'ends_with' => ':attribute必须以以下之一结尾: :values。',
    'enum' => '所选:attribute无效。',
    'exists' => '所选:attribute无效。',
    'file' => ':attribute必须是一个文件。',
    'filled' => ':attribute字段必须有一个值。',
    'gt' => [
        'array' => ':attribute必须要超过:value项。',
        'file' => ':attribute必须大于:valueKib。',
        'numeric' => ':attribute必须大于:value。',
        'string' => ':attribute必须多于:value个字符。',
    ],
    'gte' => [
        'array' => ':attribute必须有:value项或更多。',
        'file' => ':attribute必须大于或等于:valueKib。',
        'numeric' => ':attribute必须大于或等于:value。',
        'string' => ':attribute必须大于或等于:value个字符。',
    ],
    'image' => ':attribute必须是一个图像。',
    'in' => '所选:attribute无效。',
    'in_array' => ':attribute字段不存在于:other中。',
    'integer' => ':attribute必须是一个整数。',
    'ip' => ':attribute必须是一个有效的IP地址。',
    'ipv4' => ':attribute必须是一个有效的IPv4地址。',
    'ipv6' => ':attribute必须是一个有效的IPv6地址。',
    'json' => ':attribute必须是一个有效的JSON字符串。',
    'lt' => [
        'array' => ':attribute必须少于:value项。',
        'file' => ':attribute必须小于:valueKib。',
        'numeric' => ':attribute必须小于:value。',
        'string' => ':attribute必须少于:value个字符。',
    ],
    'lte' => [
        'array' => ':attribute不能超过:value项。',
        'file' => ':attribute必须小于或等于:valueKib。',
        'numeric' => ':attribute必须小于或等于:value。',
        'string' => ':attribute必须小于或等于:value个字符。',
    ],
    'mac_address' => ':attribute必须是一个有效的MAC地址。',
    'max' => [
        'array' => ':attribute不能超过:max项。',
        'file' => ':attribute不能大于:maxKib。',
        'numeric' => ':attribute不能大于:max。',
        'string' => ':attribute不能大于:max个字符。',
    ],
    'mimes' => ':attribute必须是类型为:values的文件。',
    'mimetypes' => ':attribute必须是类型为:values的文件。',
    'min' => [
        'array' => ':attribute必须至少有:min项。',
        'file' => ':attribute必须至少为:minKib。',
        'numeric' => ':attribute必须至少为:min。',
        'string' => ':attribute必须至少为:min个字符。',
    ],
    'multiple_of' => ':attribute必须是:value的倍数。',
    'not_in' => '所选:attribute无效。',
    'not_regex' => ':attribute格式无效。',
    'numeric' => ':attribute必须是一个数字。',
    'password' => [
        'letters' => ':attribute必须至少包含一个字母。',
        'mixed' => ':attribute必须至少包含一个大写字母和一个小写字母。',
        'numbers' => ':attribute必须至少包含一个数字。',
        'symbols' => ':attribute必须至少包含一个符号。',
        'uncompromised' => '提供的:attribute出现在数据泄露中。请为:attribute选择一个不同的值。',
    ],
    'present' => ':attribute字段必须存在。',
    'prohibited' => ':attribute字段被禁止。',
    'prohibited_if' => '当:other为:value时，:attribute字段被禁止。',
    'prohibited_unless' => '除非:other在:values中，否则:attribute字段被禁止。',
    'prohibits' => ':attribute字段禁止:other出现。',
    'regex' => ':attribute格式无效。',
    'required' => ':attribute字段是必需的。',
    'required_array_keys' => ':attribute字段必须包含:values的条目。',
    'required_if' => '当:other为:value时，:attribute字段是必需的。',
    'required_unless' => '除非:other在:values中，否则:attribute字段是必需的。',
    'required_with' => '当:values存在时，:attribute字段是必需的。',
    'required_with_all' => '当:values存在时，:attribute字段是必需的。',
    'required_without' => '当:values不存在时，:attribute字段是必需的。',
    'required_without_all' => '当:values都不存在时，:attribute字段是必需的。',
    'same' => ':attribute和:other必须匹配。',
    'size' => [
        'array' => ':attribute必须包含:size项。',
        'file' => ':attribute必须为:sizeKib。',
        'numeric' => ':attribute必须为:size。',
        'string' => ':attribute必须为:size个字符。',
    ],
    'starts_with' => ':attribute必须以以下之一开头：:values。',
    'string' => ':attribute必须是一个字符串。',
    'timezone' => ':attribute必须是一个有效的时区。',
    'unique' => ':attribute已被使用。',
    'uploaded' => ':attribute上传失败。',
    'url' => ':attribute必须是一个有效的URL。',
    'uuid' => ':attribute必须是一个有效的UUID。',

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
