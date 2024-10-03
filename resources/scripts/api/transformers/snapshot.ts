import { Snapshot } from '@/types/snapshot.ts'

export const rawDataToSnapshot = (data: any): Snapshot => {
    return {
        id: data.id,
        uuid: data.uuid,
        serverId: data.server_id,
        snapshotId: data.snapshot_id,
        name: data.name,
        description: data.description,
        isLocked: data.is_locked,
        errors: data.errors,
        size: data.size,
        createdAt: new Date(data.created_at),
        children: data.children
            ? data.children.map(rawDataToSnapshot)
            : undefined,
    }
}
