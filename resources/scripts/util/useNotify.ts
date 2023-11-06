import { NotificationProps, showNotification } from '@mantine/notifications'

const useNotify = () => {
    return (props: Omit<NotificationProps, 'style'>) =>
        showNotification({
            ...props,
            style: {
                transitionTimingFunction: 'cubic-bezier(0.4, 0, 0.2, 1)',
            },
        })
}

export default useNotify