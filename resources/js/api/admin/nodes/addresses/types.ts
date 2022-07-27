export interface Address {
    id: number
    server_id?: number
    node_id: number
    address: string
    subnet_mask: string
    gateway: string
    type: string
    created_at?: string
    updated_at?: string
}