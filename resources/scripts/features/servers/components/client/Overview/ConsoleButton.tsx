import type { ConsoleType } from '@/features/servers/console/api'
import { useServer } from '@/features/servers/detail/api.ts'
import { IconExternalLink } from '@tabler/icons-react'
import { useNavigate } from '@tanstack/react-router'
import { useState } from 'react'

import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/Tabs'
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/Tooltip'

const ConsoleButton = () => {
    const [type, setType] = useState<ConsoleType>('novnc')
    const { data: server } = useServer()
    const navigate = useNavigate()
    const openNewWindow = () => {
        if (!server) return
        window.open(
            `/servers/${server.uuid}/console?type=${type}`,
            '_blank',
            'noopener,noreferrer'
        )
    }
    const open = () =>
        server &&
        navigate({
            to: '/servers/$serverUuid/console',
            params: { serverUuid: server.uuid },
            search: { type },
        })
    return (
        <ResponsiveDialog>
            <ResponsiveDialogTrigger
                render={<Button variant='outline'>Console</Button>}
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>Open console</ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody>
                    <Tabs
                        value={type}
                        onValueChange={value => setType(value as ConsoleType)}
                    >
                        <TabsList className='w-full'>
                            <TabsTrigger value='novnc'>Display</TabsTrigger>
                            <TabsTrigger value='xtermjs'>Terminal</TabsTrigger>
                        </TabsList>
                    </Tabs>
                </ResponsiveDialogBody>
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose
                        render={<Button variant='outline'>Cancel</Button>}
                    />
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Button
                                variant='outline'
                                size='icon'
                                disabled={!server}
                                onClick={openNewWindow}
                            >
                                <IconExternalLink />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Open in new window</TooltipContent>
                    </Tooltip>
                    <Button disabled={!server} onClick={open}>
                        Open console
                    </Button>
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default ConsoleButton
