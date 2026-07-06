import { PaginatedAddressBlocks } from '@/types/address-block.ts'
import { AddressVersion } from '@/types/address.ts'
import { handleFormErrors } from '@/utils/http.ts'
import { zodResolver } from '@hookform/resolvers/zod'
import { IconPlus } from '@tabler/icons-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { Mutator } from '@/types/query.ts'
import { z } from 'zod'

import {
    createAddressBlock,
    addressBlockSchema,
} from '@/features/ipam/blocks/api.ts'

import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
    CredenzaTrigger,
} from '@/components/ui/Credenza'
import { Form, FormButton } from '@/components/ui/Form'
import { InputForm, TextareaForm } from '@/components/ui/Forms'
import TabForm from '@/components/ui/Forms/TabForm.tsx'
import { TabsTrigger } from '@/components/ui/Tabs'

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
            prefixLengthTo: 24,
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
            toast.success('Address block created')
        } catch (e) {
            handleFormErrors(e, form.setError)
            toast.error('Failed to save changes')
            throw e
        }
    }

    return (
        <Credenza open={open} onOpenChange={setOpen}>
            <CredenzaTrigger asChild>
                <Button size={'sm'}>
                    <IconPlus className={'mr-2 size-4'} /> Add address block
                </Button>
            </CredenzaTrigger>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle>New Address Block</CredenzaTitle>
                </CredenzaHeader>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(submit as any)}>
                        <CredenzaBody className={'space-y-2 max-sm:max-h-[60vh] max-sm:overflow-y-auto'}>
                            <InputForm name={'name'} label={'Name'} />
                            <TextareaForm
                                name={'description'}
                                label={'Description'}
                            />
                            <TabForm name={'version'} tabsListProps={{className: 'grid grid-cols-2 w-full'}}>
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
                        </CredenzaBody>
                        <CredenzaFooter className={'sm:mt-4'}>
                            <CredenzaClose asChild>
                                <Button variant={'outline'} type={'button'}>
                                    Cancel
                                </Button>
                            </CredenzaClose>
                            <FormButton>Add address block</FormButton>
                        </CredenzaFooter>
                    </form>
                </Form>
            </CredenzaContent>
        </Credenza>
    )
}

export default CreateAddressBlockModal