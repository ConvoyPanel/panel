import type { AnchorEnrollmentQueueItem } from '@/features/anchors/types.ts'
import { IconPencil } from '@tabler/icons-react'
import byteSize from 'byte-size'
import type { ReactNode } from 'react'

import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { StatLabel } from '@/components/ui/Typography'

interface Props {
    enrollment: AnchorEnrollmentQueueItem
    onEdit: () => void
    editing: boolean
}

/**
 * Everything the host said about itself, as a receipt rather than a form.
 *
 * Nothing here was typed, so nothing here gets an input by default: presenting
 * fourteen pre-filled boxes made the facts the machine already settled look
 * exactly as demanding as the two decisions only an operator can make. The
 * fields are one click away for the case that actually needs them — the host
 * being wrong, or the operator knowing better.
 */
const Row = ({ label, children }: { label: string; children: ReactNode }) => (
    <div
        className={
            'flex items-center justify-between gap-4 border-b border-border py-2.5 last:border-b-0'
        }
    >
        <dt className={'text-muted-foreground text-sm'}>{label}</dt>
        <dd className={'min-w-0 truncate text-sm'}>{children}</dd>
    </div>
)

const ReportedFactsCard = ({ enrollment, onEdit, editing }: Props) => {
    const facts = (enrollment.reportedFacts ?? {}) as Record<string, unknown>
    const cpu = (facts.cpu ?? {}) as Record<string, number>
    const memory =
        typeof facts.memory_bytes === 'number'
            ? byteSize(facts.memory_bytes, { units: 'iec' })
            : null

    return (
        <Card>
            <CardContent className={'p-5'}>
                <div
                    className={
                        'flex items-center justify-between gap-3 border-b border-border pb-3'
                    }
                >
                    <StatLabel className={'tracking-wide uppercase'}>
                        What it told us
                    </StatLabel>
                    {enrollment.compatibility === 'compatible' && (
                        <span
                            className={
                                'text-success flex items-center gap-1.5 text-xs font-medium'
                            }
                        >
                            <span
                                className={'bg-success size-1.5 rounded-full'}
                                aria-hidden
                            />
                            Live
                        </span>
                    )}
                </div>

                <dl className={'pt-1'}>
                    <Row label={'Hostname'}>
                        <span className={'font-mono'}>
                            {String(facts.hostname ?? enrollment.name)}
                        </span>
                    </Row>
                    {facts.pve_node_name ? (
                        <Row label={'Node name'}>
                            <span className={'font-mono'}>
                                {String(facts.pve_node_name)}
                            </span>
                        </Row>
                    ) : null}
                    {cpu.threads ? (
                        <Row label={'CPU'}>
                            {`${cpu.threads} threads`}
                            {cpu.sockets
                                ? ` · ${cpu.sockets} socket${cpu.sockets === 1 ? '' : 's'}`
                                : ''}
                        </Row>
                    ) : null}
                    {memory && (
                        <Row label={'Memory'}>
                            {`${memory.value} ${memory.unit}`}
                        </Row>
                    )}
                    {facts.observed_source_ip ? (
                        <Row label={'Seen from'}>
                            <span className={'font-mono'}>
                                {String(facts.observed_source_ip)}
                            </span>
                        </Row>
                    ) : null}
                    {enrollment.version && (
                        <Row label={'Agent'}>
                            <span className={'font-mono'}>
                                {enrollment.version}
                            </span>
                        </Row>
                    )}
                </dl>

                <Button
                    type={'button'}
                    variant={'outline'}
                    className={'mt-4 w-full'}
                    onClick={onEdit}
                >
                    <IconPencil className={'size-4'} />
                    {editing
                        ? 'Hide these fields'
                        : 'Something looks wrong — edit'}
                </Button>

                <p className={'text-muted-foreground mt-3 text-xs'}>
                    Nothing here was typed. Change it only if the host is wrong
                    or you know better.
                </p>
            </CardContent>
        </Card>
    )
}

export default ReportedFactsCard
