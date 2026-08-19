import { deleteServerPreset } from '@/features/servers/presets/api.ts'
import { useModal } from '@/hooks/create-modal-store.ts'
import useAsyncFunction from '@/hooks/use-async-function.ts'
import { useServerPresetsModalStore } from '@/routes/_app/admin/_dashboard/server-presets.lazy.tsx'
import { Mutator } from '@/types/query.ts'
import type { ServerPreset } from '@/types/server-preset'

import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogDescription,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import { toast } from '@/components/ui/Toast'

interface Props {
    mutate: Mutator<ServerPreset[]>
}

const DeletePresetModal = ({ mutate }: Props) => {
    const {
        open,
        data: preset,
        close,
    } = useModal(useServerPresetsModalStore, 'delete')

    const [state, submit] = useAsyncFunction(async (current: ServerPreset) => {
        try {
            await deleteServerPreset(current.uuid)

            await mutate(presets =>
                presets?.filter(item => item.uuid !== current.uuid)
            )

            toast.add({ title: 'Preset deleted', type: 'success' })

            close()
        } catch (e) {
            toast.add({ title: 'Deletion failed', type: 'error' })
            throw e
        }
    })

    return (
        <ResponsiveDialog open={open} onOpenChange={open => !open && close()}>
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Delete {preset?.name}
                    </ResponsiveDialogTitle>
                    <ResponsiveDialogDescription>
                        Servers already created from this preset are not
                        affected — only the saved configuration goes away.
                    </ResponsiveDialogDescription>
                </ResponsiveDialogHeader>
                <ResponsiveDialogFooter className={'mt-4'}>
                    <ResponsiveDialogClose
                        render={<Button variant={'outline'}>Cancel</Button>}
                    />
                    <Button
                        autoFocus
                        loading={state.loading}
                        variant={'destructive'}
                        onClick={() => preset && submit(preset)}
                    >
                        Delete
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default DeletePresetModal
