import { createLazyFileRoute } from '@tanstack/react-router'

import useServersSWR from '@/api/servers/use-servers-swr.ts'

import ServerCard from '@/components/interfaces/ClientDashboard/ServerCard.tsx'


export const Route = createLazyFileRoute('/_app/_dashboard/')({
    component: Dashboard,
})

function Dashboard() {
    const { data } = useServersSWR()

    return (
        <>
            <h1 className='text-3xl font-semibold'>My Servers</h1>
            <div className={'flex flex-col space-y-3'}>
                {data?.items.map(server => (
                    <ServerCard key={server.id} server={server} />
                ))}
            </div>
            {/*<Card className={'flex items-center py-3 px-5'}>*/}
            {/*    <div*/}
            {/*        className={*/}
            {/*            'w-0 flex-auto text-ellipsis md:w-1/3 md:flex-initial md:shrink-0'*/}
            {/*        }*/}
            {/*    >*/}
            {/*        <a href={'#'} className={'font-semibold'}>*/}
            {/*            Ubuntu Server*/}
            {/*        </a>*/}
            {/*        <p className={'text-sm text-muted-foreground'}>*/}
            {/*            us-south.advinservers.com*/}
            {/*        </p>*/}
            {/*    </div>*/}
            {/*    <div className={'hidden md:block md:w-2/4'}>*/}
            {/*        <p>175.45.176.80</p>*/}
            {/*    </div>*/}
            {/*    <div className={'hidden w-1/4 flex-col md:flex lg:w-1/5'}>*/}
            {/*        <div className={'flex items-center text-sm'}>*/}
            {/*            <IconCpu*/}
            {/*                className={'mr-1.5 h-5 w-5 text-muted-foreground'}*/}
            {/*            />{' '}*/}
            {/*            <span>4 vCPU</span>*/}
            {/*        </div>*/}
            {/*        <div className={'flex items-center text-sm'}>*/}
            {/*            <IconAirConditioningDisabled*/}
            {/*                className={'mr-1.5 h-5 w-5 text-muted-foreground'}*/}
            {/*            />{' '}*/}
            {/*            <span>4 GiB</span>*/}
            {/*        </div>*/}

            {/*        <div className={'flex items-center text-sm'}>*/}
            {/*            <IconDatabase*/}
            {/*                className={'mr-1.5 h-5 w-5 text-muted-foreground'}*/}
            {/*            />{' '}*/}
            {/*            <span>128 GiB</span>*/}
            {/*        </div>*/}
            {/*    </div>*/}
            {/*    <div*/}
            {/*        className={*/}
            {/*            'relative ml-auto flex w-1/6 items-center justify-end justify-items-end md:ml-0 md:items-start lg:flex-auto'*/}
            {/*        }*/}
            {/*    >*/}
            {/*        <DropdownMenu>*/}
            {/*            <DropdownMenuTrigger asChild>*/}
            {/*                <Button size={'icon'} variant={'ghost'}>*/}
            {/*                    <IconDots className={'text-muted-foreground'} />*/}
            {/*                </Button>*/}
            {/*            </DropdownMenuTrigger>*/}
            {/*            <DropdownMenuContent align='end' className={'w-60'}>*/}
            {/*                <DropdownMenuItem>SSH to machine</DropdownMenuItem>*/}
            {/*                <DropdownMenuSeparator />*/}
            {/*                <DropdownMenuGroup>*/}
            {/*                    <DropdownMenuItem>*/}
            {/*                        Edit hostname*/}
            {/*                    </DropdownMenuItem>*/}
            {/*                    <DropdownMenuItem>*/}
            {/*                        Edit security settings*/}
            {/*                    </DropdownMenuItem>*/}
            {/*                    <DropdownMenuItem>*/}
            {/*                        Edit networking settings*/}
            {/*                    </DropdownMenuItem>*/}
            {/*                </DropdownMenuGroup>*/}
            {/*                <DropdownMenuSeparator />*/}
            {/*                <DropdownMenuItem>*/}
            {/*                    Reinstall system*/}
            {/*                </DropdownMenuItem>*/}
            {/*            </DropdownMenuContent>*/}
            {/*        </DropdownMenu>*/}
            {/*    </div>*/}
            {/*</Card>*/}
        </>
    )
}
