import http from '@/api/http'
import { Disk, rawDataToDisk } from '@/api/server/useServerDetails'

export interface BootOrderSettings {
    unusedDevices: Disk[]
    bootOrder: Disk[]
}

const rawDataToBootOrderSettings = (data: any): BootOrderSettings => ({
    unusedDevices: data.unused_devices.map(rawDataToDisk),
    bootOrder: data.boot_order.map(rawDataToDisk),
})

export default async (uuid: string): Promise<BootOrderSettings> => {
    const {
        data: { data },
    } = await http.get(
        `/api/client/servers/${uuid}/settings/hardware/boot-order`
    )

    return rawDataToBootOrderSettings(data)
}