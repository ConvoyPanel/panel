/** One value a provisioner asks for. The script reads it as `CONVOY_FIELD_<KEY>`. */
export interface ProvisionerField {
    key: string
    label: string
    required: boolean
}

/** A script that runs inside a server on its first boot. */
export interface Provisioner {
    uuid: string
    name: string
    description: string | null
    script: string
    fields: ProvisionerField[]
    /** How many images run it. */
    imageCount: number
    createdAt: Date
    updatedAt: Date
}
