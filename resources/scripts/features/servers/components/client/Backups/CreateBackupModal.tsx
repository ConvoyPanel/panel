import { Mutator } from '@/types/query.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { AxiosError } from 'axios'
import { useState, type ReactElement } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'

import {
    createBackup,
    createBackupDefaults,
    createBackupSchema,
    type CreateBackupPayload,
} from '@/features/servers/backups/api.ts'
import type { PaginatedBackups } from '@/features/servers/types.ts'

import { Button } from '@/components/ui/Button'
import {
    Collapsible,
    CollapsiblePanel,
    CollapsibleTrigger,
} from '@/components/ui/Collapsible'
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
import { Form, FormButton } from '@/components/ui/Form'
import { CheckboxForm, InputForm, SelectForm } from '@/components/ui/Forms'

// Mode decides what happens to a RUNNING guest, so the copy has to say so —
// "Kill" stops the VM for the duration of the backup.
const modeItems = [
    { value: 'snapshot', label: 'Snapshot — no downtime' },
    { value: 'suspend', label: 'Suspend — guest is paused' },
    { value: 'kill', label: 'Kill — guest is stopped' },
]

const compressionItems = [
    { value: 'zstd', label: 'ZSTD — fast, recommended' },
    { value: 'gzip', label: 'GZIP — slower, smaller' },
    { value: 'lzo', label: 'LZO — fastest, larger' },
    { value: 'none', label: 'None — no compression' },
]

// The backend's backup failures are curated and actionable — the throttle names
// its window, and a node with no backup-capable storage says so. handleFormErrors
// only maps 422 field errors, so surface the message for everything else rather
// than burying it under a generic toast.
const errorMessage = (e: unknown, fallback: string): string =>
    e instanceof AxiosError && e.response?.data?.message
        ? e.response.data.message
        : fallback

interface Props {
    serverUuid: string
    mutate: Mutator<PaginatedBackups>
    trigger?: ReactElement
}

const CreateBackupModal = ({ serverUuid, mutate, trigger }: Props) => {
    const [open, setOpen] = useState(false)

    const form = useForm<CreateBackupPayload>({
        resolver: zodResolver(createBackupSchema),
        defaultValues: createBackupDefaults,
    })

    const submit = async (data: CreateBackupPayload) => {
        try {
            const backup = await createBackup(serverUuid, data)

            await mutate(current => {
                if (!current) return

                return {
                    ...current,
                    items: [backup, ...current.items],
                    // A new backup counts against the quota immediately.
                    backupCount: current.backupCount + 1,
                }
            }, false)

            form.reset(createBackupDefaults)
            setOpen(false)
            toast.success('Backup started')
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error(errorMessage(e, 'Failed to create backup'))
            throw e
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={setOpen}>
            <ResponsiveDialogTrigger
                render={
                    (trigger as ReactElement) ?? (
                        <Button>
                            <IconPlus className={'size-4'} /> Create backup
                        </Button>
                    )
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Create Backup</ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody className={'space-y-4'}>
                            <InputForm name={'name'} label={'Name'} />
                            {/* Defaults (snapshot + zstd) cover the common case;
                                the rest stays out of the way until asked for. */}
                            <Collapsible>
                                <CollapsibleTrigger>Advanced</CollapsibleTrigger>
                                <CollapsiblePanel>
                                    <div className={'space-y-4 pt-3'}>
                                        <SelectForm
                                            name={'mode'}
                                            label={'Mode'}
                                            items={modeItems}
                                            description={
                                                'How the guest is handled while the backup runs.'
                                            }
                                        />
                                        <SelectForm
                                            name={'compressionType'}
                                            label={'Compression'}
                                            items={compressionItems}
                                        />
                                        <CheckboxForm
                                            name={'isLocked'}
                                            label={'Lock this backup'}
                                            description={
                                                'Protects the backup from automatic pruning.'
                                            }
                                        />
                                    </div>
                                </CollapsiblePanel>
                            </Collapsible>
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>Create backup</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default CreateBackupModal
