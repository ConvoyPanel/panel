export interface Snapshot {
    id: number
    uuid: string
    serverId: number
    snapshotId: number | null
    name: string
    description: string
    isLocked: boolean
    errors: string | null
    size: number
    createdAt: Date
    children?: Snapshot[]
}
