import RoundedButton from '@/components/RoundedButton'
import { PencilIcon, TrashIcon } from '@heroicons/react/solid'

interface Props {
  onClick?: () => void
}
const DeleteButton = ({ onClick }: Props) => {
  return (
    <RoundedButton onClick={() => onClick && onClick()}>
      <TrashIcon className='text-gray-600 hover:text-red-600 w-[18px] h-[18px]' />
    </RoundedButton>
  )
}

export default DeleteButton
