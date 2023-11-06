import SettingsLayout from '@/components/elements/layouts/SettingsLayout'

import UserContentBlock from '@/components/admin/users/UserContentBlock'

const UserSettingsContainer = () => {
    return (
        <SettingsLayout
            indexPattern='/admin/users/:id/settings'
            defaultUrl='/admin/users/:id/settings/general'
            contentBlock={props => (
                <UserContentBlock
                    title='Server Settings'
                    showFlashKey='admin:user:settings'
                    {...props}
                />
            )}
            routes={[
                {
                    path: '/admin/users/:id/settings/general',
                    name: 'General',
                },
            ]}
        />
    )
}

export default UserSettingsContainer