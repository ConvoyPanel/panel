<?php

return [
    'ip_allocation' => [
        'empty_state' => 'No hay direcciones asociadas a este servidor.',
    ],
    'display_info' => [
        'title' => 'Nombre del Servidor',
    ],
    'reinstall' => [
        'title' => 'Reinstalar Servidor',
        'description' => 'Empiece su servidor desde cero.',
        'modal' => [
            'title' => 'Confirmar Reinstalación',
            'description' => '¿Estás seguro de que quieres reinstalar este servidor? Se perderán todos los datos.',
        ],
    ],
    'isos' => [
        'title' => 'ISOs Disponibles',
        'empty' => 'No hay ISOs',
    ],
    'device_config' => [
        'title' => 'Configuración del Dispositivo',
        'current' => 'Orden de Arranque Actual (se utilizará primero el más alto)',
        'unused' => 'Dispositivos no Utilizados',
        'unused_empty' => 'No hay dispositivos sin utilizar.',
        'no_boot_device_warning' => 'No se ha configurado ningún dispositivo de arranque. Su VM no se iniciará.',
    ],
    'nameservers' => [
        'title' => 'Nameservers',
        'nameserver' => 'Nameserver :index',
        'add' => 'Nuevo Servidor de Nombres',
    ],
    'auth' => [
        'title' => 'Autentificación',
    ],
    'hardware' => [
        'bandwidth_used' => 'Ancho de Banda Usado',
        'bandwidth_alloted' => 'Ancho de Banda Asignado',
    ],
];
