import { type SelectFormItem } from '@/components/ui/Forms'

/**
 * Proxmox's `ostype` values, with the names people use for them.
 *
 * The list is Proxmox's, but the labels are not: PVE describes these in its own
 * abbreviations, and an admin picking an operating system should be reading
 * "Windows Server 2022", not `win11`. The value is what matters — every
 * cloud-init decision branches on it, and Proxmox derives the cloud-init drive
 * type from it, so a wrong choice is a guest that never finishes provisioning.
 */
export const OSTYPE_ITEMS: SelectFormItem[] = [
    { value: 'l26', label: 'Linux (kernel 2.6 or newer)' },
    { value: 'l24', label: 'Linux (kernel 2.4)' },
    { value: 'win11', label: 'Windows 11 / Server 2022 & 2025' },
    { value: 'win10', label: 'Windows 10 / Server 2016 & 2019' },
    { value: 'win8', label: 'Windows 8 / Server 2012' },
    { value: 'win7', label: 'Windows 7 / Server 2008 R2' },
    { value: 'w2k8', label: 'Windows Server 2008' },
    { value: 'wvista', label: 'Windows Vista' },
    { value: 'wxp', label: 'Windows XP / Server 2003' },
    { value: 'w2k', label: 'Windows 2000' },
    { value: 'solaris', label: 'Solaris' },
    { value: 'other', label: 'Other' },
]
