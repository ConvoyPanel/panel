import ConsoleButton from '@/features/servers/components/client/Overview/ConsoleButton.tsx'
import PowerActionsDropdown from '@/features/servers/components/client/Overview/PowerActionsDropdown.tsx'
import PowerActionsExpanded from '@/features/servers/components/client/Overview/PowerActionsExpanded.tsx'
import { usePowerActionToast } from '@/features/servers/detail/use-power-action-toast.ts'
import { cn } from '@/utils'

export const actions = {
    start: {
        buttonText: 'Start server',
        toastText: 'Server queued to start',
        title: 'Start server?',
        description: 'This action will start your server',
    },
    restart: {
        buttonText: 'Restart server',
        toastText: 'Server queued to restart',
        title: 'Restart server?',
        description:
            'Please save your work and close out of any running processes. Any unsaved changes will be lost, and active sessions may be interrupted. Proceed only when it is safe to restart',
    },
    kill: {
        buttonText: 'Kill server',
        toastText: 'Server queued to be killed',
        title: 'Kill server?',
        description:
            'This action will immediately stop all processes, resulting in loss of any unsaved data. Ensure that all critical tasks are saved and completed before proceeding',
    },
    shutdown: {
        buttonText: 'Shutdown server',
        toastText: 'Server queued to be shutdown',
        title: 'Shutdown server?',
        description:
            'This will power off all services and processes, potentially causing data loss if there are unsaved changes. Please ensure all work is saved and close any active connections before proceeding',
    },
}

interface Props {
    className?: string
}

const Toolbar = ({ className }: Props) => {
    // Surfaces the in-progress / success / failure toast for power actions on
    // this server; dismissed automatically when the overview unmounts.
    usePowerActionToast()

    return (
        <>
            <div
                className={cn(
                    'grid grid-cols-2 justify-end gap-2 @sm:flex',
                    className
                )}
            >
                <ConsoleButton />
                <PowerActionsDropdown />
                <PowerActionsExpanded />
            </div>
        </>
    )
}

export default Toolbar
