import {
    createISO,
    isoQueries,
    isoSchema,
    queryRemoteFile,
    uploadISO,
} from '@/features/isos/api'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { CheckboxForm, InputForm } from '@/components/ui/Forms'
import { Input } from '@/components/ui/Input'
import { Label } from '@/components/ui/Label'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

/**
 * Adds an ISO to the library.
 *
 * No node is chosen and nothing is transferred here: the panel is only being
 * told what the ISO is and where the bytes are. The first node someone mounts
 * it on is the one that fetches it.
 */
const CreateISOModal = () => {
    const queryClient = useQueryClient()
    const [open, setOpen] = useState(false)
    const [uploading, setUploading] = useState(false)
    const [progress, setProgress] = useState(0)

    const form = useForm<z.input<typeof isoSchema>>({
        resolver: zodResolver(isoSchema),
        defaultValues: {
            name: '',
            fileName: '',
            url: null,
            path: null,
            sha256: null,
            size: null,
            hidden: false,
        },
    })

    const path = form.watch('path')

    /** One probe fills in the two fields nobody should be typing from a URL. */
    const probe = async (url: string) => {
        try {
            const meta = await queryRemoteFile(url)

            if (!form.getValues('fileName')) {
                form.setValue('fileName', meta.fileName)
            }

            form.setValue('size', meta.size)
        } catch {
            // A link Proxmox cannot inspect is still perfectly usable; the
            // admin just fills in the name themselves.
        }
    }

    const upload = async (file: File) => {
        setUploading(true)
        setProgress(0)

        try {
            const uploaded = await uploadISO(file, setProgress)

            form.setValue('url', null)
            form.setValue('path', uploaded.path)
            form.setValue('sha256', uploaded.sha256)
            form.setValue('size', uploaded.size)

            if (!form.getValues('fileName')) {
                form.setValue('fileName', uploaded.fileName)
            }
        } catch {
            toast.add({ title: 'Failed to upload the ISO', type: 'error' })
        } finally {
            setUploading(false)
        }
    }

    const submit = async (data: z.input<typeof isoSchema>) => {
        try {
            await createISO(data as z.infer<typeof isoSchema>)
            await queryClient.invalidateQueries({ queryKey: isoQueries.all() })

            form.reset()
            setOpen(false)
            toast.add({ title: 'ISO added', type: 'success' })
        } catch (e) {
            if (!handleFormErrors(e, form.setError)) {
                toast.add({ title: 'Failed to add the ISO', type: 'error' })
            }
        }
    }

    return (
        <ResponsiveDialog open={open} onOpenChange={setOpen}>
            <ResponsiveDialogTrigger
                render={
                    <Button>
                        <IconPlus className={'size-4'} /> Add ISO
                    </Button>
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Add an ISO</ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody className={'space-y-4'}>
                            <InputForm
                                name={'name'}
                                label={'Name'}
                                description={'What customers will see.'}
                            />

                            {path ? (
                                <p className={'text-muted-foreground text-sm'}>
                                    Uploaded. Convoy will serve this file to
                                    your nodes.
                                </p>
                            ) : (
                                <>
                                    <div className={'space-y-1.5'}>
                                        <Label>Link</Label>
                                        <Input
                                            placeholder={
                                                'https://…/debian-12.iso'
                                            }
                                            onChange={event => {
                                                form.setValue(
                                                    'url',
                                                    event.target.value || null
                                                )
                                            }}
                                            onBlur={event =>
                                                event.target.value &&
                                                probe(event.target.value)
                                            }
                                        />
                                        <p
                                            className={
                                                'text-muted-foreground text-xs'
                                            }
                                        >
                                            Any URL your nodes can reach.
                                        </p>
                                    </div>

                                    <div className={'space-y-1.5'}>
                                        <Label>Or upload one</Label>
                                        <Input
                                            type={'file'}
                                            accept={'.iso'}
                                            disabled={uploading}
                                            onChange={event => {
                                                const file =
                                                    event.target.files?.[0]
                                                if (file) void upload(file)
                                            }}
                                        />
                                        {uploading && (
                                            <p
                                                className={
                                                    'text-muted-foreground text-xs'
                                                }
                                            >
                                                Uploading…{' '}
                                                {Math.round(progress * 100)}%
                                            </p>
                                        )}
                                    </div>
                                </>
                            )}

                            <InputForm
                                name={'fileName'}
                                label={'File name'}
                                description={
                                    'What this is called on every node that fetches it.'
                                }
                            />
                            <CheckboxForm
                                name={'hidden'}
                                label={'Admin only'}
                                description={
                                    'Keeps it out of the list customers see.'
                                }
                            />
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter>
                            <FormButton disabled={uploading}>
                                Add ISO
                            </FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default CreateISOModal
