import { useFlashKey } from '@/util/useFlash'
import { port } from '@/util/validation'
import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { FormProvider, useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import { KeyedMutator } from 'swr'
import { z } from 'zod'

import createCoterm from '@/api/admin/coterms/createCoterm'
import { CotermResponse } from '@/api/admin/coterms/getCoterms'

import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import Modal from '@/components/elements/Modal'
import CheckboxForm from '@/components/elements/forms/CheckboxForm'
import TextInputForm from '@/components/elements/forms/TextInputForm'

import CotermNodesMultiSelectForm from '@/components/admin/coterms/CotermNodesMultiSelect'
import CotermTokenModal from '@/components/admin/coterms/CotermTokenModal'


interface Props {
    open: boolean
    onClose: () => void
    mutate: KeyedMutator<CotermResponse>
}

const CreateCotermModal = ({ open, onClose, mutate }: Props) => {
    const { t: tStrings } = useTranslation('strings')
    const { clearFlashes, clearAndAddHttpError } =
        useFlashKey(`admin.coterms.create`)

    const [cotermToken, setCotermToken] = useState<string | null>(null)

    const schema = z.object({
        name: z.string().min(1).max(191),
        isTlsEnabled: z.boolean(),
        fqdn: z.string().min(1).max(191),
        port: port(z.coerce.number()),
        nodeIds: z.array(z.coerce.number()),
    })

    const form = useForm({
        resolver: zodResolver(schema),
        defaultValues: {
            name: '',
            isTlsEnabled: false,
            fqdn: '',
            port: 443,
            nodeIds: [] as string[],
        },
    })

    const handleClose = () => {
        form.reset()
        setCotermToken(null)
        onClose()
    }

    const submit = async (_data: any) => {
        const data = _data as z.infer<typeof schema>

        clearFlashes()
        try {
            const coterm = await createCoterm(data)

            mutate(data => {
                if (!data) return data
                if (data.pagination.currentPage !== 1) return data

                return {
                    ...data,
                    items: [coterm, ...data.items],
                }
            }, false)

            setCotermToken(`${coterm!.tokenId}|${coterm!.token}`)
        } catch (e) {
            clearAndAddHttpError(e as Error)
        }
    }

    return (
        <>
            <CotermTokenModal value={cotermToken} onClose={handleClose} />
            <Modal open={open} onClose={handleClose}>
                <Modal.Header>
                    <Modal.Title>Create Instance</Modal.Title>
                </Modal.Header>

                <FormProvider {...form}>
                    <form onSubmit={form.handleSubmit(submit)}>
                        <Modal.Body>
                            <FlashMessageRender
                                className='mb-5'
                                byKey={`admin.coterms.create`}
                            />
                            <TextInputForm
                                name='name'
                                label={tStrings('name')}
                            />
                            <div className={'space-y-3'}>
                                <TextInputForm
                                    name='fqdn'
                                    label={tStrings('fqdn')}
                                />
                                <CheckboxForm
                                    name={'isTlsEnabled'}
                                    label={'Is TLS Enabled?'}
                                />
                            </div>
                            <TextInputForm
                                name='port'
                                label={tStrings('port')}
                            />
                            <CotermNodesMultiSelectForm />
                        </Modal.Body>

                        <Modal.Actions>
                            <Modal.Action type='button' onClick={handleClose}>
                                {tStrings('cancel')}
                            </Modal.Action>
                            <Modal.Action
                                type='submit'
                                loading={form.formState.isSubmitting}
                            >
                                {tStrings('create')}
                            </Modal.Action>
                        </Modal.Actions>
                    </form>
                </FormProvider>
            </Modal>
        </>
    )
}

export default CreateCotermModal