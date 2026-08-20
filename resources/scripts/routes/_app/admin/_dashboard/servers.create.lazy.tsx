import { createServer, serverSchema } from '@/features/servers/admin/api.ts'
import GeneralSection from '@/features/servers/components/admin/Create/sections/GeneralSection.tsx'
import NetworkSection from '@/features/servers/components/admin/Create/sections/NetworkSection.tsx'
import OperatingSystemSection from '@/features/servers/components/admin/Create/sections/OperatingSystemSection.tsx'
import PresetSection from '@/features/servers/components/admin/Create/sections/PresetSection.tsx'
import ResourcesSection from '@/features/servers/components/admin/Create/sections/ResourcesSection.tsx'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck } from '@tabler/icons-react'
import { Link, createLazyFileRoute, useNavigate } from '@tanstack/react-router'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { buttonVariants } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import FormToolbar from '@/components/ui/FormToolbar'
import { toast } from '@/components/ui/Toast'

const CreateServerPage = () => {
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
            // -1, not 0: a left-alone field means "no ceiling", where 0
            // would hand the server a zero quota and a NIC capped at zero.
            bandwidth: -1,
            speedLimit: -1,
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
                bandwidth:
                    data.bandwidth !== -1
                        ? data.bandwidth * 1024 * 1024
                        : data.bandwidth,
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
                {/* Capped so the fields keep a readable measure: AppLayout gives
                    the page up to 1600px, and a form stretched that far pulls
                    each label away from its own control. */}
                <div className={'mx-auto w-full max-w-4xl'}>
                    <FormToolbar
                        title={'Add a new server'}
                        subtitle={
                            'Place a server on a node and describe what it gets.'
                        }
                        actions={
                            <>
                                <Link
                                    to={'/admin/servers'}
                                    className={buttonVariants({
                                        variant: 'ghost',
                                    })}
                                >
                                    Cancel
                                </Link>
                                <FormButton className={'flex'}>
                                    Add server{' '}
                                    <IconCheck className={'size-4'} />
                                </FormButton>
                            </>
                        }
                    />

                    <div className={'space-y-4 pt-4'}>
                        <PresetSection />
                        <GeneralSection />
                        <ResourcesSection />
                        <NetworkSection />
                        <OperatingSystemSection />
                    </div>
                </div>
            </form>
        </Form>
    )
}

export const Route = createLazyFileRoute(
    '/_app/admin/_dashboard/servers/create'
)({
    component: CreateServerPage,
})
