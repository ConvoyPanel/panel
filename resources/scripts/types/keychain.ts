export interface SSHKey {
    id: number
    name: string
    // TODO: add linked servers field; editing the linked servers should push the updates to the VM
    createdAt: Date
}
