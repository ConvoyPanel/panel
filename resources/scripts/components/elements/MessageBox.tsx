import { Alert } from '@mantine/core'

export type FlashMessageType = 'success' | 'info' | 'warning' | 'error'

const getColor = (type?: FlashMessageType) => {
  switch (type) {
    case 'success':
      return 'green'
    case 'info':
      return 'blue'
    case 'warning':
      return 'yellow'
    case 'error':
      return 'red'
    default:
      return 'gray'
  }
}

interface Props {
    title?: string;
    children: string;
    type?: FlashMessageType;
}

const MessageBox = ({ title, children, type }: Props) => {
  return <Alert title={title} color={getColor(type)}>{children}</Alert>
}

export default MessageBox
