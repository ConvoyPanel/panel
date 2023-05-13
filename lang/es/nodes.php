<?php

return [
    'create_node' => 'Create Node',
    'create_modal' => [
        'title' => 'Crear Nodo',
    ],

    'location_group' => 'Grupo Ubicación',
    'pve_name' => 'Nombre del Nodo en Proxmox',
    'override_creds' => 'Anular credenciales',
    'creds_warning' => 'Please disable privilege separation and grant root privileges.',
    'token_id' => 'Token ID',
    'secret' => 'Secreto',
    'port' => 'Puerto',
    'memory_allocation' => 'Asignación de Memoria',
    'memory_overallocation' => 'Sobreasignación de Memoria',
    'disk_allocation' => 'Asignación de Disco',
    'disk_overallocation' => 'Sobreasignación de Disco',
    'vm_storage' => 'Almacenamiento VM',
    'backup_storage' => 'Copias de Seguridad',
    'iso_storage' => 'Almacenamiento ISO',

    'create_template_modal' => [
        'title' => 'Crear una Plantilla',
    ],
    'new_template' => 'Nueva Plantilla',
    'create_template_group_modal' => [
        'title' => 'Crear un Grupo de Plantilla',
    ],
    'new_template_group' => 'Nuevo Grupo de Plantilla',

    'node_info' => [
        'title' => 'Información Nodo',
    ],
    'delete_node' => [
        'title' => 'Eliminar Nodo',
        'description' => 'El nodo se eliminará permanentemente de Convoy. Esta acción es irreversible y no se puede deshacer.',
        'has_servers_error' => 'No se puede eliminar un nodo que tenga servidores asignados.',
    ],
];
