export interface Template {
  id: number
  server_id: number
  visible: boolean
  created_at?: string
  updated_at?: string
}

export interface ServerTemplate {
  id: number
  server_id: number
  server: {
      id: number
      vmid: number
      name: string
  }
}