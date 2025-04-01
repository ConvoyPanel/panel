import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck } from '@tabler/icons-react'
import { createLazyFileRoute, useNavigate } from '@tanstack/react-router'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import createNode, { nodeSchema } from '@/api/admin/nodes/createNode.ts'

import FullscreenLayout from '@/components/layouts/FullscreenLayout.tsx'

import ConnectionSettingsForm from '@/components/interfaces/Admin/Node/Create/ConnectionSettingsForm.tsx'
import GeneralSettingsForm from '@/components/interfaces/Admin/Node/Create/GeneralSettingsForm.tsx'
import SpecificationsSettingsForm from '@/components/interfaces/Admin/Node/Create/SpecificationsSettingsForm.tsx'

import { Form, FormButton } from '@/components/ui/Form'
import { toast } from '@/components/ui/Toast'

export const Route = createLazyFileRoute(
    '/_app/admin/(fullscreen)/nodes/create'
)({
    component: CreateNodePage,
})

function CreateNodePage() {
    const navigate = useNavigate()

    const form = useForm({
        resolver: zodResolver(nodeSchema),
        defaultValues: {
            displayName: '',
            locationId: '',
            name: '',
            verifyTls: true,
            tokenId: '',
            tokenSecret: '',
            rootPrivileges: false,
            privilegeSeparationDisabled: false,
            fqdn: '',
            port: '8006',
            socketCount: '',
            coreCount: '',
            cpuCount: '',
            memory: '',
            memoryOverallocate: '',
        },
    })

    const submit = async (data: z.infer<typeof nodeSchema>) => {
        try {
            const node = await createNode(data)

            toast({
                description: 'Node created',
            })

            navigate({
                to: '/admin/nodes/$nodeId',
                replace: true,
                params: {
                    nodeId: node.id,
                },
                search: {
                    oobe: true,
                },
            })
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast({
                description: 'Failed to create node',
                variant: 'destructive',
            })
            console.error(e)
        }
    }

    return (
        <Form {...form}>
            <form onSubmit={form.handleSubmit(submit as any)}>
                <FullscreenLayout
                    backLink={'/admin/nodes'}
                    title={'Add a new node'}
                    headerActions={
                        <FormButton size={'sm'} className={'flex'}>
                            Add node <IconCheck className={'ml-2 size-4'} />
                        </FormButton>
                    }
                    center
                >
                    <div className={'flex w-full max-w-lg flex-col space-y-16'}>
                        <GeneralSettingsForm />
                        <ConnectionSettingsForm />
                        <SpecificationsSettingsForm />
                    </div>
                </FullscreenLayout>
            </form>
        </Form>
    )
}
