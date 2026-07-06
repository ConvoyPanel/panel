import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck } from '@tabler/icons-react'
import { createLazyFileRoute, useNavigate } from '@tanstack/react-router'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import {
    createServer,
    serverSchema,
} from '@/features/servers/admin/api.ts'
import GeneralForm from '@/components/interfaces/Admin/Server/Create/GeneralForm'
import LimitsForm from '@/components/interfaces/Admin/Server/Create/LimitsForm'
import NetworkForm from '@/components/interfaces/Admin/Server/Create/NetworkForm'
import VmOptionsForm from '@/components/interfaces/Admin/Server/Create/VmOptionsForm'
import FullscreenLayout from '@/components/layouts/FullscreenLayout.tsx'
import { Form, FormButton } from '@/components/ui/Form'
import { useEffect } from 'react'

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
            backupCount: 0,
            backupSize: 0,
            networkInterfaceId: '',
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
            // Convert storage values from mebibytes to bytes (unless -1)
            const convertedData = {
                ...data,
                memory: data.memory !== -1 ? data.memory * 1024 * 1024 : data.memory,
                disk: data.disk !== -1 ? data.disk * 1024 * 1024 : data.disk,
                backupSize: data.backupSize !== -1 ? data.backupSize * 1024 * 1024 : data.backupSize,
            }

            await createServer(convertedData)

            toast.success('Server created')

            navigate({
                to: '/admin/servers',
                replace: true,
            })
        } catch (e) {
            handleFormErrors(e, form.setError, {
                'limits.cpu': 'cpu',
                'limits.memory': 'memory',
                'limits.disk': 'disk',
                'limits.bandwidth': 'bandwidth',
                'limits.backups.count': 'backupCount',
                'limits.backups.size': 'backupSize',
                'limits.network_interface_id': 'networkInterfaceId',
                'limits.addresses_ipv4_count': 'addressesIpv4Count',
                'limits.addresses_ipv6_count': 'addressesIpv6Count',
                'limits.addresses': 'addresses',
            })
            toast.error('Failed to create server')
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
                        <FormButton size={'sm'} className={'flex'}>
                            Add server <IconCheck className={'ml-2 size-4'} />
                        </FormButton>
                    }
                    center
                >
                    <div
                        className={
                            'flex w-full max-w-lg flex-col space-y-16'
                        }
                    >
                        <GeneralForm />
                        <LimitsForm />
                        <NetworkForm />
                        <VmOptionsForm />
                    </div>
                </FullscreenLayout>
            </form>
        </Form>
    )
}
