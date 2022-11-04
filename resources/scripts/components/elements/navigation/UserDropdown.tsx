import { Avatar, LoadingOverlay, Menu } from '@mantine/core'
import { useStoreState } from '@/state'
import { getInitials } from '@/util/helpers'
interface Props {
  logout: () => void
}

const UserDropdown = ({ logout }: Props) => {
  const user = useStoreState((state) => state.user.data!)

  return (
    <div className='hidden sm:block'>
      <Menu width={175} shadow='lg'>
        <Menu.Target>
          <button className='flex items-center space-x-3 bg-transparent ring-transparent rounded-sm ring-4 hover:ring-stone-100 dark:hover:ring-stone-900 hover:bg-stone-100 dark:hover:bg-stone-900 transition'>
            <p className='text-auto font-medium text-sm'>{user.name}</p>
            <Avatar color='blue' size='md' radius='xl'>
              {getInitials(user.name, ' ', 2)}
            </Avatar>
          </button>
        </Menu.Target>

        <Menu.Dropdown>
          <Menu.Item color='red' onClick={logout}>Log Out</Menu.Item>
        </Menu.Dropdown>
      </Menu>
    </div>
  )
}

export default UserDropdown
