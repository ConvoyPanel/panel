import NodePicker from '@/features/servers/components/admin/Create/pickers/NodePicker'
import StoragePicker from '@/features/servers/components/admin/Create/pickers/StoragePicker'
import UserPicker from '@/features/servers/components/admin/Create/pickers/UserPicker'
import { useWatch } from 'react-hook-form'

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { FieldFold, InputForm } from '@/components/ui/Forms'

/**
 * The five answers that are genuinely written per server, plus the one that
 * almost never is.
 *
 * Placement used to be a card of its own. It is two pickers, and a card header
 * costs two lines before either of them — so the node and its storage sit here,
 * beside the name and owner, as part of the same question: which server is
 * this, and where does it run.
 */
const GeneralSection = () => {
    const nodeId = useWatch({ name: 'nodeId' })
    const vmid = useWatch({ name: 'vmid' })

    return (
        <Card className={'@container'}>
            <CardHeader>
                <CardTitle>General</CardTitle>
                <CardDescription>
                    What the server is called, who owns it, and where it runs.
                </CardDescription>
            </CardHeader>
            <CardContent className={'space-y-4'}>
                <div className={'grid grid-cols-1 gap-3 @2xl:grid-cols-2'}>
                    <InputForm name={'name'} label={'Server Name'} />
                    <InputForm name={'hostname'} label={'Hostname'} />
                </div>

                <div className={'grid grid-cols-1 gap-3 @2xl:grid-cols-2'}>
                    <UserPicker />
                    <NodePicker />
                </div>

                {/* Full width: the storage option is a two-line item (name over
                    free space), so half a row crops it. */}
                <StoragePicker
                    nodeId={nodeId ? Number(nodeId) : null}
                    requiredContentTypes={['storesKvm']}
                />

                <FieldFold
                    fields={['vmid']}
                    summary={`VM ID · ${vmid ? vmid : 'next free on the node'}`}
                >
                    <InputForm name={'vmid'} label={'VM ID'} type={'number'} />
                </FieldFold>
            </CardContent>
        </Card>
    )
}

export default GeneralSection
