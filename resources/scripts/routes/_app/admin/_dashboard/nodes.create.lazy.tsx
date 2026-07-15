import { cn } from '@/utils'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useHeadroom } from '@mantine/hooks'
import { IconCheck } from '@tabler/icons-react'
import { Link, createLazyFileRoute, useNavigate } from '@tanstack/react-router'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

import { createNode, nodeSchema } from '@/features/nodes/api.ts'

import ConnectionSettingsForm from '@/features/nodes/components/Create/ConnectionSettingsForm.tsx'
import GeneralSettingsForm from '@/features/nodes/components/Create/GeneralSettingsForm.tsx'
import SpecificationsSettingsForm from '@/features/nodes/components/Create/SpecificationsSettingsForm.tsx'

import { buttonVariants } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'

export const Route = createLazyFileRoute('/_app/admin/_dashboard/nodes/create')(
    {
        component: CreateNodePage,
    }
)

function CreateNodePage() {
    const navigate = useNavigate()

    // Headroom: the toolbar gets out of the way on the way down and comes back
    // the moment you scroll up, so the actions are always a flick away without
    // permanently eating vertical space. fixedAt keeps it put near the top,
    // where it has not started overlapping anything yet.
    const pinned = useHeadroom({ fixedAt: 120 })

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
        } as unknown as z.input<typeof nodeSchema>,
    })

    const submit = async ({ memory, ...data }: z.infer<typeof nodeSchema>) => {
        try {
            const node = await createNode({
                memory: memory * 1024 * 1024,
                ...data,
            })

            toast.success('Node created')

            navigate({
                to: '/admin/nodes/$nodeId',
                replace: true,
                params: { nodeId: node.id.toString() },
            })
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error('Failed to create node')
            console.error(e)
        }
    }

    return (
        <Form {...form}>
            <form
                onSubmit={form.handleSubmit(submit as any)}
                className={'w-full'}
            >
                {/*
                 * Opaque so rows can't show through while scrolling, and mixed
                 * to AppLayout's page tint (bg-muted/40 over bg-background)
                 * rather than bg-background — a plain `bg-muted/40` here would
                 * composite over the page's own tint and read as a darker band.
                 * `in srgb` because that is where the browser alpha-composites
                 * the page's own bg-muted/40; mixing in oklab lands elsewhere.
                 *
                 * top-14 clears the global Header, which is `sticky top-0 h-14`
                 * below sm; from sm up it is `static`, so we pin to 0 there.
                 */}
                <div
                    className={cn(
                        'sticky top-14 z-10 flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b bg-[color-mix(in_srgb,var(--muted)_40%,var(--background))] py-4 transition-transform duration-200 ease-out motion-reduce:transition-none sm:top-0',
                        // Unpinned it slides up by exactly its own height, which
                        // parks it behind the (opaque, z-30) header on mobile and
                        // off the top of the viewport on desktop.
                        pinned ? 'translate-y-0' : '-translate-y-full'
                    )}
                >
                    {/* basis-36 is the title's floor, not its width: it still
                        grows to fill the row. Below that the flex line can't fit
                        the actions, so they wrap to their own row instead of
                        crushing the title — one row on a phone, two on a 320px
                        screen, without a breakpoint guess. */}
                    <div className={'min-w-0 grow basis-36'}>
                        <h1
                            className={
                                'text-lg font-semibold tracking-tight'
                            }
                        >
                            Add a new node
                        </h1>
                        {/* Hidden below sm: on a phone this orienting line just
                            squeezes the title against the actions, and the bar is
                            sticky, so every row it costs is permanent. */}
                        <p
                            className={
                                'hidden text-sm text-muted-foreground sm:block'
                            }
                        >
                            Connect a Proxmox host and describe its capacity.
                        </p>
                    </div>
                    <div className={'ml-auto flex shrink-0 items-center gap-2'}>
                        <Link
                            to={'/admin/nodes'}
                            className={buttonVariants({
                                variant: 'ghost',
                                size: 'sm',
                            })}
                        >
                            Cancel
                        </Link>
                        <FormButton size={'sm'} className={'flex'}>
                            Add node <IconCheck className={'size-4'} />
                        </FormButton>
                    </div>
                </div>

                <div className={'divide-y'}>
                    <GeneralSettingsForm />
                    <ConnectionSettingsForm />
                    <SpecificationsSettingsForm />
                </div>
            </form>
        </Form>
    )
}
