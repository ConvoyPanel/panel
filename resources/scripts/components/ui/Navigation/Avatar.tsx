import { logout } from '@/features/auth/api.ts'
import { currentUserQueries, useUser } from '@/features/auth/api.ts'
import { identityQueries } from '@/features/auth/identity/api.ts'
import { useTheme } from '@/providers/theme-provider.tsx'
import { cn } from '@/utils'
import { useQueryClient } from '@tanstack/react-query'
import { Link, useLocation, useRouter } from '@tanstack/react-router'

import Logo from '@/components/ui/Branding/Logo.tsx'
import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'

const Avatar = () => {
    const { theme, setTheme } = useTheme()
    const { data: user } = useUser()
    const queryClient = useQueryClient()

    const { navigate } = useRouter()
    const pathname = useLocation({ select: location => location.pathname })
    const isAdminArea = pathname.startsWith('/admin')

    const signout = async () => {
        await logout()
        // Identity confirmation lives in the session the logout just destroyed;
        // drop the cached answer with the rest of the session's state.
        queryClient.removeQueries({ queryKey: identityQueries.all() })
        queryClient.removeQueries({ queryKey: currentUserQueries.all() })
        await navigate({ to: '/auth/login' })
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant='outline'
                    size='icon'
                    className='overflow-hidden rounded-full'
                    aria-label={'Open account menu'}
                >
                    <Logo className='h-6 w-6 rounded-full' />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align='end' className={'w-60'}>
                <DropdownMenuGroup>
                    <DropdownMenuLabel className={'truncate'}>
                        {user?.name}
                    </DropdownMenuLabel>
                    <p
                        className={
                            'text-muted-foreground -mt-1 mb-1 truncate px-1.5 text-xs'
                        }
                    >
                        {user?.email}
                    </p>
                </DropdownMenuGroup>
                {user?.rootAdmin ? (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            <DropdownMenuLabel className='text-muted-foreground text-xs font-medium'>
                                Workspace
                            </DropdownMenuLabel>
                            <DropdownMenuItem
                                asChild
                                className={cn(
                                    !isAdminArea &&
                                        'bg-accent text-accent-foreground'
                                )}
                            >
                                <Link
                                    to='/'
                                    aria-current={
                                        !isAdminArea ? 'page' : undefined
                                    }
                                >
                                    <span className='min-w-0 flex-1 truncate'>
                                        Client Area
                                    </span>
                                    {!isAdminArea ? (
                                        <span className='text-muted-foreground ml-auto text-xs'>
                                            Current
                                        </span>
                                    ) : null}
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                asChild
                                className={cn(
                                    isAdminArea &&
                                        'bg-accent text-accent-foreground'
                                )}
                            >
                                <Link
                                    to='/admin'
                                    aria-current={
                                        isAdminArea ? 'page' : undefined
                                    }
                                >
                                    <span className='min-w-0 flex-1 truncate'>
                                        Admin Console
                                    </span>
                                    {isAdminArea ? (
                                        <span className='text-muted-foreground ml-auto text-xs'>
                                            Current
                                        </span>
                                    ) : null}
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                    </>
                ) : null}
                <DropdownMenuSeparator />
                <DropdownMenuSub>
                    <DropdownMenuSubTrigger>
                        <span className={'flex-1'}>Theme</span>
                        <span
                            className={
                                'text-xs capitalize text-muted-foreground'
                            }
                        >
                            {theme}
                        </span>
                    </DropdownMenuSubTrigger>
                    <DropdownMenuSubContent>
                        <DropdownMenuRadioGroup
                            value={theme}
                            onValueChange={setTheme}
                        >
                            <DropdownMenuRadioItem value={'light'}>
                                Light
                            </DropdownMenuRadioItem>
                            <DropdownMenuRadioItem value={'dark'}>
                                Dark
                            </DropdownMenuRadioItem>
                            <DropdownMenuRadioItem value={'system'}>
                                System
                            </DropdownMenuRadioItem>
                        </DropdownMenuRadioGroup>
                    </DropdownMenuSubContent>
                </DropdownMenuSub>
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={signout}>Sign out</DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    )
}

export default Avatar
