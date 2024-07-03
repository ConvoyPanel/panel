import { Icon, IconProps } from '@tabler/icons-react'
import { ForwardRefExoticComponent, RefAttributes } from 'react'

export type TablerIcon = ForwardRefExoticComponent<
    Omit<IconProps, 'ref'> & RefAttributes<Icon>
>
