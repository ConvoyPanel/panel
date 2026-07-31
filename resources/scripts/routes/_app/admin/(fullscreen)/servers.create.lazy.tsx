import { createServer, serverSchema } from '@/features/servers/admin/api.ts'
import GeneralForm from '@/features/servers/components/admin/Create/GeneralForm'
import LimitsForm from '@/features/servers/components/admin/Create/LimitsForm'
import NetworkForm from '@/features/servers/components/admin/Create/NetworkForm'
import SecondaryDisksForm from '@/features/servers/components/admin/Create/SecondaryDisksForm'
import VmOptionsForm from '@/features/servers/components/admin/Create/VmOptionsForm'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck } from '@tabler/icons-react'
import { createLazyFileRoute, useNavigate } from '@tanstack/react-router'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import FullscreenLayout from '@/components/layouts/FullscreenLayout.tsx'

import { Form, FormButton } from '@/components/ui/Form'
import { toast } from '@/components/ui/Toast'

export const Route = createLazyFileRoute(
    '/_app/admin/(fullscreen)/servers/create'
)({
    component: CreateServerPage,
})

function CreateServerPage() {
    const navigate = useNavigate()

    const form = useForm({
        resolver: zodResolver(serverSchema),
        defaultValues: {
            name: '',
            hostname: '',
            vmid: '',
            userId: '',
            nodeId: '',
            storageId: '',
            cpu: 1,
            memory: 1024,
            disk: 10240,
            bandwidth: 0,
            // '' (not 0) so a left-alone field stays "uncapped" rather than
            // capping every NIC at zero.
            speedLimit: '',
            disks: [],
            backupCount: 0,
            backupSize: 0,
            networkInterfaceId: '',
            vlanTag: '',
            addressesIpv4Count: 1,
            addressesIpv6Count: 0,
            addresses: [],
            deferredOsSelection: false,
            shouldCreateVm: true,
            accountPassword: '',
            templateUuid: '',
            startOnCompletion: true,
        },
    })

    const deferredOsSelection = form.watch('deferredOsSelection')
    useEffect(() => {
        if (deferredOsSelection) {
            form.setValue('shouldCreateVm', false)
            form.setValue('startOnCompletion', false)
            form.setValue('accountPassword', '')
            form.setValue('templateUuid', '')
        }
    }, [deferredOsSelection, form])

    const submit = async (data: z.infer<typeof serverSchema>) => {
        try {
            // Convert storage values from mebibytes to bytes (unless -1).
            // Secondary disks are entered in GiB (PVE allocates in whole GiB).
            const convertedData = {
                ...data,
                memory:
                    data.memory !== -1
                        ? data.memory * 1024 * 1024
                        : data.memory,
                disk: data.disk !== -1 ? data.disk * 1024 * 1024 : data.disk,
                backupSize:
                    data.backupSize !== -1
                        ? data.backupSize * 1024 * 1024
                        : data.backupSize,
                disks: data.disks?.map(d => ({
                    ...d,
                    size: d.size * 1024 * 1024 * 1024,
                })),
            }

            await createServer(convertedData)

            toast.add({ title: 'Server created', type: 'success' })

            navigate({
                to: '/admin/servers',
                replace: true,
            })
        } catch (e) {
            // Map each secondary-disk field back to its field-array input, e.g.
            // `limits.disks.0.storage_id` → `disks.0.storageId`.
            const diskFieldMapping: Record<string, string> = {}
            data.disks?.forEach((_, i) => {
                diskFieldMapping[`limits.disks.${i}.storage_id`] =
                    `disks.${i}.storageId`
                diskFieldMapping[`limits.disks.${i}.size`] = `disks.${i}.size`
            })

            handleFormErrors(e, form.setError, {
                'limits.cpu': 'cpu',
                'limits.memory': 'memory',
                'limits.disk': 'disk',
                'limits.bandwidth': 'bandwidth',
                'limits.backups.count': 'backupCount',
                'limits.backups.size': 'backupSize',
                'limits.network_interface_id': 'networkInterfaceId',
                'limits.vlan_tag': 'vlanTag',
                'limits.addresses_ipv4_count': 'addressesIpv4Count',
                'limits.addresses_ipv6_count': 'addressesIpv6Count',
                'limits.addresses': 'addresses',
                ...diskFieldMapping,
            })
            toast.add({ title: 'Failed to create server', type: 'error' })
            console.error(e)
        }
    }

    return (
        <Form {...form}>
            <form onSubmit={form.handleSubmit(submit)}>
                <FullscreenLayout
                    backLink={'/admin/servers'}
                    title={'Add a new server'}
                    headerActions={
                        <FormButton className={'flex'}>
                            Add server <IconCheck className={'size-4'} />
                        </FormButton>
                    }
                    center
                >
                    <div className={'flex w-full max-w-lg flex-col space-y-16'}>
                        <GeneralForm />
                        <LimitsForm />
                        <SecondaryDisksForm />
                        <NetworkForm />
                        <VmOptionsForm />
                    </div>
                </FullscreenLayout>
            </form>
        </Form>
    )
}
