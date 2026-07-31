import { createNode, nodeSchema } from '@/features/nodes/api.ts'
import NodeFormToolbar from '@/features/nodes/components/NodeFormToolbar.tsx'
import ConnectionSection from '@/features/nodes/components/sections/ConnectionSection.tsx'
import GeneralSection from '@/features/nodes/components/sections/GeneralSection.tsx'
import SpecificationsSection from '@/features/nodes/components/sections/SpecificationsSection.tsx'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconCheck } from '@tabler/icons-react'
import { Link, createLazyFileRoute, useNavigate } from '@tanstack/react-router'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { buttonVariants } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { toast } from '@/components/ui/Toast'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/nodes/create')(
    {
        component: CreateNodePage,
    }
)

function CreateNodePage() {
    const navigate = useNavigate()

    const form = useForm<z.input<typeof nodeSchema>>({
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
            anchorId: 'none',
        } as unknown as z.input<typeof nodeSchema>,
    })

    const submit = async ({ memory, ...data }: z.infer<typeof nodeSchema>) => {
        try {
            const node = await createNode({
                memory: memory * 1024 * 1024,
                ...data,
            })

            toast.add({ title: 'Node created', type: 'success' })

            navigate({
                to: '/admin/nodes/$nodeId',
                replace: true,
                params: { nodeId: node.id.toString() },
            })
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({ title: 'Failed to create node', type: 'error' })
            console.error(e)
        }
    }

    return (
        <Form {...form}>
            <form onSubmit={form.handleSubmit(submit as any)}>
                {/* Capped so the fields keep a readable measure: AppLayout gives
                    the page up to 1600px, and a form stretched that far pulls
                    each label away from its own control. */}
                <div className={'mx-auto w-full max-w-4xl'}>
                    <NodeFormToolbar
                        title={'Add a new node'}
                        subtitle={
                            'Connect a Proxmox host and describe its capacity.'
                        }
                        actions={
                            <>
                                <Link
                                    to={'/admin/nodes'}
                                    className={buttonVariants({
                                        variant: 'ghost',
                                    })}
                                >
                                    Cancel
                                </Link>
                                <FormButton className={'flex'}>
                                    Add node <IconCheck className={'size-4'} />
                                </FormButton>
                            </>
                        }
                    />

                    <div className={'space-y-4 pt-4'}>
                        <GeneralSection />
                        <ConnectionSection mode={'create'} />
                        <SpecificationsSection />
                    </div>
                </div>
            </form>
        </Form>
    )
}
