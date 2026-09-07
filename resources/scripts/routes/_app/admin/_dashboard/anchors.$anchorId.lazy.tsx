import { approveEnrollment, useEnrollment } from '@/features/anchors/api'
import OverallocatePresets from '@/features/anchors/components/OverallocatePresets.tsx'
import ReportedFactsCard from '@/features/anchors/components/ReportedFactsCard.tsx'
import { approveNodeSchema } from '@/features/anchors/types.ts'
import { useLocations } from '@/features/locations/api.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck } from '@tabler/icons-react'
import { createLazyFileRoute, useNavigate } from '@tanstack/react-router'
import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm, SelectForm } from '@/components/ui/Forms'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading, StatLabel } from '@/components/ui/Typography'

export const Route = createLazyFileRoute(
    '/_app/admin/_dashboard/anchors/$anchorId'
)({
    component: ApproveEnrollmentPage,
})

/**
 * Letting a machine in.
 *
 * Framed as one question rather than a form, because that is what it is: the
 * host has already answered everything except where it belongs and how hard you
 * are willing to push it. Its answers sit beside the question as a receipt, so
 * the two real decisions are not buried among fourteen pre-filled boxes.
 */
function ApproveEnrollmentPage() {
    const { anchorId } = Route.useParams()
    const navigate = useNavigate()
    const { data: enrollment, isPending } = useEnrollment(Number(anchorId))
    const { data: locations } = useLocations({})
    const [editingFacts, setEditingFacts] = useState(false)

    const form = useForm<z.input<typeof approveNodeSchema>>({
        resolver: zodResolver(approveNodeSchema),
        defaultValues: {
            locationId: '',
            memoryOverallocate: '0',
            relayId: 'none',
            fqdn: '',
            port: '8006',
            agentPublicUrl: '',
            tokenId: '',
            tokenSecret: '',
            displayName: '',
            name: '',
            socketCount: '',
            coreCount: '',
            cpuCount: '',
            memory: '',
        } as unknown as z.input<typeof approveNodeSchema>,
    })

    const { reset } = form

    // Filled once the enrollment lands rather than starting blank and jumping.
    useEffect(() => {
        if (!enrollment) return

        const s = enrollment.suggestions as Record<string, unknown>
        const pick = (...keys: string[]) => {
            for (const key of keys) {
                if (s[key] !== undefined && s[key] !== null) return String(s[key])
            }

            return ''
        }

        const fqdn = pick('fqdn')

        reset(current => ({
            ...current,
            displayName: pick('displayName', 'display_name'),
            name: pick('name'),
            fqdn,
            agentPublicUrl: fqdn ? `https://${fqdn}:2115` : '',
            socketCount: pick('socketCount', 'socket_count'),
            coreCount: pick('coreCount', 'core_count'),
            cpuCount: pick('cpuCount', 'cpu_count'),
            // Stored in bytes, shown in MiB like every other capacity field.
            // Rounded because a host's real total is rarely a whole MiB, and
            // "9070.078125" in a text box reads as a bug rather than a fact.
            memory: s.memory
                ? String(Math.round(Number(s.memory) / 1024 / 1024))
                : '',
        }))
    }, [enrollment, reset])

    const submit = async (data: z.infer<typeof approveNodeSchema>) => {
        try {
            const node = await approveEnrollment(Number(anchorId), {
                location_id: data.locationId,
                memory_overallocate: data.memoryOverallocate,
                relay_id: data.relayId === 'none' ? null : Number(data.relayId),
                fqdn: data.fqdn,
                port: data.port,
                agent_public_url: data.agentPublicUrl,
                token_id: data.tokenId,
                token_secret: data.tokenSecret,
                display_name: data.displayName,
                name: data.name,
                socket_count: data.socketCount,
                core_count: data.coreCount,
                cpu_count: data.cpuCount,
                memory: data.memory * 1024 * 1024,
            })

            toast.add({ title: 'Node added', type: 'success' })

            navigate({
                to: '/admin/nodes/$nodeId',
                replace: true,
                params: { nodeId: String(node.id) },
            })
        } catch (e) {
            handleFormErrors(e, form.setError)
            // The reported fields are hidden by default, so a validation error
            // on one of them would otherwise point at nothing.
            setEditingFacts(true)
            toast.add({ title: 'Could not approve this machine', type: 'error' })
        }
    }

    if (isPending || !enrollment) return <Skeleton className={'h-96 w-full'} />

    return (
        <Form {...form}>
            <form onSubmit={form.handleSubmit(submit as never)}>
                <div
                    className={
                        'mx-auto grid w-full max-w-5xl items-start gap-6 lg:grid-cols-5'
                    }
                >
                    <div
                        className={'flex flex-col gap-4 lg:col-span-3'}
                    >
                        <div>
                            <Heading>Let {enrollment.name} join?</Heading>
                        </div>

                        <Card>
                            <CardContent
                                className={'flex flex-col gap-5 p-5'}
                            >
                                <SelectForm
                                    name={'locationId'}
                                    label={'Which location?'}
                                    placeholder={'Select a location'}
                                    items={(locations?.items ?? []).map(
                                        location => ({
                                            label: location.shortCode,
                                            value: String(location.id),
                                        })
                                    )}
                                />

                                <OverallocatePresets
                                    name={'memoryOverallocate'}
                                />

                                <div
                                    className={
                                        'flex flex-col gap-3 border-t border-border pt-5'
                                    }
                                >
                                    <div
                                        className={
                                            'flex flex-wrap items-center gap-2'
                                        }
                                    >
                                        <span
                                            className={'text-sm font-medium'}
                                        >
                                            Proxmox API token
                                        </span>
                                        <StatLabel
                                            className={
                                                'bg-muted rounded px-1.5 py-0.5 text-xs'
                                            }
                                        >
                                            Goes away once the agent mints its
                                            own
                                        </StatLabel>
                                    </div>
                                    <div
                                        className={
                                            'grid gap-3 sm:grid-cols-2'
                                        }
                                    >
                                        <InputForm
                                            name={'tokenId'}
                                            label={'Token ID'}
                                        />
                                        <InputForm
                                            name={'tokenSecret'}
                                            label={'Token secret'}
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <div className={'flex flex-wrap gap-2'}>
                            <FormButton className={'flex'}>
                                Approve and add node
                                <IconCheck className={'size-4'} />
                            </FormButton>
                            <Button
                                type={'button'}
                                variant={'outline'}
                                onClick={() =>
                                    navigate({ to: '/admin/anchors' })
                                }
                            >
                                Cancel
                            </Button>
                        </div>
                    </div>

                    <div className={'flex flex-col gap-4 lg:col-span-2'}>
                        <ReportedFactsCard
                            enrollment={enrollment}
                            editing={editingFacts}
                            onEdit={() => setEditingFacts(value => !value)}
                        />

                        {editingFacts && (
                            <Card>
                                <CardContent
                                    className={'grid gap-3 p-5 sm:grid-cols-2'}
                                >
                                    <InputForm
                                        name={'displayName'}
                                        label={'Display name'}
                                    />
                                    <InputForm
                                        name={'name'}
                                        label={'Proxmox node name'}
                                    />
                                    <InputForm name={'fqdn'} label={'FQDN'} />
                                    <InputForm name={'port'} label={'Port'} />
                                    <InputForm
                                        name={'agentPublicUrl'}
                                        label={'Agent address'}
                                    />
                                    <InputForm
                                        name={'socketCount'}
                                        label={'Sockets'}
                                    />
                                    <InputForm
                                        name={'coreCount'}
                                        label={'Cores'}
                                    />
                                    <InputForm
                                        name={'cpuCount'}
                                        label={'Threads'}
                                    />
                                    <InputForm
                                        name={'memory'}
                                        label={'Memory (MiB)'}
                                    />
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </form>
        </Form>
    )
}
