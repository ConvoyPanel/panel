import { useFormContext } from 'react-hook-form'

import TemplateGroupPicker from '@/components/interfaces/Admin/Server/Create/pickers/TemplateGroupPicker'
import TemplatePicker from '@/components/interfaces/Admin/Server/Create/pickers/TemplatePicker'
import { CheckboxForm, InputForm } from '@/components/ui/Forms'
import { Heading } from '@/components/ui/Typography'

const VmOptionsForm = () => {
    const { watch } = useFormContext()
    const deferredOsSelection = watch('deferredOsSelection')
    const shouldCreateVm = watch('shouldCreateVm')
    const templateGroupId = watch('templateGroupId')

    return (
        <div className={'flex flex-col space-y-4'}>
            <Heading as={'h3'}>Virtual Machine Options</Heading>

            <CheckboxForm
                name={'deferredOsSelection'}
                label={'Deferred OS Selection'}
                description={
                    'The user will be able to select an OS after creation.'
                }
            />

            {!deferredOsSelection && (
                <>
                    <CheckboxForm
                        name={'shouldCreateVm'}
                        label={'Create Virtual Machine'}
                        description={
                            'A virtual machine will be created on the node.'
                        }
                    />

                    {shouldCreateVm && (
                        <>
                            <InputForm
                                name={'accountPassword'}
                                label={'Password'}
                                type={'password'}
                            />

                            <TemplateGroupPicker />
                            <TemplatePicker
                                templateGroupId={templateGroupId || null}
                            />

                            <CheckboxForm
                                name={'startOnCompletion'}
                                label={'Start on Completion'}
                                description={
                                    'The server will be started after it is created.'
                                }
                            />
                        </>
                    )}
                </>
            )}
        </div>
    )
}

export default VmOptionsForm
