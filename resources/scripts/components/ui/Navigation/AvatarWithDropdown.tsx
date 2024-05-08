import { useTheme } from '@/providers/theme-provider.tsx'
import { useRouter } from '@tanstack/react-router'

import logout from '@/api/auth/logout.ts'
import useUserSWR from '@/api/auth/use-user-swr.ts'

import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'
import Logo from '@/components/ui/Logo.tsx'
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'

const AvatarWithDropdown = () => {
    const { theme, setTheme } = useTheme()
    const { data: user, mutate } = useUserSWR()

    const { navigate } = useRouter()

    const signout = async () => {
        await logout()
        await mutate(undefined, false)
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
                        'mb-3 -mt-1 truncate px-2 text-xs text-muted-foreground'
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

export default AvatarWithDropdown
