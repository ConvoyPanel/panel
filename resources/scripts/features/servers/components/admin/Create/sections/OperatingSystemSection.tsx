import ImageGroupPicker from '@/features/servers/components/admin/Create/pickers/ImageGroupPicker'
import ImagePicker from '@/features/servers/components/admin/Create/pickers/ImagePicker'
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
 * The three switches deciding *whether* anything is installed lead the card even
 * though they are almost always left alone, because they are what puts the image
 * and its password on screen at all — asking for a root password above the
 * checkbox that governs whether it is asked for read backwards. They are folded
 * into a line describing what will happen, so leading with them costs one row.
 *
 * The boxed `CheckboxForm` stays boxed — this is a card where the operator is
 * choosing, and a bare switch row among the pickers would read as a different
 * design.
 */
const OperatingSystemSection = () => {
    const deferredOsSelection = useWatch({ name: 'deferredOsSelection' })
    const shouldCreateVm = useWatch({ name: 'shouldCreateVm' })
    const startOnCompletion = useWatch({ name: 'startOnCompletion' })
    const imageGroupId = useWatch({ name: 'imageGroupId' })

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
                    What happens when this server is created, and the image it
                    is built from.
                </CardDescription>
            </CardHeader>
            <CardContent className={'space-y-4'}>
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

                {!deferredOsSelection && shouldCreateVm && (
                    <>
                        <div
                            className={
                                'grid grid-cols-1 gap-3 @2xl:grid-cols-2'
                            }
                        >
                            <ImageGroupPicker />
                            <ImagePicker
                                imageGroupId={imageGroupId || null}
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
            </CardContent>
        </Card>
    )
}

export default OperatingSystemSection
