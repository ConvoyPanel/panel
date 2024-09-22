import { Payload } from '@/lib/recharts.ts'

interface Props {
    x: number
    y: number
    payload: Payload<Date>
}

const XAxisHistoricalCpuTick = ({ x, y, payload }: Props) => {
    const time = payload.value.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    })

    return (
        <g className='recharts-layer recharts-cartesian-axis-tick'>
            <text
                height='50'
                orientation='bottom'
                width='664'
                x={x}
                y={y}
                textAnchor='end'
                className='recharts-text recharts-cartesian-axis-tick-value'
                transform='rotate(-45, 549.0289855072464, 351)'
            >
                <tspan x={x} dy='0.71em'>
                    {time}
                </tspan>
            </text>
        </g>
    )
}

export default XAxisHistoricalCpuTick
