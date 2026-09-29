import {
    type AdoptableGuest,
    type AdoptionAddress,
    VERDICT_LABELS,
    adoptGuest,
    adoptionQueries,
    claimsAddress,
    useAdoptionPreview,
} from '@/features/adoption/api.ts'
import { serverQueries } from '@/features/servers/admin/api.ts'
import UserPicker from '@/features/servers/components/admin/Create/pickers/UserPicker.tsx'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconExclamationCircle } from '@tabler/icons-react'
import byteSize from 'byte-size'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { queryClient } from '@/lib/query-client.ts'

import { Alert, AlertDescription } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm } from '@/components/ui/Forms'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { toast } from '@/components/ui/Toast'

const schema = z.object({
    userId: z.string({ error: 'An owner is required.' }).min(1),
    name: z.string().max(40).optional(),
})

interface Props {
    guest: AdoptableGuest | null
    onClose: () => void
}

const formatBytes = (bytes: number) => {
    const size = byteSize(bytes, { units: 'iec', precision: 2 })

    return `${size.value} ${size.unit}`
}

const AddressRow = ({ address }: { address: AdoptionAddress }) => (
    <div className={'space-y-0.5'}>
        <div className={'flex items-center gap-2'}>
            <span className={'font-mono text-xs'}>
                {address.ip}/{address.prefixLength}
            </span>
            <Badge
                variant={
                    claimsAddress(address.verdict) ? 'secondary' : 'outline'
                }
            >
                {VERDICT_LABELS[address.verdict]}
            </Badge>
        </div>
        <p className={'text-muted-foreground text-xs'}>{address.reason}</p>
    </div>
)

/**
 * Adopts one observed guest, showing everything the panel read off it first.
 *
 * Nothing here writes to the guest. What it can claim it claims; what it cannot
 * it names, and the server is created anyway with those addresses unclaimed and
 * a flag saying so, because refusing a whole guest over one ambiguous address
 * is how an import feature stops being used.
 */
const AdoptGuestModal = ({ guest, onClose }: Props) => {
    const { data: preview, isPending } = useAdoptionPreview(
        guest?.nodeId ?? null,
        guest?.vmid ?? null
    )

    const form = useForm<z.input<typeof schema>>({
        resolver: zodResolver(schema),
        defaultValues: { userId: '', name: '' },
    })

    useEffect(() => {
        form.reset({ userId: '', name: guest?.name ?? '' })
    }, [guest?.nodeId, guest?.vmid])

    const submit = async (data: z.input<typeof schema>) => {
        if (!guest) return

        try {
            await adoptGuest(guest.nodeId, guest.vmid, {
                userId: Number(data.userId),
                name: data.name,
            })

            await queryClient.invalidateQueries({
                queryKey: adoptionQueries.all(),
            })
            await queryClient.invalidateQueries({
                queryKey: serverQueries.all(),
            })

            toast.add({ title: 'Guest adopted', type: 'success' })
            onClose()
        } catch (e) {
            handleFormErrors(e, form.setError, { user_id: 'userId' })
            toast.add({
                title: 'Could not adopt this guest',
                description: e instanceof Error ? e.message : undefined,
                type: 'error',
            })
            throw e
        }
    }

    const unclaimed = (preview?.addresses ?? []).filter(
        address => !claimsAddress(address.verdict)
    )

    return (
        <ResponsiveDialog
            open={guest !== null}
            onOpenChange={open => !open && onClose()}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Adopt {guest?.name ?? `VMID ${guest?.vmid}`}
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody className={'space-y-4'}>
                            {isPending && (
                                <Skeleton className={'h-32 w-full'} />
                            )}

                            {preview?.blockedReason && (
                                <Alert variant={'destructive'}>
                                    <IconExclamationCircle
                                        className={'size-4'}
                                    />
                                    <AlertDescription>
                                        {preview.blockedReason}
                                    </AlertDescription>
                                </Alert>
                            )}

                            {preview && (
                                <dl
                                    className={
                                        'grid grid-cols-2 gap-y-2 text-sm'
                                    }
                                >
                                    <dt
                                        className={
                                            'text-muted-foreground text-xs'
                                        }
                                    >
                                        Node
                                    </dt>
                                    <dd className={'text-right'}>
                                        {preview.nodeName}
                                    </dd>
                                    <dt
                                        className={
                                            'text-muted-foreground text-xs'
                                        }
                                    >
                                        VMID
                                    </dt>
                                    <dd className={'text-right font-mono'}>
                                        {preview.vmid}
                                    </dd>
                                    <dt
                                        className={
                                            'text-muted-foreground text-xs'
                                        }
                                    >
                                        Resources
                                    </dt>
                                    <dd className={'text-right'}>
                                        {preview.cpuCount} vCPU ·{' '}
                                        {formatBytes(preview.memory)} ·{' '}
                                        {formatBytes(preview.diskSize)}
                                    </dd>
                                    <dt
                                        className={
                                            'text-muted-foreground text-xs'
                                        }
                                    >
                                        Bridge
                                    </dt>
                                    <dd className={'text-right font-mono'}>
                                        {preview.bridge ?? '—'}
                                    </dd>
                                    <dt
                                        className={
                                            'text-muted-foreground text-xs'
                                        }
                                    >
                                        Storage
                                    </dt>
                                    <dd className={'text-right font-mono'}>
                                        {preview.storageName ?? '—'}
                                    </dd>
                                </dl>
                            )}

                            {preview && preview.addresses.length > 0 && (
                                <div
                                    className={
                                        'space-y-3 rounded-lg border p-3'
                                    }
                                >
                                    {preview.addresses.map(address => (
                                        <AddressRow
                                            key={address.ip}
                                            address={address}
                                        />
                                    ))}
                                </div>
                            )}

                            {preview?.hasUnmanagedIpConfig && (
                                <p className={'text-muted-foreground text-xs'}>
                                    Its address comes from somewhere other than
                                    Convoy. It is adopted with no addresses, and
                                    network sync leaves its ipconfig alone.
                                </p>
                            )}

                            {unclaimed.length > 0 && (
                                <p className={'text-muted-foreground text-xs'}>
                                    The server is created either way and flagged
                                    for you to resolve the{' '}
                                    {unclaimed.length === 1
                                        ? 'address'
                                        : 'addresses'}{' '}
                                    above.
                                </p>
                            )}

                            {preview &&
                                preview.ignoredInterfaces.length > 0 && (
                                    <p
                                        className={
                                            'text-muted-foreground text-xs'
                                        }
                                    >
                                        {preview.ignoredInterfaces.join(', ')}{' '}
                                        {preview.ignoredInterfaces.length === 1
                                            ? 'is'
                                            : 'are'}{' '}
                                        left alone. Convoy manages net0 only.
                                    </p>
                                )}

                            <UserPicker />
                            <InputForm
                                name={'name'}
                                label={'Name'}
                                description={
                                    'Defaults to the name Proxmox has.'
                                }
                            />
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton
                                disabled={
                                    isPending || preview?.blockedReason != null
                                }
                            >
                                Adopt
                            </FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default AdoptGuestModal
