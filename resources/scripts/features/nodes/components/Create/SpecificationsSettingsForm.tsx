import byteSize from 'byte-size'
import { IconCpu, IconDeviceSdCard } from '@tabler/icons-react'
import { ReactNode, useState } from 'react'
import { useWatch } from 'react-hook-form'
import { z } from 'zod'

import { nodeSchema } from '@/features/nodes/api.ts'
import SectionRow from '@/features/nodes/components/Create/SectionRow.tsx'

import { Card } from '@/components/ui/Card'
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/InputGroup'

const iec = { units: 'iec' as const, precision: 1 }

const GroupHeader = ({
    icon,
    title,
    aside,
}: {
    icon: ReactNode
    title: string
    aside?: ReactNode
}) => (
    <div className={'mb-3 flex items-center gap-2'}>
        <span
            className={
                'flex size-6 items-center justify-center rounded-md bg-primary/10 text-primary'
            }
        >
            {icon}
        </span>
        <span className={'text-sm font-semibold'}>{title}</span>
        {aside && <div className={'ml-auto'}>{aside}</div>}
    </div>
)

/** Memory amount input with a MiB/GiB display toggle. The form value is always
 * stored in MiB; GiB is a convenience conversion for entry. */
const MemoryAmountField = () => {
    const [unit, setUnit] = useState<'MiB' | 'GiB'>('MiB')

    const toDisplay = (mib: number | string) => {
        if (mib === '' || mib === null || mib === undefined) return ''
        const n = Number(mib)
        if (Number.isNaN(n)) return ''
        return unit === 'MiB' ? String(n) : String(Math.round((n / 1024) * 100) / 100)
    }

    return (
        <FormField
            name={'memory'}
            render={({ field }) => (
                <FormItem>
                    <FormLabel>Amount</FormLabel>
                    <FormControl>
                        <InputGroup>
                            <InputGroupInput
                                type={'number'}
                                inputMode={'numeric'}
                                value={toDisplay(field.value as never)}
                                onBlur={field.onBlur}
                                onChange={e => {
                                    const v = e.target.value
                                    if (v === '') return field.onChange('')
                                    const num = Number(v)
                                    if (Number.isNaN(num)) return
                                    field.onChange(
                                        unit === 'MiB'
                                            ? Math.round(num)
                                            : Math.round(num * 1024)
                                    )
                                }}
                            />
                            {/* py-0 overrides the addon's default py-1.5, which
                                would make this 36px tall inside the h-8 group. */}
                            <InputGroupAddon
                                align={'inline-end'}
                                className={'py-0'}
                            >
                                <div
                                    className={
                                        'flex h-5 items-center rounded-md bg-muted p-0.5 text-xs font-medium'
                                    }
                                >
                                    {(['MiB', 'GiB'] as const).map(u => (
                                        <button
                                            key={u}
                                            type={'button'}
                                            onClick={() => setUnit(u)}
                                            className={
                                                'flex h-4 items-center rounded px-1.5 leading-none transition-colors ' +
                                                (unit === u
                                                    ? 'bg-background text-foreground shadow-sm'
                                                    : 'text-muted-foreground')
                                            }
                                        >
                                            {u}
                                        </button>
                                    ))}
                                </div>
                            </InputGroupAddon>
                        </InputGroup>
                    </FormControl>
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}

const MemoryResult = () => {
    const values = useWatch<z.infer<typeof nodeSchema>>()
    const memBytes = Number(values.memory) * 1024 * 1024
    const over = Number(values.memoryOverallocate)

    const hasMemory = Number.isFinite(memBytes) && memBytes > 0
    const overPct = Number.isFinite(over) ? over : 0
    const totalBytes = memBytes * (1 + overPct / 100)
    const basePct = hasMemory ? (memBytes / totalBytes) * 100 : 0

    const usable = byteSize(hasMemory ? totalBytes : 0, iec)
    const physical = byteSize(hasMemory ? memBytes : 0, iec)

    return (
        <Card className={'mt-3 p-3.5'}>
            {/* Empty leaves the track bare — filling it would render a full
                bar of overcommit for a node with no memory entered yet. */}
            <div className={'flex h-3 overflow-hidden rounded-full bg-muted'}>
                {hasMemory && (
                    <>
                        <span
                            className={'bg-primary'}
                            style={{ width: `${basePct}%` }}
                        />
                        <span
                            className={'bg-primary/30'}
                            style={{ width: `${100 - basePct}%` }}
                        />
                    </>
                )}
            </div>
            <div
                className={'mt-2.5 flex items-baseline justify-between gap-3'}
            >
                <p className={'flex items-baseline gap-1.5'}>
                    <span
                        className={
                            'font-mono text-2xl font-bold tracking-tight tabular-nums' +
                            (hasMemory ? '' : ' text-muted-foreground/40')
                        }
                    >
                        {hasMemory ? usable.value : '—'}
                    </span>
                    <span className={'text-xs text-muted-foreground'}>
                        {hasMemory ? `${usable.unit} usable` : 'usable'}
                    </span>
                </p>
                <p className={'text-xs text-muted-foreground'}>
                    {hasMemory ? `${physical.value} ${physical.unit} physical` : ''}
                    {hasMemory && overPct > 0 && (
                        <span className={'text-muted-foreground/70'}>
                            {' '}
                            +{overPct}%
                        </span>
                    )}
                </p>
            </div>
        </Card>
    )
}

const ProcessorTag = () => {
    const cpuCount = useWatch({ name: 'cpuCount' })
    const n = Number(cpuCount)
    if (!Number.isFinite(n) || n <= 0) return null
    return (
        <span
            className={
                'rounded-md bg-primary/10 px-2 py-0.5 font-mono text-xs font-semibold text-primary'
            }
        >
            = {n} vCPU
        </span>
    )
}

const SpecificationsSettingsForm = () => {
    return (
        <SectionRow
            title={'Capacity'}
            description={'Drives Convoy’s placement and overcommit checks.'}
        >
            <div>
                <GroupHeader
                    icon={<IconCpu className={'size-4'} />}
                    title={'Processor'}
                    aside={<ProcessorTag />}
                />
                {/* Only cpuCount is enforced (app/Rules/HasSufficientCPU.php);
                    sockets and cores are recorded for reference. The
                    descriptions say so, since three peer number boxes otherwise
                    imply all three carry equal weight. */}
                <div className={'grid grid-cols-3 gap-3'}>
                    <InputForm
                        name={'socketCount'}
                        label={'Sockets'}
                        type={'number'}
                        description={'Physical CPU sockets. For reference.'}
                    />
                    <InputForm
                        name={'coreCount'}
                        label={'Cores'}
                        type={'number'}
                        description={'Physical cores. For reference.'}
                    />
                    <InputForm
                        name={'cpuCount'}
                        label={'CPUs'}
                        type={'number'}
                        description={
                            'Logical threads. Caps the vCPUs a server can be given.'
                        }
                    />
                </div>
            </div>

            <div className={'mt-3'}>
                <GroupHeader
                    icon={<IconDeviceSdCard className={'size-4'} />}
                    title={'Memory'}
                />
                <div className={'grid gap-3 sm:grid-cols-[1fr_10rem]'}>
                    <MemoryAmountField />
                    <InputForm
                        name={'memoryOverallocate'}
                        label={'Overallocate (%)'}
                        type={'number'}
                    />
                </div>
                <MemoryResult />
            </div>
        </SectionRow>
    )
}

export default SpecificationsSettingsForm
