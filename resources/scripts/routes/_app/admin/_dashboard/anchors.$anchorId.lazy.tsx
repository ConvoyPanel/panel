import { approveEnrollment, useEnrollment } from '@/features/anchors/api'
import { approveNodeSchema } from '@/features/anchors/types.ts'
import { useLocations } from '@/features/locations/api.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck } from '@tabler/icons-react'
import { Link, createLazyFileRoute, useNavigate } from '@tanstack/react-router'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { buttonVariants } from '@/components/ui/Button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/Card'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm, SelectForm } from '@/components/ui/Forms'
import FormToolbar from '@/components/ui/FormToolbar'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'
import { StatLabel } from '@/components/ui/Typography'

export const Route = createLazyFileRoute(
    '/_app/admin/_dashboard/anchors/$anchorId'
)({
    component: ApproveEnrollmentPage,
})

/**
 * Letting a machine in.
 *
 * Deliberately not "Add a node": the host has already told the panel what it
 * is. Everything here is either a decision it must not make for itself
 * (location, oversubscription) or a fact it could not know (how this panel
 * routes back to it). The rest is pre-filled from what it reported, and is on
 * screen so it can be corrected, not typed.
 */
function ApproveEnrollmentPage() {
    const { anchorId } = Route.useParams()
    const navigate = useNavigate()
    const { data: enrollment, isPending } = useEnrollment(Number(anchorId))
    const { data: locations } = useLocations({})

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

    // The suggestions arrive with the enrollment, so the form is filled once
    // the fetch lands rather than starting blank and jumping.
    useEffect(() => {
        if (!enrollment) return

        const s = enrollment.suggestions as Record<string, unknown>

        reset(current => ({
            ...current,
            displayName: String(s.displayName ?? s.display_name ?? ''),
            name: String(s.name ?? ''),
            fqdn: String(s.fqdn ?? ''),
            agentPublicUrl: s.fqdn ? `https://${String(s.fqdn)}:2115` : '',
            socketCount: String(s.socketCount ?? s.socket_count ?? ''),
            coreCount: String(s.coreCount ?? s.core_count ?? ''),
            cpuCount: String(s.cpuCount ?? s.cpu_count ?? ''),
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
            toast.add({ title: 'Could not approve this machine', type: 'error' })
        }
    }

    if (isPending || !enrollment) return <Skeleton className={'h-96 w-full'} />

    return (
        <Form {...form}>
            <form onSubmit={form.handleSubmit(submit as never)}>
                <div className={'mx-auto w-full max-w-4xl'}>
                    <FormToolbar
                        title={`Approve ${enrollment.name}`}
                        subtitle={
                            'This host has already described itself. Confirm what it reported and add what it could not know.'
                        }
                        actions={
                            <>
                                <Link
                                    to={'/admin/anchors'}
                                    className={buttonVariants({
                                        variant: 'ghost',
                                    })}
                                >
                                    Cancel
                                </Link>
                                <FormButton className={'flex'}>
                                    Approve <IconCheck className={'size-4'} />
                                </FormButton>
                            </>
                        }
                    />

                    <div className={'space-y-4 pt-4'}>
                        <Card>
                            <CardHeader>
                                <CardTitle>Your decisions</CardTitle>
                                <StatLabel>
                                    The host has no view on any of these.
                                </StatLabel>
                            </CardHeader>
                            <CardContent
                                className={'grid gap-4 sm:grid-cols-2'}
                            >
                                <SelectForm
                                    name={'locationId'}
                                    label={'Location'}
                                    placeholder={'Select a location'}
                                    items={(locations?.items ?? []).map(
                                        location => ({
                                            label: location.shortCode,
                                            value: String(location.id),
                                        })
                                    )}
                                />
                                <InputForm
                                    name={'memoryOverallocate'}
                                    label={'Memory overallocate (%)'}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Reaching it</CardTitle>
                                <StatLabel>
                                    How this panel gets to the Proxmox API and
                                    to the agent. The host cannot know how you
                                    route back to it.
                                </StatLabel>
                            </CardHeader>
                            <CardContent
                                className={'grid gap-4 sm:grid-cols-2'}
                            >
                                <InputForm name={'fqdn'} label={'FQDN'} />
                                <InputForm name={'port'} label={'Port'} />
                                <InputForm
                                    name={'agentPublicUrl'}
                                    label={'Agent address'}
                                />
                                <InputForm
                                    name={'tokenId'}
                                    label={'Proxmox token ID'}
                                />
                                <InputForm
                                    name={'tokenSecret'}
                                    label={'Proxmox token secret'}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>What it reported</CardTitle>
                                <StatLabel>
                                    Read off the host itself. Editable, because
                                    a report is evidence rather than authority.
                                </StatLabel>
                            </CardHeader>
                            <CardContent
                                className={'grid gap-4 sm:grid-cols-2'}
                            >
                                <InputForm
                                    name={'displayName'}
                                    label={'Display name'}
                                />
                                <InputForm
                                    name={'name'}
                                    label={'Proxmox node name'}
                                />
                                <InputForm
                                    name={'socketCount'}
                                    label={'Sockets'}
                                />
                                <InputForm name={'coreCount'} label={'Cores'} />
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
                    </div>
                </div>
            </form>
        </Form>
    )
}
