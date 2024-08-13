import { cn } from '@/utils'

import ConsoleButton from '@/components/interfaces/Client/Server/Overview/ConsoleButton.tsx'
import PowerActionsDropdown from '@/components/interfaces/Client/Server/Overview/PowerActionsDropdown.tsx'
import PowerActionsExpanded from '@/components/interfaces/Client/Server/Overview/PowerActionsExpanded.tsx'


export const actions = {
    start: {
        buttonText: 'Start server',
        toastText: 'Server queued to start',
        title: 'Start server?',
        description: 'This action will start your server.',
    },
    restart: {
        buttonText: 'Restart server',
        toastText: 'Server queued to restart',
        title: 'Restart server?',
        description: 'Make sure to save your work before restarting.',
    },
    kill: {
        buttonText: 'Kill server',
        toastText: 'Server queued to be killed',
        title: 'Kill server?',
        description: 'Data may be lost if you kill your server.',
    },
    shutdown: {
        buttonText: 'Shutdown server',
        toastText: 'Server queued to be shutdown',
        title: 'Shutdown server?',
        description:
            'Make sure to save your work before shutting down your server.',
    },
}

interface Props {
    className?: string
}

const Toolbar = ({ className }: Props) => {
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
