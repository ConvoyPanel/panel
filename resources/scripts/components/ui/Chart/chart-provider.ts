import * as React from 'react'

import { ChartConfig } from '@/components/ui/Chart/types.ts'


type ChartContextProps = {
    config: ChartConfig
}

export const ChartContext = React.createContext<ChartContextProps | null>(null)
