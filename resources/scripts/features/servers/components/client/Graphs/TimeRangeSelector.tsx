import type { TimeRange } from '@/features/servers/detail/api.ts'

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/Tabs'

const timeRangeItems = [
    { value: 'hour', label: 'Hourly' },
    { value: 'day', label: 'Daily' },
    { value: 'week', label: 'Weekly' },
    { value: 'month', label: 'Monthly' },
    { value: 'year', label: 'Yearly' },
] as const

interface Props {
    from: TimeRange
    setFrom: (from: TimeRange) => void
}

const TimeRangeSelector = ({ from, setFrom }: Props) => {
    return (
        <>
            <Tabs
                value={from}
                onValueChange={val => setFrom(val as TimeRange)}
                className={'mb-5 hidden @sm:block'}
            >
                <TabsList>
                    <TabsTrigger value='hour'>Hourly</TabsTrigger>
                    <TabsTrigger value='day'>Daily</TabsTrigger>
                    <TabsTrigger value='week'>Weekly</TabsTrigger>
                    <TabsTrigger value='month'>Monthly</TabsTrigger>
                    <TabsTrigger value='year'>Yearly</TabsTrigger>
                </TabsList>
            </Tabs>
            <Select
                items={timeRangeItems}
                value={from}
                onValueChange={value => value && setFrom(value)}
            >
                <SelectTrigger className={'mb-5 flex w-56 @sm:hidden'}>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    {timeRangeItems.map(item => (
                        <SelectItem key={item.value} value={item.value}>
                            {item.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </>
    )
}

export default TimeRangeSelector
