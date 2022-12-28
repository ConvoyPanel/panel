import { Avatar } from '@mantine/core'
import { useStoreState } from '@/state'
import { getInitials } from '@/util/helpers'
import Menu from '@/components/elements/Menu'
import { useNavigate } from 'react-router-dom'
interface Props {
    logout: () => void
}

const UserDropdown = ({ logout }: Props) => {
    const user = useStoreState(state => state.user.data!)
    const navigate = useNavigate()

    return (
        <div className='hidden sm:block'>
            <Menu>
                <Menu.Button>
                    <button className='flex items-center space-x-3 bg-transparent ring-transparent rounded-sm ring-4 hover:ring-stone-100 dark:hover:ring-stone-900 hover:bg-stone-100 dark:hover:bg-stone-900 transition'>
                        <p className='text-foreground font-medium text-sm'>{user.name}</p>
                        <Avatar color='blue' size='md' radius='xl'>
                            {getInitials(user.name, ' ', 2)}
                        </Avatar>
                    </button>
                </Menu.Button>

                <Menu.Items>
                    {user.rootAdmin && <>
                      <Menu.Item onClick={() => navigate('/admin')}>Admin Center</Menu.Item>
                      <Menu.Divider />
                    </>}
                    <Menu.Item color='danger' onClick={logout}>
                        Log Out
                    </Menu.Item>
                </Menu.Items>
            </Menu>
        </div>
    )
}

export default UserDropdown
