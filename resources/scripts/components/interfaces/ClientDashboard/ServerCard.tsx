import { Server } from '@/types/server.ts'
import {
    IconAirConditioningDisabled,
    IconCpu,
    IconDatabase,
    IconDots,
} from '@tabler/icons-react'
import byteSize from 'byte-size'

import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'

interface Props {
    server: Server
}

const ServerCard = ({ server }: Props) => {
    const memory = byteSize(server.memory, { units: 'iec', precision: 0 })
    const disk = byteSize(server.disk, { units: 'iec', precision: 0 })

    return (
        <Card className={'flex items-center py-2.5 px-5'}>
            <div
                className={
                    'w-0 flex-auto text-ellipsis md:w-1/3 md:flex-initial md:shrink-0'
                }
            >
                <a href={'#'} className={'font-semibold'}>
                    {server.name}
                </a>
                <p className={'text-sm text-muted-foreground'}>
                    {server.hostname}
                </p>
            </div>
            <div className={'hidden md:block md:w-2/4'}>
                <p>175.45.176.80</p>
            </div>
            <div className={'hidden w-1/4 flex-col md:flex lg:w-1/5'}>
                <div className={'flex items-center text-sm'}>
                    <IconCpu
                        className={'mr-1.5 h-5 w-5 text-muted-foreground'}
                    />{' '}
                    <span>{server.cpu} vCPU</span>
                    <span className={'sr-only'}>CPU count</span>
                </div>
                <div className={'flex items-center text-sm'}>
                    <IconAirConditioningDisabled
                        className={'mr-1.5 h-5 w-5 text-muted-foreground'}
                    />{' '}
                    <span>
                        {memory.value} {memory.unit}
                    </span>
                    <span className={'sr-only'}>Memory size</span>
                </div>

                <div className={'flex items-center text-sm'}>
                    <IconDatabase
                        className={'mr-1.5 h-5 w-5 text-muted-foreground'}
                    />{' '}
                    <span>
                        {disk.value} {disk.unit}
                    </span>
                    <span className={'sr-only'}>Disk size</span>
                </div>
            </div>
            <div
                className={
                    'relative ml-auto flex w-1/6 items-center justify-end justify-items-end md:ml-0 md:items-start lg:flex-auto'
                }
            >
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button size={'icon'} variant={'ghost'}>
                            <IconDots className={'text-muted-foreground'} />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align='end' className={'w-60'}>
                        <DropdownMenuItem>SSH to machine</DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            <DropdownMenuItem>Edit hostname</DropdownMenuItem>
                            <DropdownMenuItem>
                                Edit security settings
                            </DropdownMenuItem>
                            <DropdownMenuItem>
                                Edit networking settings
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem>Reinstall system</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </Card>
    )
}

export default ServerCard
