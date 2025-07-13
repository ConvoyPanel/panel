import { Template } from '@/types/template.ts'

export interface TemplateGroup {
    uuid: string
    name: string
    description: string | null
    icon: TemplateIcon | null
    isAdminOnly: boolean
    templates?: Template[]
}

export enum TemplateIcon {
    UBUNTU = 'ubuntu',
    DEBIAN = 'debian',
    CENTOS = 'centos',
    FEDORA = 'fedora',
    ROCKY_LINUX = 'rocky_linux',
    ALMALINUX = 'almalinux',
    WINDOWS = 'windows',
    ALPINE_LINUX = 'alpine_linux',
    ARCH_LINUX = 'arch_linux',
}
