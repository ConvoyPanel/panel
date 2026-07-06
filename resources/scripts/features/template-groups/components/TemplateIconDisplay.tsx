import { TemplateIcon } from '@/types/template-group.ts'
import {
    IconBox,
    IconBrandDebian,
    IconBrandUbuntu,
    IconBrandWindows,
} from '@tabler/icons-react'
import { ElementType } from 'react'

import IconBrandAlmaLinux from '@/components/ui/Icons/IconBrandAlmaLinux'
import IconBrandAlpineLinux from '@/components/ui/Icons/IconBrandAlpineLinux'
import IconBrandArchLinux from '@/components/ui/Icons/IconBrandArchLinux'
import IconBrandCentos from '@/components/ui/Icons/IconBrandCentos'
import IconBrandFedora from '@/components/ui/Icons/IconBrandFedora'
import IconBrandRockyLinux from '@/components/ui/Icons/IconBrandRockyLinux'

export const TemplateIconMap: Record<TemplateIcon, ElementType> = {
    [TemplateIcon.UBUNTU]: IconBrandUbuntu,
    [TemplateIcon.DEBIAN]: IconBrandDebian,
    [TemplateIcon.CENTOS]: IconBrandCentos,
    [TemplateIcon.FEDORA]: IconBrandFedora,
    [TemplateIcon.WINDOWS]: IconBrandWindows,
    [TemplateIcon.ROCKY_LINUX]: IconBrandRockyLinux,
    [TemplateIcon.ALMALINUX]: IconBrandAlmaLinux,
    [TemplateIcon.ALPINE_LINUX]: IconBrandAlpineLinux,
    [TemplateIcon.ARCH_LINUX]: IconBrandArchLinux,
}

interface TemplateIconDisplayProps {
    icon: TemplateIcon | null
    className?: string
    defaultIcon?: ElementType
}

const TemplateIconDisplay = ({
    icon,
    className,
    defaultIcon: DefaultIcon,
}: TemplateIconDisplayProps) => {
    if (!icon) {
        return DefaultIcon ? <DefaultIcon className={className} /> : null
    }

    const IconComponent = TemplateIconMap[icon] || IconBox

    return <IconComponent className={className} />
}

export default TemplateIconDisplay
