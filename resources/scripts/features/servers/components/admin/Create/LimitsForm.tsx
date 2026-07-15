import { InputForm } from '@/components/ui/Forms'
import { Heading } from '@/components/ui/Typography'

const LimitsForm = () => {
    return (
        <div className={'flex flex-col space-y-4'}>
            <Heading as={'h3'}>Resource Limits</Heading>

            <div className={'grid grid-cols-2 gap-4'}>
                <InputForm name={'cpu'} label={'vCPU'} type={'number'} />
                <InputForm
                    name={'memory'}
                    label={'Memory (MiB)'}
                    type={'number'}
                />
                <InputForm name={'disk'} label={'Disk (MiB)'} type={'number'} />
                <InputForm
                    name={'bandwidth'}
                    label={'Bandwidth (MiB)'}
                    type={'number'}
                    description={'Leave blank for unlimited.'}
                />
                <InputForm
                    name={'speedLimit'}
                    label={'Speed Limit (MB/s)'}
                    type={'number'}
                    description={
                        'Caps every NIC on the server. Leave blank for uncapped.'
                    }
                />
            </div>

            <Heading as={'h4'} className="pt-4">
                Backup Limits
            </Heading>
            <div className={'grid grid-cols-2 gap-4'}>
                <InputForm
                    name={'backupCount'}
                    label={'Backup Count'}
                    type={'number'}
                    description={'Use -1 for unlimited.'}
                />
                <InputForm
                    name={'backupSize'}
                    label={'Backup Storage Limit (MiB)'}
                    type={'number'}
                    description={'Use -1 for unlimited.'}
                />
            </div>
        </div>
    )
}

export default LimitsForm
