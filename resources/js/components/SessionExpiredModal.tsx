
import getAuthState from '@/api/user/getAuthState'
import { Inertia } from '@inertiajs/inertia'
import { Button, Modal } from '@mantine/core'
import { useEffect, useState } from 'react'

const SessionExpiredModal = () => {
  const [visible, setVisible] = useState(false)

  useEffect(() => {
    const checkAuthState = setInterval(async () => {
        const status = await getAuthState()

        if (!status) {
            setVisible(true)
            clearInterval(checkAuthState)
        }
    }, 10000)

    return () => {
        clearInterval(checkAuthState)
    }
  }, [])

  return (
    <Modal
      title='Session Expired'
      opened={visible}
      onClose={() => setVisible(false)}
      centered
    >
      <p className='p-desc'>
        Your session has expired. Please log in again. To prevent this from happening, please check "remember me" when logging in.
      </p>

        <Button className='mt-3' fullWidth onClick={() => Inertia.visit(route('login'))}>I understand, send me to login.</Button>
    </Modal>
  )
}

export default SessionExpiredModal
