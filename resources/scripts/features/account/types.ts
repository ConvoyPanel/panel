export interface Passkey {
    id: number
    name: string
    lastUsedAt: Date | null
    createdAt: Date
}

export interface SSHKey {
    id: number
    name: string
    publicKey: string
    createdAt: Date
}
