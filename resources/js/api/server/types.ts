export interface Server {
    id: number
    uuid: string
    uuidShort: string
    user_id: number
    node_id: number
    vmid: number
    name: string
    description?: string
    status?: 'suspended' | 'installing'
    updated_at: string
    created_at: string
}