<?php

return [
    'create_server' => 'Create Server',
    'backup_limit' => 'Limite Copias de Seguridad',
    'bandwidth_limit' => 'Limite Ancho de Banda',
    'bandwidth_usage' => 'Bandwidth Usage',
    'snapshot_limit' => 'Snapshots Limit',
    'limit_placeholder' => 'Leave blank for no limit',
    'should_create_vm' => 'Debe crear una VM',
    'start_server_after_installing' => 'Start Server After Completing Installation',
    'vmid_placeholder' => 'Leave blank for random VMID',
    'create_modal' => [
        'title' => 'Crear un Servidor',
    ],
    'manage_server' => 'Gestionar Servidor',
    'server_build' => [
        'title' => 'Creación de Servidor',
    ],
    'server_info' => [
        'title' => 'Información del Servidor',
    ],
    'suspension' => [
        'title' => 'Suspensión',
        'description' => 'Cambia el estado de suspensión del servidor.',
        'not_suspended' => 'Este servidor no está suspendido',
        'suspended' => 'Este servidor está suspendido',
    ],
    'delete_server' => [
        'title' => 'Eliminar Servidor',
        'description' => 'El servidor se eliminará de Convoy. Las Copias de Seguridad y otros datos asociados serán destruidos. Sin embargo, puede marcar la casilla de abajo para mantener la máquina virtual y los datos en el nodo Proxmox.',
        'dont_purge' => 'No purgar VM y archivos relacionados',
    ],
];
