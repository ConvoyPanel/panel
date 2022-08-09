export interface Address {
    id: number
    server_id?: number
    node_id: number
    address: string
    cidr: string
    gateway: string
    mac_address?: string
    type: string
    created_at?: string
    updated_at?: string
}