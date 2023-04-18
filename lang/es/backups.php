<?php

return [
    'new_backup' => 'Nueva Copia de Seguridad',
    'empty_state' => 'No hay copias de seguridad',
    'counter_tooltip' => 'Has hecho :count de :max copias de seguridad',
    'create_modal' => [
        'title' => 'Crear Copia de Seguridad',
        'description' => 'La creación de una copia de seguridad tomará una copia de los archivos de su servidor. Esto puede tomar un tiempo dependiendo del tamaño de su servidor.',
        'compression_type' => 'Tipo de Compresión',
        'mode' => 'Modo',
        'modes' => [
            'snapshot' => 'Instantánea',
            'suspend' => 'Suspender',
            'kill' => 'Matar'
        ]
    ],
    'delete_modal' => [
        'title' => 'Eliminar :name',
        'description' => '¿Estás seguro de que quieres borrar esta copia de seguridad?',
    ],
    'restore_modal' => [
        'title' => 'Restaurar desde :name',
        'description' => '¿Estás seguro de que quieres restaurar desde esta copia de seguridad?',
    ],
    'notices' => [
        'backup_deleted' => 'Eliminado :name',
        'backup_restored' => 'Comenzó la restauración del servidor de :name',
    ]
];