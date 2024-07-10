import { LinkOptions } from '@tanstack/react-router'

import { TablerIcon } from '@/lib/tabler.ts'


export interface Route {
    icon: TablerIcon
    label: string
    path: string
    activeOptions?: LinkOptions['activeOptions']
}
