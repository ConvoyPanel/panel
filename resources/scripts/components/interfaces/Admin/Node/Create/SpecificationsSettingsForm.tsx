import { IconInfoCircleFilled } from '@tabler/icons-react'

import MemoryPreviewAccordion from '@/components/interfaces/Admin/Node/Create/MemoryPreviewAccordion.tsx'

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/Alert'
import { InputForm } from '@/components/ui/Forms'
import { Heading } from '@/components/ui/Typography'

const SpecificationsSettingsForm = () => {
    return (
        <div className={'flex flex-col space-y-4'}>
            <Heading as={'h3'}>Specifications</Heading>
            <div className={'flex space-x-3'}>
                <InputForm
                    name={'socketCount'}
                    label={'Sockets'}
                    type={'number'}
                    formItemProps={{ className: 'grow' }}
                />
                <InputForm
                    name={'coreCount'}
                    label={'Cores'}
                    type={'number'}
                    formItemProps={{ className: 'grow' }}
                />
                <InputForm
                    name={'cpuCount'}
                    label={'CPUs'}
                    type={'number'}
                    formItemProps={{ className: 'grow' }}
                />
            </div>
            <div className={'flex space-x-3'}>
                <InputForm
                    name={'memory'}
                    label={'Memory (MiB)'}
                    type={'number'}
                    formItemProps={{ className: 'grow' }}
                />
                <InputForm
                    name={'memoryOverallocate'}
                    label={'Memory Overallocate (%)'}
                    type={'number'}
                    formItemProps={{
                        className: 'grow',
                    }}
                />
            </div>
            <MemoryPreviewAccordion />
            <Alert>
                <IconInfoCircleFilled className={'size-4'} />
                <AlertTitle>
                    Storage and network configuration has moved
                </AlertTitle>
                <AlertDescription>
                    The storage and network configuration options have been moved
                    to the <strong>Advanced</strong> tab. This change was made
                    to simplify the node creation process and make it easier to
                    configure the most important settings.
                </AlertDescription>
            </Alert>
        </div>
    )
}

export default SpecificationsSettingsForm
