import { z } from 'zod'
import axios from '@/lib/axios.ts'
import { AddressVersion } from '@/types/address.ts'
import { rawDataToAddressBlock } from '@/api/transformers/address-block.ts'

export const addressBlockSchema = z.object({
    name: z.string().max(40),
    description: z.string().max(191),
    version: z.nativeEnum(AddressVersion),
    baseIp: z.string().min(1),
    gateway: z.string(),
    macAddress: z.string(),
    prefixLengthFrom: z.number().int().min(0).max(128),
    prefixLengthTo: z.number().int().min(0).max(128),
}).superRefine((data, ctx) => {
    // IPv4 validation regex
    const ipv4Regex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;

    // IPv6 validation regex
    const ipv6Regex = /^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))$/;

    if (data.version === AddressVersion.IPv4) {
        // Validate baseIp is IPv4
        if (!ipv4Regex.test(data.baseIp)) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message: "Base IP must be a valid IPv4 address",
                path: ["baseIp"]
            });
        }

        // Validate gateway is IPv4 if provided
        if (data.gateway.length > 0 && !ipv4Regex.test(data.gateway)) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message: "Gateway must be a valid IPv4 address",
                path: ["gateway"]
            });
        }
    } else if (data.version === AddressVersion.IPv6) {
        // Validate baseIp is IPv6
        if (!ipv6Regex.test(data.baseIp)) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message: "Base IP must be a valid IPv6 address",
                path: ["baseIp"]
            });
        }

        // Validate gateway is IPv6 if provided
        if (data.gateway.length > 0 && !ipv6Regex.test(data.gateway)) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message: "Gateway must be a valid IPv6 address",
                path: ["gateway"]
            });
        }
    }
})

const createAddressBlock = async (
    addressBlockGroupId: number,
    {baseIp, macAddress, prefixLengthFrom, prefixLengthTo, ...params}: z.infer<typeof addressBlockSchema>
) => {
    const {
        data: { data },
    } = await axios.post(`/api/admin/address-block-groups/${addressBlockGroupId}/address-blocks`, {
        ...params,
        base_ip: baseIp,
        mac_address: macAddress,
        prefix_length_from: prefixLengthFrom,
        prefix_length_to: prefixLengthTo,
    })

    return rawDataToAddressBlock(data)
}

export default createAddressBlock
