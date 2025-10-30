import { useFormContext } from 'react-hook-form'

import NodePicker from '@/components/interfaces/Admin/Server/Create/pickers/NodePicker'
import StoragePicker from '@/components/interfaces/Admin/Server/Create/pickers/StoragePicker'
import UserPicker from '@/components/interfaces/Admin/Server/Create/pickers/UserPicker'
import { InputForm } from '@/components/ui/Forms'
import { Heading } from '@/components/ui/Typography'

const GeneralForm = () => {
    const { watch } = useFormContext()
    const nodeId = watch('nodeId')

    return (
        <div className={'flex flex-col space-y-4'}>
            <Heading as={'h3'}>General</Heading>
            <InputForm name={'name'} label={'Server Name'} />
            <InputForm name={'hostname'} label={'Hostname'} />
            <InputForm
                name={'vmid'}
                label={'VM ID'}
                description={
                    'If you leave this blank, the next available VM ID will be used.'
                }
            />

            <UserPicker />
            <NodePicker />
            <StoragePicker nodeId={nodeId ? Number(nodeId) : null} />
        </div>
    )
}

export default GeneralForm
