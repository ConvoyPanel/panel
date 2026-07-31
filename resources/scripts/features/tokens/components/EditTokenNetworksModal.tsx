import {
    allowedNetworksSchema,
    updateTokenNetworks,
} from '@/features/tokens/api.ts'
import { type ApiKey, type PaginatedApiKeys } from '@/features/tokens/types.ts'
import type { Mutator } from '@/types/query.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { TextareaForm } from '@/components/ui/Forms'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

const schema = z.object({ allowedNetworks: allowedNetworksSchema })
type Input = z.infer<typeof schema>

interface Props {
    token: ApiKey | null
    onClose: () => void
    mutate: Mutator<PaginatedApiKeys>
}

const EditTokenNetworksModal = ({ token, onClose, mutate }: Props) => {
    const form = useForm<Input>({
        resolver: zodResolver(schema),
        defaultValues: { allowedNetworks: '' },
    })

    useEffect(() => {
        form.reset({
            allowedNetworks: token?.allowedNetworks.join('\n') ?? '',
        })
    }, [form, token])

    const submit = async ({ allowedNetworks }: Input) => {
        if (!token) return

        try {
            const updated = await updateTokenNetworks(token.id, allowedNetworks)

            await mutate(data =>
                data
                    ? {
                          ...data,
                          items: data.items.map(item =>
                              item.id === updated.id ? updated : item
                          ),
                      }
                    : data
            )

            onClose()
            toast.add({
                title: 'Network restrictions updated',
                type: 'success',
            })
        } catch (error) {
            handleFormErrors(error, form.setError, {
                allowed_networks: 'allowedNetworks',
            })
            toast.add({
                title: 'Failed to update network restrictions',
                type: 'error',
            })
        }
    }

    return (
        <ResponsiveDialog
            open={token !== null}
            onOpenChange={open => !open && onClose()}
        >
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Edit network access
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Restrict “{token?.name}” to known source addresses.
                        Changes apply immediately.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <ResponsiveDialogBody>
                            <TextareaForm
                                name={'allowedNetworks'}
                                label={'Allowed addresses and ranges'}
                                placeholder={'203.0.113.10\n2001:db8::/48'}
                                rows={7}
                                description={
                                    'Enter one IPv4 or IPv6 address or CIDR range per line. Leave empty to make the token unrestricted.'
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
                            <FormButton>Save changes</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default EditTokenNetworksModal
