import { IconWifi } from '@tabler/icons-react'
import { RadialBar, RadialBarChart } from 'recharts'

import StatisticCard from '@/components/interfaces/Client/Server/Overview/StatisticCard.tsx'

import { ChartConfig, ChartContainer } from '@/components/ui/Chart'


const chartConfig = {
    used: {
        label: 'Used',
        color: '#2563eb',
    },
} satisfies ChartConfig

const data = [{ fill: '#8884d8', name: 'Used', value: 20 }]

const BandwidthUsageCard = () => {
    return (
        <StatisticCard title={'Bandwidth Allowance'} icon={IconWifi}>
            <ChartContainer
                config={chartConfig}
                className={'min-h-[200px] w-full'}
            >
                <RadialBarChart
                    accessibilityLayer
                    cx='50%'
                    cy='50%'
                    innerRadius='60%'
                    outerRadius='80%'
                    barSize={50}
                    data={data}
                >
                    <RadialBar dataKey={'value'} cornerRadius={10} />
                </RadialBarChart>
            </ChartContainer>
        </StatisticCard>
    )
}

export default BandwidthUsageCard
