export interface Server {
    id: number
    user_id: number
    node_id: number
    vmid: number
    name: string
    description?: string
    installing: boolean
    updated_at: string
    created_at: string
}