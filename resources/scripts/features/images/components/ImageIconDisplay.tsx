import { ImageIcon } from '@/types/image.ts'
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

export const ImageIconMap: Record<ImageIcon, ElementType> = {
    [ImageIcon.UBUNTU]: IconBrandUbuntu,
    [ImageIcon.DEBIAN]: IconBrandDebian,
    [ImageIcon.CENTOS]: IconBrandCentos,
    [ImageIcon.FEDORA]: IconBrandFedora,
    [ImageIcon.WINDOWS]: IconBrandWindows,
    [ImageIcon.ROCKY_LINUX]: IconBrandRockyLinux,
    [ImageIcon.ALMALINUX]: IconBrandAlmaLinux,
    [ImageIcon.ALPINE_LINUX]: IconBrandAlpineLinux,
    [ImageIcon.ARCH_LINUX]: IconBrandArchLinux,
}

interface ImageIconDisplayProps {
    icon: ImageIcon | null
    className?: string
    defaultIcon?: ElementType
}

const ImageIconDisplay = ({
    icon,
    className,
    defaultIcon: DefaultIcon,
}: ImageIconDisplayProps) => {
    if (!icon) {
        return DefaultIcon ? <DefaultIcon className={className} /> : null
    }

    const IconComponent = ImageIconMap[icon] || IconBox

    return <IconComponent className={className} />
}

export default ImageIconDisplay
