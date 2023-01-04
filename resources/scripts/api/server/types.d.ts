export type EloquentStatus =
    | 'installing'
    | 'install_failed'
    | 'suspended'
    | 'restoring_backup'
    | 'restoring_snapshot'
    | null
