import TemplateGroupPicker from '@/features/servers/components/admin/Create/pickers/TemplateGroupPicker'
import TemplatePicker from '@/features/servers/components/admin/Create/pickers/TemplatePicker'
import { useWatch } from 'react-hook-form'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { CheckboxForm, FieldFold, InputForm } from '@/components/ui/Forms'

/**
 * What gets installed.
 *
 * The image and its password are per-server answers; the three switches that
 * decide *whether* anything is installed are almost always left alone, so they
 * fold into a line describing what will happen. The boxed `CheckboxForm` stays
 * boxed — this is a card where the operator is choosing, and a bare switch row
 * among the pickers would read as a different design.
 */
const OperatingSystemSection = () => {
    const deferredOsSelection = useWatch({ name: 'deferredOsSelection' })
    const shouldCreateVm = useWatch({ name: 'shouldCreateVm' })
    const startOnCompletion = useWatch({ name: 'startOnCompletion' })
    const templateGroupId = useWatch({ name: 'templateGroupId' })

    const summary = deferredOsSelection
        ? 'The owner picks the OS · nothing is installed now'
        : !shouldCreateVm
          ? 'Recorded in Convoy only · not built on the node'
          : startOnCompletion
            ? 'Build now · start when the install finishes'
            : 'Build now · leave it powered off'

    return (
        <Card className={'@container'}>
            <CardHeader>
                <CardTitle>Operating system</CardTitle>
                <CardDescription>
                    The image this server is built from, and what happens once
                    it is.
                </CardDescription>
            </CardHeader>
            <CardContent className={'space-y-4'}>
                {!deferredOsSelection && shouldCreateVm && (
                    <>
                        <div
                            className={
                                'grid grid-cols-1 gap-3 @2xl:grid-cols-2'
                            }
                        >
                            <TemplateGroupPicker />
                            <TemplatePicker
                                templateGroupId={templateGroupId || null}
                            />
                        </div>

                        <InputForm
                            name={'accountPassword'}
                            label={'Root password'}
                            type={'password'}
                            autoComplete={'new-password'}
                            description={'Set on the guest at install.'}
                        />
                    </>
                )}

                <FieldFold
                    fields={[
                        'deferredOsSelection',
                        'shouldCreateVm',
                        'startOnCompletion',
                    ]}
                    summary={summary}
                >
                    <div className={'space-y-3'}>
                        <CheckboxForm
                            name={'deferredOsSelection'}
                            label={'Let the owner choose the OS'}
                            description={
                                'Nothing is installed now — the server is handed over for its owner to pick an image.'
                            }
                        />

                        {!deferredOsSelection && (
                            <>
                                <CheckboxForm
                                    name={'shouldCreateVm'}
                                    label={'Create the virtual machine now'}
                                    description={
                                        'Leave off to record the server in Convoy without building it on the node.'
                                    }
                                />

                                {shouldCreateVm && (
                                    <CheckboxForm
                                        name={'startOnCompletion'}
                                        label={
                                            'Start once the install finishes'
                                        }
                                        description={
                                            'The server boots as soon as it is built.'
                                        }
                                    />
                                )}
                            </>
                        )}
                    </div>
                </FieldFold>
            </CardContent>
        </Card>
    )
}

export default OperatingSystemSection
