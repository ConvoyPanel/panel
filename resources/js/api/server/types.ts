export interface Server {
    id: number
    user_id: number
    node_id: number
    vmid: number
    name: string
    description?: string
    vnc_username?: string
    vnc_password?: string
    cloud_init_enabled: boolean
    is_os_template: boolean
    ip_address: string
    updated_at: string
    created_at: string
}