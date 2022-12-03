import http from '@/api/http'

export interface BootOrderSettings {
    unusedDevices: string[]
    bootOrder: string[]
}

const rawDataToBootOrderSettings = (data: any): BootOrderSettings => ({
    unusedDevices: data.unused_devices,
    bootOrder: data.boot_order,
})

export default async (uuid: string): Promise<BootOrderSettings> => {
    const { data: { data } } = await http.get(`/api/client/servers/${uuid}/settings/hardware/boot-order`)

    return rawDataToBootOrderSettings(data)
}