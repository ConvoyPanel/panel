import { useState } from 'react'
import { Credenza, CredenzaContent, CredenzaHeader, CredenzaTitle, CredenzaTrigger } from '@/components/ui/Credenza'
import { IconPlus } from '@tabler/icons-react'
import { Button } from '@/components/ui/Button'
import { KeyedMutator } from 'swr'
import { Storage } from '@/types/storage.ts'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { storageSchema } from '@/api/admin/nodes/storages/createStorage.ts'

interface Props {
    mutate: KeyedMutator<Storage[]>
}

const CreateStorageModal = ({ mutate }: Props) => {
    const [open, setOpen] = useState(false)

    const form = useForm({
        resolver: zodResolver(storageSchema),
        defaultValues: {
            displayName: '',
            description: '',
            name: '',
            size: '',
            isShareable: false,
            storesKvm: false,
            storesLxc: false,
            storesLxcTemplates: false,
            storesBackups: false,
            storesIso: false,
            storesSnippets: false,
        },
    })

    return <Credenza open={open} onOpenChange={setOpen}>
        <CredenzaTrigger asChild>
            <Button className={'flex ml-auto'} size={'sm'}>
                <IconPlus className={'mr-2 size-4'} /> Add storage
            </Button>
        </CredenzaTrigger>
        <CredenzaContent>
            <CredenzaHeader>
                <CredenzaTitle>New Storage</CredenzaTitle>
            </CredenzaHeader>

        </CredenzaContent>
    </Credenza>
}

export default CreateStorageModal