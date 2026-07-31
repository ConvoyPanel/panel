import {
    addressBlockSchema,
    createAddressBlock,
} from '@/features/ipam/blocks/api.ts'
import { PaginatedAddressBlocks } from '@/types/address-block.ts'
import { AddressVersion } from '@/types/address.ts'
import { Mutator } from '@/types/query.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'

import { Button } from '@/components/ui/Button'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm, TextareaForm } from '@/components/ui/Forms'
import TabForm from '@/components/ui/Forms/TabForm.tsx'
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
import { TabsTrigger } from '@/components/ui/Tabs'
import { toast } from '@/components/ui/Toast'

interface Props {
    addressBlockGroupId: number
    mutate: Mutator<PaginatedAddressBlocks>
}

const CreateAddressBlockModal = ({ addressBlockGroupId, mutate }: Props) => {
    const [open, setOpen] = useState(false)

    const form = useForm({
        resolver: zodResolver(addressBlockSchema),
        defaultValues: {
            name: '',
            description: '',
            version: AddressVersion.IPv4,
            baseIp: '',
            gateway: '',
            macAddress: '',
            prefixLengthFrom: 24,
            // Hand out individual addresses by default. Defaulting the output prefix to the source
            // prefix builds a block with exactly one unit — the whole /24 delegated at once — which
            // is a real configuration but not the one most operators are reaching for.
            prefixLengthTo: 32,
        },
    })

    const submit = async (data: z.infer<typeof addressBlockSchema>) => {
        try {
            const addressBlock = await createAddressBlock(
                addressBlockGroupId,
                data
            )

            await mutate(data => {
                if (!data) return

                return {
                    ...data,
                    items: [addressBlock, ...data.items],
                }
            }, false)

            form.reset()
            setOpen(false)
            toast.add({ title: 'Address block created', type: 'success' })
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
                        <IconPlus className={'size-4'} /> Add address block
                    </Button>
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        New Address Block
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <ResponsiveDialogBody
                            className={
                                'space-y-2 max-sm:max-h-[60vh] max-sm:overflow-y-auto'
                            }
                        >
                            <InputForm name={'name'} label={'Name'} />
                            <TextareaForm
                                name={'description'}
                                label={'Description'}
                            />
                            <TabForm
                                name={'version'}
                                tabsListProps={{
                                    className: 'grid grid-cols-2 w-full',
                                }}
                            >
                                <TabsTrigger value={'ipv4'}>IPv4</TabsTrigger>
                                <TabsTrigger value={'ipv6'}>IPv6</TabsTrigger>
                            </TabForm>
                            <InputForm name={'baseIp'} label={'Base IP'} />
                            <InputForm name={'gateway'} label={'Gateway'} />
                            <InputForm
                                name={'macAddress'}
                                label={'MAC Address'}
                            />
                            <div className={'grid grid-cols-2 gap-2'}>
                                <InputForm
                                    name={'prefixLengthFrom'}
                                    label={'Source Prefix Length'}
                                    type={'number'}
                                    min={0}
                                    max={128}
                                />
                                <InputForm
                                    name={'prefixLengthTo'}
                                    label={'Output Prefix Length'}
                                    type={'number'}
                                    min={0}
                                    max={128}
                                />
                            </div>
                        </ResponsiveDialogBody>
                        <ResponsiveDialogFooter className={'sm:mt-4'}>
                            <ResponsiveDialogClose
                                render={
                                    <Button variant={'outline'} type={'button'}>
                                        Cancel
                                    </Button>
                                }
                            />
                            <FormButton>Add address block</FormButton>
                        </ResponsiveDialogFooter>
                    </form>
                </Form>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default CreateAddressBlockModal
