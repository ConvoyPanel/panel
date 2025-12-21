import { IconAlertTriangle, IconDatabase } from '@tabler/icons-react'
import byteSize from 'byte-size'

import useServerResources from '@/api/servers/use-server-resources.ts'
import useServerSWR from '@/api/servers/use-server-swr.ts'

import LinearProgressBar from '@/components/ui/Progress/LinearProgressBar.tsx'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/Tooltip'

import StatisticCard from './StatisticCard'

const StorageUsageCard = () => {
    const { data: server } = useServerSWR()
    const { data: resources, error, isLoading } = useServerResources()

    // If there's an error (e.g. 409 conflict from guest agent unavailable), we consider it unavailable.
    // Also if resources aren't loaded yet, it's not "available" in the sense of showing valid data,
    // but we might want to differentiate loading state.
    // Here, if we have data, we assume it's valid. If we have an error, it's unavailable.
    const isAvailable = !error && resources
    const limitBytes = server?.disk ?? 0

    const usedBytes = isAvailable ? (resources?.usedBytes ?? 0) : 0
    const totalBytes = isAvailable ? (resources?.totalBytes ?? 0) : limitBytes

    const used = byteSize(usedBytes, {
        units: 'iec',
        precision: 2,
    })
    const total = byteSize(totalBytes, {
        units: 'iec',
        precision: 2,
    })

    const usedPercent = totalBytes > 0 ? (usedBytes / totalBytes) * 100 : 0

    return (
        <StatisticCard
            title={
                <div className={'flex items-center gap-2'}>
                    Storage Usage
                    {!isAvailable && !isLoading && (
                        <TooltipProvider>
                            <Tooltip>
                                <TooltipTrigger>
                                    <IconAlertTriangle
                                        className={'h-4 w-4 text-yellow-500'}
                                    />
                                </TooltipTrigger>
                                <TooltipContent>
                                    Guest Agent unavailable. Showing disk limit.
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    )}
                </div>
            }
            icon={IconDatabase}
            className={'col-span-2 @sm:col-span-1'}
            footer={
                <LinearProgressBar
                    className={'bottom-0'}
                    value={isAvailable ? usedPercent : 0}
                    aria-label={`${usedPercent.toFixed(2)}% of your storage is used`}
                />
            }
        >
            <p>
                <span
                    className={
                        'text-lg font-semibold tracking-tight @sm:text-xl @xl:text-2xl'
                    }
                >
                    {isAvailable
                        ? `${used.value} ${used.unit} used`
                        : 'Usage unavailable'}
                </span>
                <span className={'block text-sm text-muted-foreground'}>
                    {isAvailable ? 'out of' : 'Limit:'} {total.value} {total.unit}
                    {isAvailable && ` • ${usedPercent.toFixed(2)}%`}
                </span>
            </p>
        </StatisticCard>
    )
}

export default StorageUsageCard
