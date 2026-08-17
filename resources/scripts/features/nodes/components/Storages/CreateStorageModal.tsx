import {
    createStorage,
    getStoragesProxmox,
    registerStorageSchema,
    storageQueries,
    useStorages,
} from '@/features/nodes/storages/api.ts'
import { NodeStorage, StorageProxmox } from '@/features/nodes/types.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { Route as StorageRoute } from '@/routes/_app/admin/nodes.$nodeId/storages.tsx'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useQuery } from '@tanstack/react-query'
import byteSize from 'byte-size'
import { useId, useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldLabel,
    FieldTitle,
} from '@/components/ui/Field'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import { RadioGroup, RadioGroupItem } from '@/components/ui/RadioGroup'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'

const fmt = (bytes: number) => {
    const { value, unit } = byteSize(bytes, { units: 'iec', precision: 2 })

    return `${value} ${unit}`
}

/** What Proxmox says this storage may hold, in the panel's own words. */
const contentSummary = (storage: StorageProxmox) =>
    [
        storage.storesKvm && 'KVM',
        storage.storesLxc && 'LXC',
        storage.storesLxcTemplates && 'LXC templates',
        storage.storesBackups && 'Backups',
        storage.storesIso && 'ISO',
        storage.storesSnippets && 'Snippets',
    ]
        .filter(Boolean)
        .join(' · ')

const StorageChoice = ({ storage }: { storage: StorageProxmox }) => {
    const id = useId()

    return (
        <FieldLabel htmlFor={id}>
            <Field orientation={'horizontal'}>
                <RadioGroupItem id={id} value={storage.name} />
                <FieldContent>
                    <FieldTitle className={'font-mono'}>
                        {storage.name}
                    </FieldTitle>
                    <FieldDescription>
                        {fmt(storage.total)}
                        {contentSummary(storage) &&
                            ` · ${contentSummary(storage)}`}
                    </FieldDescription>
                </FieldContent>
            </Field>
        </FieldLabel>
    )
}

/**
 * Registers a storage Proxmox already has.
 *
 * Used to be a form asking for the name, size and content types by hand -- all
 * of which the host already knows, and any of which could be typed wrong and
 * then silently disagree with it forever. Those now come from the picked
 * storage, and the operator supplies only what Proxmox cannot know: what to call
 * it, and how much headroom Convoy should never allocate into.
 */
const CreateStorageModal = () => {
    const { nodeId } = StorageRoute.useParams()
    const id = Number(nodeId)
    const mutate = useQueryMutator<NodeStorage[]>(storageQueries.all(id))
    const [open, setOpen] = useState(false)
    const [picked, setPicked] = useState<string | null>(null)

    const { data: registered } = useStorages(id)

    const {
        data: reported,
        isLoading,
        isError,
    } = useQuery({
        queryKey: [...storageQueries.all(id), 'proxmox'] as const,
        queryFn: () => getStoragesProxmox(id),
        // Costs a live Proxmox call, so it waits until the dialog is opened.
        enabled: open,
    })

    // Anything already registered here is not a candidate; registering it twice
    // is the one thing the node's uniqueness rule would reject anyway.
    const candidates = (reported ?? []).filter(
        storage => !registered?.some(existing => existing.name === storage.name)
    )

    const form = useForm({
        resolver: zodResolver(registerStorageSchema),
        defaultValues: {
            displayName: '',
            description: '',
            reservedBytes: '',
        },
    })

    const submit = async ({
        reservedBytes,
        ...data
    }: z.infer<typeof registerStorageSchema>) => {
        const storage = candidates.find(candidate => candidate.name === picked)

        if (!storage) return

        try {
            const created = await createStorage(id, {
                ...data,
                name: storage.name,
                // Straight from the host, so the figure Convoy stores and the
                // one Proxmox reports cannot drift apart.
                size: storage.total,
                reservedBytes: reservedBytes
                    ? reservedBytes * 1024 * 1024
                    : null,
                storesKvm: storage.storesKvm,
                storesLxc: storage.storesLxc,
                storesLxcTemplates: storage.storesLxcTemplates,
                storesBackups: storage.storesBackups,
                storesIso: storage.storesIso,
                storesSnippets: storage.storesSnippets,
            })

            await mutate(existing => existing?.concat(created), false)

            form.reset()
            setPicked(null)
            setOpen(false)
            toast.add({ title: 'Storage registered', type: 'success' })
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.add({ title: 'Failed to save changes', type: 'error' })
            throw e
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={setOpen}>
            <ResponsiveDialogTrigger
                render={
                    <Button>
                        <IconPlus className={'size-4'} /> Add storage
                    </Button>
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Add storage</ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <ResponsiveDialogBody className={'space-y-4'}>
                            {isError ? (
                                /*
                                 * The list is the whole dialog, so a node that
                                 * cannot be reached has to say so. Falling
                                 * through to the empty state would report
                                 * "Proxmox has no storage" when the truth is
                                 * that nobody asked it successfully.
                                 */
                                <p className={'text-muted-foreground text-sm'}>
                                    Couldn&rsquo;t reach this node, so Convoy
                                    can&rsquo;t list what storage it has.
                                </p>
                            ) : isLoading ? (
                                <div className={'flex flex-col gap-2'}>
                                    {Array.from({ length: 3 }, (_, index) => (
                                        <Skeleton
                                            key={index}
                                            className={'h-14'}
                                        />
                                    ))}
                                </div>
                            ) : candidates.length === 0 ? (
                                <p className={'text-muted-foreground text-sm'}>
                                    {reported?.length
                                        ? 'Every storage Proxmox reports on this node is already registered.'
                                        : 'Proxmox reported no storage on this node.'}
                                </p>
                            ) : (
                                <RadioGroup
                                    className={'gap-2'}
                                    value={picked ?? ''}
                                    onValueChange={value =>
                                        setPicked(value as string)
                                    }
                                >
                                    {candidates.map(storage => (
                                        <StorageChoice
                                            key={storage.name}
                                            storage={storage}
                                        />
                                    ))}
                                </RadioGroup>
                            )}

                            {picked && (
                                <div className={'space-y-2'}>
                                    <InputForm
                                        name={'displayName'}
                                        label={'Display name'}
                                        description={
                                            'Optional. Shown instead of the Proxmox name.'
                                        }
                                    />
                                    <InputForm
                                        name={'description'}
                                        label={'Description'}
                                    />
                                    <InputForm
                                        name={'reservedBytes'}
                                        label={'Reserved headroom (MiB)'}
                                        description={
                                            'Free space Convoy will never allocate into. Leave blank for none.'
                                        }
                                    />
                                </div>
                            )}
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'}>Cancel</Button>
                                }
                            />
                            <FormButton disabled={!picked}>Add</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default CreateStorageModal
