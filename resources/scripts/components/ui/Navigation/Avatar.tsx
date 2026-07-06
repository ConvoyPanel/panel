import { useTheme } from '@/providers/theme-provider.tsx'
import useIdentityConfirmationStore from '@/stores/identity-confirmation-store.ts'
import { useRouter } from '@tanstack/react-router'
import { useQueryClient } from '@tanstack/react-query'
import { useShallow } from 'zustand/react/shallow'

import logout from '@/api/auth/logout.ts'
import useUser, { currentUserQueries } from '@/api/auth/use-user.ts'

import Logo from '@/components/ui/Branding/Logo.tsx'
import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'

const Avatar = () => {
    const { theme, setTheme } = useTheme()
    const { data: user } = useUser()
    const queryClient = useQueryClient()
    const reset = useIdentityConfirmationStore(useShallow(state => state.reset))

    const { navigate } = useRouter()

    const signout = async () => {
        await logout()
        reset()
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
                >
                    <Logo className='h-6 w-6 rounded-full' />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align='end' className={'w-60'}>
                <DropdownMenuLabel className={'truncate'}>
                    {user?.name}
                </DropdownMenuLabel>
                <p
                    className={
                        '-mt-1 mb-3 truncate px-2 text-xs text-muted-foreground'
                    }
                >
                    {user?.email}
                </p>
                <div className={'mt-2 flex items-center space-x-14 px-2 py-1'}>
                    <span className={'text-sm'}>Theme</span>
                    <Select value={theme} onValueChange={setTheme}>
                        <SelectTrigger className={'w-28'}>
                            <SelectValue placeholder='Select a fruit' />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value='light'>Light</SelectItem>
                                <SelectItem value='dark'>Dark</SelectItem>
                                <SelectItem value='system'>System</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={signout}>Sign out</DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    )
}

export default Avatar
