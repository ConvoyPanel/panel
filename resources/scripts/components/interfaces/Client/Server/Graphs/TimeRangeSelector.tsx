import { TimeRange } from '@/api/servers/getStatistics.ts'

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/Tabs'

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
                value={from}
                onValueChange={val => setFrom(val as TimeRange)}
            >
                <SelectTrigger className={'mb-5 flex w-56 @sm:hidden'}>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={'hour'}>Hourly</SelectItem>
                    <SelectItem value={'day'}>Daily</SelectItem>
                    <SelectItem value={'week'}>Weekly</SelectItem>
                    <SelectItem value={'month'}>Monthly</SelectItem>
                    <SelectItem value={'year'}>Yearly</SelectItem>
                </SelectContent>
            </Select>
        </>
    )
}

export default TimeRangeSelector
